<?php

class CoolShipping_Coolrunner_Block_Adminhtml_System_Config_Info extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $module = 'CoolShipping_CoolRunner';

        if (!Mage::getStoreConfig(strtolower($module) . '/install/date')) {
            Mage::app()->getConfig()->saveConfig(strtolower($module) . '/install/date', time())->reinit();
        }

        $moduleName = (string) Mage::getConfig()->getModuleConfig($module)->name;
        if (!$moduleName) {
            $moduleName = $module;
        }
        $moduleVersion = (string) Mage::getConfig()->getModuleConfig($module)->version;

        return '';
    }

}
