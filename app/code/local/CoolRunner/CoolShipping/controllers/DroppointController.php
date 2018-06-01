<?php

class CoolRunner_CoolShipping_DroppointController
    extends Mage_Core_Controller_Front_Action {
    public function getDroppointsFromPostalCodeAction() {
        $this->getResponse()->setHeader('Content-type', 'text/json; charset=UTF-8');
        $params = $this->getRequest()->getParams();

        $store_id = Mage::app()->getStore()->getStoreId();

        /** @var \CoolRunnerSDK\API $api */
        $api = Mage::getModel('coolrunner/apiv3')->loadAPI($store_id);

        $postal_code = isset($params['zip-code']) ? $params['zip-code'] : false;
        $country_code = isset($params['country-code']) ? $params['country-code'] : false;
        $carrier = isset($params['carrier']) ? $params['carrier'] : false;


        if ($postal_code && $country_code && $carrier) {
            /** @var Mage_Sales_Model_Quote $quote */
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $carrier = str_replace('coolrunner_', '', $carrier);
            if ($quote->getBillingAddress()->getPostcode() === $postal_code) {
                $street = $quote->getBillingAddress()->getStreet();
                $street = array_shift($street);
                $city = $quote->getBillingAddress()->getCity();

                $data = $api->findServicepoints($carrier, $country_code, $postal_code, '', $street);
            } elseif($quote->getShippingAddress()->getPostcode() === $postal_code) {
                $street = $quote->getShippingAddress()->getStreet();
                $street = array_shift($street);
                $city = $quote->getShippingAddress()->getCity();

                $data = $api->findServicepoints($carrier, $country_code, $postal_code, '', $street);
            } else {
                $data = $api->findServicepoints($carrier, $country_code, $postal_code);
            }

            $servicepoints = array();

            foreach ($data as $servicepoint) {
                $sp = $servicepoint->toArray();
                $sp['carrier'] = $carrier;
                $servicepoints[] = $sp;
            }

            $this->getResponse()->setBody(json_encode(array('status' => 'ok', 'message' => '', 'result' => $servicepoints),JSON_PRETTY_PRINT));
        } else {
            $result = json_encode($params);
            $this->getResponse()->setBody($result);
        }
    }
}