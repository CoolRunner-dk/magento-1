<?php

class CoolRunner_CoolShipping_Model_Adminhtml_System_Config_Source_CacheType extends Mage_Core_Block_Html_Select{
    public function toOptionArray() {
        return array(
            0 => 'None',
            1 => 'Disk',
            2 => 'Database'
        );
    }
}