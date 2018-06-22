<?php
class CoolRunner_CoolShipping_Block_Adminhtml_System_Config_Conditions extends Mage_Core_Block_Html_Select
{
    private function _getOptions()
    {
	    $options = array(
		    'package_value' => Mage::helper('coolrunner')->__("Subtotal"),
		    'package_value_with_discount' => Mage::helper('coolrunner')->__("Subtotal after discount"),
		    'package_weight' => Mage::helper('coolrunner')->__("Order Weight"),
		    'package_qty' => Mage::helper('coolrunner')->__("Order Qty"),
		    
	    );
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
        Mage::helper('coolrunner/logger')->log('Rendered Conditions config');
        
        return parent::_toHtml();
    }
}
