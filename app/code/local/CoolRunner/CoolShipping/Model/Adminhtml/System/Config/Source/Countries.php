<?php

class CoolRunner_CoolShipping_Model_Adminhtml_System_Config_Source_Countries {
    public function toOptionArray() {
        $c = new CoolRunner_CoolShipping_Block_Adminhtml_System_Config_Countries();
        return $c->getOptionsList();
    }
}