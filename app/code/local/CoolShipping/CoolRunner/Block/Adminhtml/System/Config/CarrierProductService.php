<?php
class CoolShipping_Coolrunner_Block_Adminhtml_System_Config_CarrierProductService extends Mage_Core_Block_Html_Select
{
    private function _getOptions()
    {
	    if($zone_from = Mage::helper('coolrunner')->getConfig("coolrunner/sender/country")) {
		    $api = Mage::getModel('coolrunner/api');
		    $endpoint = $api->getFreightRatesUrl($zone_from);
		    $options = $api->sendData($endpoint,array(),Mage_Core_Model_App::ADMIN_STORE_ID);

			if(isset($options['status']) && $options['status'] == 'error') {
				if(isset($options['message']) && $options['message'] != '') {
					Mage::getSingleton('adminhtml/session')->addError($options['message']);
				}
			} elseif(isset($options['status']) && $options['status'] == 'ok') {
				Mage::getModel('core/config')->saveConfig('coolrunner/pricelist/list',serialize($options['result']));
				Mage::getConfig()->reinit();
				Mage::app()->reinitStores();
			}
	    }
	    
		$options = Mage::helper('coolrunner')->getCarrierProductServiceOptions($withLabels = false);
	   
    	return $options;
    }
    
    public function setInputName($value)
    {
        return $this->setName($value);
    }
	
    public function _toHtml()
    {
    	if (!$this->getOptions()) {
    		foreach ($this->_getOptions() as $key => $label) {
                $this->addOption($key, $label);
            }
        }
        
        return parent::_toHtml();
    }
}
