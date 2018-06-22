<?php

class CoolRunner_CoolShipping_Helper_Droppoints
    extends Mage_Core_Helper_Abstract {
    public function getDroppointsIds($code) {
        Mage::helper('coolrunner/logger')->log('Fetching Droppoints', $code);
        $carrier = str_replace("coolrunner_", "", $code);
        $ids = array();

        $rates = unserialize(Mage::getStoreConfig('coolrunner/rates/carrier_options'));
        foreach ($rates as $key => $rate) {
            if (strpos($rate['carrier_product_service'], $carrier) === 0 && strpos($rate['carrier_product_service'], "droppoint") !== false) {
                $ids[] = str_replace($carrier . "_", "", $rate['carrier_product_service']);
            }
        }

        return $ids;
    }

    public function getDroppoint($droppoint) {
        return $droppoint;
    }

}