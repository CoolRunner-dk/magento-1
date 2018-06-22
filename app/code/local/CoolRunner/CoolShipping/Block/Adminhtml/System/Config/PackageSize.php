<?php

class CoolRunner_CoolShipping_Block_Adminhtml_System_Config_PackageSize
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {
    protected $_block;

    protected function _toHtml() {
        Mage::helper('coolrunner/logger')->log('Rendered PackageSize config');
        return parent::_toHtml();
    }

    protected function _getBlockToRenderer($type) {
        if (!$this->{"_$type"}) {
            $this->{"_$type"} = $this->getLayout()->createBlock(
                "coolrunner/adminhtml_system_config_$type", '',
                array('is_render_to_js_template' => true)
            );
            $this->{"_$type"}->setExtraParams('style="width:75px"');
        }
        return $this->{"_$type"};
    }

    protected function _prepareToRender() {
        $this->addColumn('title', array(
            'label'    => Mage::helper('coolrunner')->__('Your Title'),
            'renderer' => 'text',
            'class'    => 'title',
        ));
        $this->addColumn('weight', array(
            'label'    => Mage::helper('coolrunner')->__('Weight up to (kg)'),
            'renderer' => 'text',
            'class'    => 'weight',
        ));
        $this->addColumn('length', array(
            'label'    => Mage::helper('coolrunner')->__('Package Length'),
            'renderer' => 'text',
            'class'    => 'length',
        ));
        $this->addColumn('width', array(
            'label'    => Mage::helper('coolrunner')->__('Package Width'),
            'renderer' => 'text',
            'class'    => 'width',
        ));
        $this->addColumn('height', array(
            'label'    => Mage::helper('coolrunner')->__('Package Height'),
            'renderer' => 'text',
            'class'    => 'height',
        ));
        $this->addColumn('sortable', array(
            'label'    => Mage::helper('coolrunner')->__('Move'),
            'renderer' => $this->_getBlockToRenderer('sortable'),
            'style'    => 'width:100px',
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('coolrunner')->__('Add');
    }
}
