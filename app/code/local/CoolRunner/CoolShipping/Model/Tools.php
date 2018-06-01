<?php

class CoolRunner_CoolShipping_Model_Tools
    extends Mage_Core_Model_Abstract {

    /**
     * Convert ISO 3611-1 Alpha-2 to the fully qualified name
     *
     * @param $iso
     *
     * @return bool|mixed
     */
    public function isoToCountry($iso) {
        $list = $this->getCountryList(false);
        return isset($list[strtoupper($iso)]) ? $list[strtoupper($iso)] : $iso;
    }

    public function getCountryList($filter_allowed = true) {
        if ($filter_allowed) {
            $countries = Mage::getModel('directory/country')->getResourceCollection()->loadByStore()->toOptionArray();
        } else {
            $countries = Mage::getModel('directory/country')->getResourceCollection()->toOptionArray();
        }

        $ret = array();
        foreach ($countries as $country) {
            if ($country['value'] || $country['label']) {
                $ret[$country['value']] = $country['label'];
            }
        }

        return $ret;
    }

    public static function isActive() {
        return !!Mage::getStoreConfig('coolrunner/settings/active');
    }
}