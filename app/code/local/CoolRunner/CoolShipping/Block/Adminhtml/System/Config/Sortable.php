<?php

class CoolRunner_CoolShipping_Block_Adminhtml_System_Config_Sortable
    extends Mage_Core_Block_Template {

    public function _toHtml() {
        return '<img draggable="false" src="' . Mage::getDesign()->getSkinUrl('coolshipping/images/arrow_up_down-128.png') . '" class="coolrunner-sortable sortable" style="width:20px; margin-left: 2px;">';
    }
}
