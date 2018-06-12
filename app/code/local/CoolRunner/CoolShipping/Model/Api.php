<?php

class CoolRunner_CoolShipping_Model_Api extends Mage_Core_Model_Abstract
{

    const URL_FREIGHT_RATES = "https://api.coolrunner.dk/v2/freight_rates/:zone_from";
    const URL_SHIPMENT_CREATE = "https://api.coolrunner.dk/v2/shipments/";

    public function sendData($endpoint, $data = array(), $store_id = false)
    {

        try {
            $email = Mage::helper('coolrunner')->getConfig('coolrunner/settings/email', $store_id);
            $token = Mage::helper('coolrunner')->getConfig('coolrunner/settings/token', $store_id);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 60000);
            curl_setopt($ch, CURLOPT_USERPWD, "$email:$token");

            $response = curl_exec($ch);

            curl_close($ch);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::log($e->getMessage(), null, "coolrunner.log", true);
        }

        if ($data = json_decode($response, true)) {
            return $data;
        }

        return array();
    }

    public function getFreightRatesUrl($zone_from)
    {
        return str_replace(":zone_from", $zone_from, self::URL_FREIGHT_RATES);
    }

    public function exportOrders($orderIds, $type, $weight, $height, $length, $width, $receiver, $droppointData)
    {
        if (!is_array($orderIds)) {
            $orderIds = array($orderIds);
        }
        $shipments = array();
        $store_id = false;
        foreach ($orderIds as $orderId) {
            $shipment = array();

            $order = Mage::getModel('sales/order')->load($orderId);
            $store_id = $order->getStoreId();


            if ($type == "auto") {
                $shipping_method = $order->getData('shipping_method');
                if (strpos($shipping_method, "coolrunner") === false) {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('coolrunner')->__("Can't create label for order: %s. Not a CoolRunner Shipping Method.", $order->getIncrementId()));
                    continue;
                }
            } else {
                $shipping_method = $type;
            }

            $billing_address = $order->getBillingAddress();
            $shipping_address = $order->getShippingAddress();
            $orderLabelData = $this->_getOrderLabelData($order);

            // CARRIER PRODUCT SERVICE
            $carrier_product_service = explode("_", str_replace("coolrunner_", "", $shipping_method));
            $carrier = count($carrier_product_service) ? array_shift($carrier_product_service) : "";
            $product = count($carrier_product_service) ? array_shift($carrier_product_service) : "";
            $service = count($carrier_product_service) ? implode("_", $carrier_product_service) : "";

            $shipment["carrier"] = $carrier;
            $shipment["carrier_product"] = $product;
            $shipment["carrier_service"] = $service;


            // DROPPOINT AND RECEIVER
            $droppoint = strpos($shipping_method, "droppoint") !== false ? true : false;
            $notify_sms = ($orderLabelData && isset($orderLabelData['telephone']) && $orderLabelData['telephone']) ? $orderLabelData['telephone'] : $shipping_address->getTelephone();

            $billing_streets = $billing_address->getStreet();
            $billing_streets = is_array($billing_streets) ? $billing_streets : array($billing_streets);

            $shipping_streets = $shipping_address->getStreet();
            $shipping_streets = is_array($shipping_streets) ? $shipping_streets : array($shipping_streets);


            // DROPPOINT ORDER WITH CUSTOMER SELECTED DROPPOINT
            if ($droppoint && $orderLabelData && $orderLabelData['droppoint']) {
                // RECEIVER
                /*
                $shipment["receiver_name"] = ($billing_address->getCompany()) ? $billing_address->getCompany() : $billing_address->getName();
                $shipment["receiver_attention"] = ($billing_address->getCompany()) ? $billing_address->getName() : "";
                $shipment["receiver_street1"] = isset($billing_streets[0]) ? $billing_streets[0] : "";
                $shipment["receiver_street2"] = isset($billing_streets[1]) ? $billing_streets[1] : "";
                $shipment["receiver_zipcode"] = $billing_address->getPostcode();
                $shipment["receiver_city"] = $billing_address->getCity();
                $shipment["receiver_country"] = $billing_address->getCountry();
                $shipment["receiver_phone"] = $billing_address->getTelephone();
                ;
                $shipment["receiver_email"] = $order->getCustomerEmail();
                $shipment["receiver_notify"] = 1;
                $shipment["receiver_notify_sms"] = $notify_sms;
                $shipment["receiver_notify_email"] = $order->getCustomerEmail();
*/
                $shipment["receiver_name"] = ($shipping_address->getCompany()) ? $shipping_address->getCompany() : $shipping_address->getName();
                $shipment["receiver_attention"] = ($shipping_address->getCompany()) ? $shipping_address->getName() : "";
                $shipment["receiver_street1"] = isset($shipping_streets[0]) ? $shipping_streets[0] : "";
                $shipment["receiver_street2"] = isset($shipping_streets[1]) ? $shipping_streets[1] : "";
                $shipment["receiver_zipcode"] = $shipping_address->getPostcode();
                $shipment["receiver_city"] = $shipping_address->getCity();
                $shipment["receiver_country"] = $shipping_address->getCountry();
                $shipment["receiver_phone"] = $shipping_address->getTelephone();
                 
                $shipment["receiver_email"] = $order->getCustomerEmail();
                $shipment["receiver_notify"] = 1;
                $shipment["receiver_notify_sms"] = $notify_sms;
                $shipment["receiver_notify_email"] = $order->getCustomerEmail();
                
                // DROPPOINT
                $shipment["droppoint"] = 1;
                $shipment["droppoint_id"] = $orderLabelData['droppoint'];
                $shipment["droppoint_name"] = $shipping_address->getCompany();
                $shipment["droppoint_street1"] = isset($shipping_streets[0]) ? $shipping_streets[0] : "";
                
                $shipment["droppoint_zipcode"] = $shipping_address->getPostcode();
                $shipment["droppoint_city"] = $shipping_address->getCity();
                $shipment["droppoint_country"] = $shipping_address->getCountry();
            }
            // DROPPOINT ORDER WITH NO DROPPOINT SELECTED
            elseif ($droppoint) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('coolrunner')->__("Can't create droppoint label for order: %s. No Droppoint found.", $order->getIncrementId()));
                continue;
            }
            // NORMAL ORDER, NO DROPPOINT INVOLVED
            else {
                // RECEIVER
                $shipment["receiver_name"] = ($shipping_address->getCompany()) ? $shipping_address->getCompany() : $shipping_address->getName();
                $shipment["receiver_attention"] = ($shipping_address->getCompany()) ? $shipping_address->getName() : "";
                $shipment["receiver_street1"] = isset($shipping_streets[0]) ? $shipping_streets[0] : "";
                $shipment["receiver_street2"] = isset($shipping_streets[1]) ? $shipping_streets[1] : "";
                $shipment["receiver_zipcode"] = $shipping_address->getPostcode();
                $shipment["receiver_city"] = $shipping_address->getCity();
                $shipment["receiver_country"] = $shipping_address->getCountry();
                $shipment["receiver_phone"] = $shipping_address->getTelephone();
                 
                $shipment["receiver_email"] = $order->getCustomerEmail();
                $shipment["receiver_notify"] = 1;
                $shipment["receiver_notify_sms"] = $notify_sms;
                $shipment["receiver_notify_email"] = $order->getCustomerEmail();

                // DROPPOINT
                $shipment["droppoint"] = 0;
                $shipment["droppoint_id"] = "";
                $shipment["droppoint_name"] = "";
                $shipment["droppoint_street1"] = "";
                 
                $shipment["droppoint_zipcode"] = "";
                $shipment["droppoint_city"] = "";
                $shipment["droppoint_country"] = "";
            }

            // BUSINESS LABEL WITHOUT COMPANY?
            if (strpos($shipping_method, "business") !== false && (!isset($shipment["receiver_attention"]) || !$shipment["receiver_attention"])) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('coolrunner')->__("Can't create business label for order: %s. No Company found.", $order->getIncrementId()));
                continue;
            }

            // INTERNATIONAL - SENDER AND RECEIVER COUNTRY THE SAME?
            // SENDER
            $shipment["sender_name"] = Mage::helper('coolrunner')->getConfig('coolrunner/sender/name', $store_id);
            $shipment["sender_attention"] = Mage::helper('coolrunner')->getConfig('coolrunner/sender/attention', $store_id);
            $shipment["sender_street1"] = Mage::helper('coolrunner')->getConfig('coolrunner/sender/street1', $store_id);
            $shipment["sender_street2"] = Mage::helper('coolrunner')->getConfig('coolrunner/sender/street2', $store_id);
            $shipment["sender_zipcode"] = Mage::helper('coolrunner')->getConfig('coolrunner/sender/zipcode', $store_id);
            $shipment["sender_city"] = Mage::helper('coolrunner')->getConfig('coolrunner/sender/city', $store_id);
            $shipment["sender_country"] = Mage::helper('coolrunner')->getConfig('coolrunner/sender/country', $store_id);
            $shipment["sender_phone"] = Mage::helper('coolrunner')->getConfig('coolrunner/sender/phone', $store_id);
            $shipment["sender_email"] = Mage::helper('coolrunner')->getConfig('coolrunner/sender/email', $store_id);

            // LABEL SIZE, REFERENCE AND FORMAT
            $shipment["length"] = $length;
            $shipment["width"] = $width;
            $shipment["height"] = $height;
            $shipment["weight"] = $weight * 1000;
            $shipment["reference"] = $order->getIncrementId();
            $shipment["label_format"] = Mage::helper('coolrunner')->getConfig('coolrunner/settings/print_size', $store_id);
            ;
/*
            // INSURANCE 
            $shipment["insurance"] = 0;
            $shipment["insurance_value"] = $order->getSubtotal();
            $shipment["insurance_currency"] = $order->getOrderCurrencyCode();

            // CUSTOMS
            $shipment["customs_value"] = $order->getSubtotal();
            $shipment["customs_currency"] = $order->getOrderCurrencyCode();
*/
            $shipments[] = $shipment;
        }

        if (count($shipments)) {
            if (count($shipments) == 1) {
                $endpoint = self::URL_SHIPMENT_CREATE;
                $data = $shipments[0];
            }

//Mage::getSingleton('adminhtml/session')->addWarning("<pre>".print_r($data,true)."</pre>");

            $response = $this->sendData($endpoint, $data, $store_id);
            if ($response['status'] == 'ok') {
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('coolrunner')->__("%s Labels Created Successfully", 1));
            } elseif ($response['status'] == 'error') {
                Mage::getSingleton('adminhtml/session')->addError($response['message']);
            }
//Mage::getSingleton('adminhtml/session')->addWarning("<pre>".print_r($response,true)."</pre>");

            return $response;
        }
        return array();
    }

    private function _getOrderLabelData($order)
    {
        $order_id = $order->getId();
        $resource = Mage::getSingleton('core/resource');
        $read = $resource->getConnection('core_read');
        $table = $resource->getTableName('coolrunner_coolshipping_sales_order_info');
        $query = "SELECT * FROM $table WHERE order_id='$order_id'";

        $results = $read->fetchAll($query);
        if (isset($results[0])) {
            return $results[0];
        }
        return false;
    }

}
