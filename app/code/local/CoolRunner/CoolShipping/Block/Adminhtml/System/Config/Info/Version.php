<?php

class CoolRunner_CoolShipping_Block_Adminhtml_System_Config_Info_Version
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface {

    public function render(Varien_Data_Form_Element_Abstract $element) {
        $module = 'CoolRunner_CoolShipping';

        if (!Mage::getStoreConfig(strtolower($module) . '/install/date')) {
            Mage::app()->getConfig()->saveConfig(strtolower($module) . '/install/date', time())->reinit();
        }

        $moduleName = (string)Mage::getConfig()->getModuleConfig($module)->name;
        if (!$moduleName) {
            $moduleName = $module;
        }
        $moduleVersion = (string)Mage::getConfig()->getModuleConfig($module)->version;

        $cur_row = "<tr><td class='label'>Running Version</td><td class='value'><input class='input-text' type='text' disabled value='$moduleVersion'></td></tr>";
        $latest_row = ''; //"<tr><td class='label'>Latest Version</td><td class='value'><input class='input-text' type='text' disabled value='0.0.0'></td></tr>";

        return implode(' ', array($cur_row, $latest_row));
    }

}
