<?php

class CoolRunner_CoolShipping_Block_Adminhtml_System_Config_CarrierProductService
    extends Mage_Core_Block_Html_Select {
//    private function _getOptions() {
//        if ($zone_from = Mage::helper('coolrunner')->getConfig("coolrunner/sender/country")) {
//            $api = Mage::getModel('coolrunner/api');
//            $endpoint = $api->getFreightRatesUrl($zone_from);
//            $options = $api->sendData($endpoint, array(), Mage_Core_Model_App::ADMIN_STORE_ID);
//
//            /** @var \CoolRunnerSDK\API $apiv3 */
//            $apiv3 = Mage::getModel('coolrunner/apiv3')->loadAPI(Mage_Core_Model_App::ADMIN_STORE_ID);
//
//            if (isset($options['status']) && $options['status'] == 'error') {
//                if (isset($options['message']) && $options['message'] != '') {
//                    Mage::getSingleton('adminhtml/session')->addError($options['message']);
//                }
//            } else if (isset($options['status']) && $options['status'] == 'ok') {
//                /** @var CoolRunner_CoolShipping_Helper_Database $database */
//                $database = Mage::helper('coolrunner/database');
//                $database->set('pricelist', $options['result']);
//                Mage::getModel('core/config')->saveConfig('coolrunner/pricelist/list', serialize($options['result']));
//                Mage::getConfig()->reinit();
//                Mage::app()->reinitStores();
//            }
//        }
//
//        $options = Mage::helper('coolrunner')->getCarrierProductServiceOptions($withLabels = false);
//
//        return $options;
//    }


    private function _getOptions() {
        /** @var CoolRunner_CoolShipping_Helper_Data $data */
        $data = Mage::helper('coolrunner');
        if ($zone_from = $data->getConfig("coolrunner/sender/country")) {

            /** @var CoolRunner_CoolShipping_Helper_Database $database */
            $database = Mage::helper('coolrunner/database');
            /** @var \CoolRunnerSDK\API $apiv3 */
            $apiv3 = Mage::getModel('coolrunner/apiv3')->loadAPI(Mage_Core_Model_App::ADMIN_STORE_ID);
            $options = $apiv3->getProducts($zone_from);

            /** @var Mage_Adminhtml_Model_Session $session */
            $session = Mage::getSingleton('adminhtml/session');
            if ($resp = $apiv3->getLastResponse()) {
                if ($resp->isOk()) {
                    $database->set('pricelist', $options);
                } else if ($resp->isUnauthorized()) {
                    $session->addWarning('The email and/or token used is incorrect');
                } else if ($resp->isForbidden()) {
                    $session->addWarning('The email and/or token doesn\'t allow access to the resource');
                } else if ($resp->isInternalError() || $resp->isServiceUnavailable()) {
                    $session->addWarning('CoolRunner can\'t process the request right now - Try again later');
                }
            } else {
                $session->addError('CoolRunner encountered an error - Please contact our support for assistance');
            }
        }
        $options = $data->getCarrierProductServiceOptions();

        return $options;
    }

    public function setInputName($value) {
        return $this->setName($value);
    }

    public function _toHtml() {
        if (!$this->getOptions()) {
            $this->addOption('', 'Pick service', array('style' => 'display: none; opacity: 0'));
            foreach ($this->_getOptions() as $key => $value) {
                $this->addOption($key, $value['label'], array('countries' => implode(' ', $value['countries'])));
            }
        }

        Mage::helper('coolrunner/logger')->log('Rendered CarrierProductService config');
        return parent::_toHtml();
    }
}
