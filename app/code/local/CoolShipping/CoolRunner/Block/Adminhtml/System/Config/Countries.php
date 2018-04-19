<?php
class CoolShipping_Coolrunner_Block_Adminhtml_System_Config_Countries extends Mage_Core_Block_Html_Select
{
    private function _getOptions()
    {
	    $options = array();
	    
	    if($pricelist = Mage::helper('coolrunner')->getConfig('coolrunner/pricelist/list')) 
	    {
			$pricelist = unserialize($pricelist);
			foreach($pricelist as $zone_to => $rates) {
				$options[$zone_to] = $zone_to;
			}
	    }
	    
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
