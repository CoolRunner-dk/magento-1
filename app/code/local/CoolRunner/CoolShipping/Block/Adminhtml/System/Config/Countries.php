<?php

class CoolRunner_CoolShipping_Block_Adminhtml_System_Config_Countries
    extends Mage_Core_Block_Html_Select {
    private function _getOptions() {
        /** @var \CoolRunnerSDK\API $api */
        $api = Mage::getModel('coolrunner/apiv3')->loadAPI(Mage_Core_Model_App::ADMIN_STORE_ID);

        /** @var CoolRunner_CoolShipping_Helper_Database */
        $dbhelper = Mage::helper('coolrunner/database');

        /** @var CoolRunner_CoolShipping_Helper_Data $helper */
        $helper = Mage::helper('coolrunner');

        $options = array();

        $pricelist = $dbhelper->get('pricelist');
        if (!$pricelist) {
            $cc = $helper->getConfig('coolrunner/sender/country', Mage_Core_Model_App::ADMIN_STORE_ID);
            if (!$cc) {
                $cc = 'dk';
            }
            $pricelist = $api->getProducts($cc);
        }

        foreach ($pricelist as $zone_to => $rates) {
            $options[$zone_to] = Mage::getModel('coolrunner/tools')->isoToCountry($zone_to);
        }

        asort($options);

        Mage::helper('coolrunner/logger')->log(sprintf('Returned Countries config options -> %s', count($options)));
        return $options;
    }

    public function getOptionsList() {
        return $this->_getOptions();
    }

    public function setInputName($value) {
        return $this->setName($value);
    }

    public function _toHtml() {
        $this->addOption('', 'Pick country', array('style' => 'display: none; opacity: 0'));
        foreach ($this->_getOptions() as $key => $label) {
            $this->addOption($key, $label);
        }
        Mage::helper('coolrunner/logger')->log('Rendered Countries config');

        return parent::_toHtml();
    }
}
