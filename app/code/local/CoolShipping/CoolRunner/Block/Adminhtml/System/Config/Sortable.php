<?php
class CoolShipping_Coolrunner_Block_Adminhtml_System_Config_Sortable extends Mage_Core_Block_Template
{
    
    public function _toHtml()
    {
    	return '<img src="'.Mage::getDesign()->getSkinUrl('coolrunner/images/arrow_up_down-128.png').'" class="sortable" style="width:20px; margin-left: 2px;">';
    }
}
