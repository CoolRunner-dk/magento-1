<?php

class CoolRunner_CoolShipping_Model_Carrier_Abstract extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_prefix = 'coolrunner_';
    protected $_default_condition_name = 'package_value';
    protected $_is_tracking_available = true;
    protected $_tracking_title = '';

    public function isTrackingAvailable()
    {
        return $this->_is_tracking_available;
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function getConfigData($field) {
        if(strtolower($field) === 'title') {
            return "CoolRunner | $this->_tracking_title";
        }
        return parent::getConfigData($field);
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
        $store_id = Mage::app()->getStore()->getStoreId();
        /** @var \CoolRunnerSDK\API $api */
        $api = Mage::getModel('coolrunner/apiv3')->loadAPI($store_id);

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
        $zone_from = Mage::helper('coolrunner')->getConfig("coolrunner/sender/country");
        $cache_key = "coolrunner_{$zone_from}_{$store_id}";
        if($countries = Mage::app()->getCache()->load($cache_key)) {
            $countries = unserialize($countries);
        } else {
            $countries = $api->getProducts($zone_from);
            Mage::app()->getCache()->save(serialize($countries),$cache_key, array(Mage_Core_Model_Config::CACHE_TAG));
        }

        $carriers = array();
        foreach ($countries->getCountry($request->getData('dest_country_id')) as $ca => $prod) {
            if(!in_array(strtolower($ca), $carriers)) {
                $carriers[] = strtolower($ca);
            }
        }

        foreach ($rates as $key => $rate) {
            if (!empty($rate) && $rate['price'] >= 0) {
                if (strpos($rate['carrier_product_service'], $carrier) !== 0) {
                    continue;
                }
                if (!in_array($carrier, $carriers)) {
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
