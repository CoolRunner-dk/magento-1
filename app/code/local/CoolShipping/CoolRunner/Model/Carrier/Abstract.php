<?php

class CoolShipping_CoolRunner_Model_Carrier_Abstract extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_prefix = 'coolrunner_';
    protected $_default_condition_name = 'package_value';
    protected $_is_tracking_available = true;

    public function isTrackingAvailable()
    {
        return $this->_is_tracking_available;
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function getAllowedMethods()
    {

        $methods = array();
        $carrier = str_replace($this->_prefix, "", $this->_code);
        $rates = unserialize(Mage::getStoreConfig('coolrunner/rates/carrier_options'));
        foreach ($rates as $key => $rate) {
            if (strpos($rate['carrier_product_service'], $carrier) === 0) {
                if (!isset($methods[$this->_prefix . $rate['carrier_product_service']])) {
                    $methods[$this->_prefix . $rate['carrier_product_service']] = $rate['title'];
                }
            }
        }

        return $methods;
    }

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {

        if (!Mage::getStoreConfig('coolrunner/settings/active')) {
            return false;
        }

        $result = Mage::getModel('shipping/rate_result');

        $freeShipping = false;

        if ($request->getFreeShipping() === true || $request->getPackageQty() == $this->getFreeBoxes()) {
            $freeShipping = true;
        }

        $carrier = str_replace($this->_prefix, "", $this->_code);

        $rates = unserialize(Mage::getStoreConfig('coolrunner/rates/carrier_options'));

        $carrier_product_services_added = array();

        $api = Mage::getModel('coolrunner/api');
        $zone_from = Mage::helper('coolrunner')->getConfig("coolrunner/sender/country");
        $endpoint = $api->getFreightRatesUrl($zone_from);
        $dt = $api->sendData($endpoint, array(), Mage::app()->getStore()->getStoreId());
  //      echo "<pre>";
//print_r($dt);
//echo "</pre>";
        foreach ($dt['result'][$request->getData('dest_country_id')] as $v) {
            $cr[] = $v['carrier'];
        }

        foreach ($rates as $key => $rate) {
            if (!empty($rate) && $rate['price'] >= 0) {

                if (strpos($rate['carrier_product_service'], $carrier) !== 0) {
                    continue;
                }
                if (!in_array($carrier, $cr)) {
                    continue;
                }

                $countries = explode(",", $rate['countries']);

                if (in_array($request->getData('dest_country_id'), $countries)) {
                    if ($rate['condition_from'] <= $request->getData($rate['condition']) && $request->getData($rate['condition']) <= $rate['condition_to']) {
                        if (isset($carrier_product_services_added[$rate['carrier_product_service']])) {
                            continue;
                        } else {
                            $carrier_product_services_added[$rate['carrier_product_service']] = true;
                        }
                        /*
                          if($freeShipping) {
                          $shippingPrice = $this->getFinalPriceWithHandlingFee('0.00');
                          } else {
                          $shippingPrice = $this->getFinalPriceWithHandlingFee($rate['price']);
                          }
                         */
                        $shippingPrice = $this->getFinalPriceWithHandlingFee($rate['price']);

                        $carrier_product_service = explode("_", $rate['carrier_product_service']);
                        $carrier = array_shift($carrier_product_service);
                        $product = count($carrier_product_service) ? array_shift($carrier_product_service) : "";
                        $service = count($carrier_product_service) ? array_shift($carrier_product_service) : "";

                        $method = Mage::getModel('shipping/rate_result_method');
                        $method->setCarrier($this->_code);
                        $method->setCarrierTitle($this->getConfigData('title'));
                        $method->setMethod(str_replace($carrier . "_", "", $rate['carrier_product_service']));
                        $method->setMethodTitle($rate['title']);
                        $method->setCost(0);
                        $method->setPrice($shippingPrice);

                        //Mage::log($carrier, null, 'system.log', true);
                        $result->append($method);
                    }
                }
            }
        }

        return $result;
    }

}
