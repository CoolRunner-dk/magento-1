<?php

class CoolRunner_CoolShipping_Block_Adminhtml_System_Config_CarrierRates
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {
    protected $_block;

    protected function _toHtml() {
        Mage::helper('coolrunner/logger')->log('Rendered CarrierRates config');
        return parent::_toHtml();
    }

    protected function _getBlockToRenderer($type) {
        if (!$this->{"_$type"}) {
            $this->{"_$type"} = $this->getLayout()->createBlock(
                "coolrunner/adminhtml_system_config_$type", '',
                array('is_render_to_js_template' => true)
            );
            $this->{"_$type"}->setClass(strtolower($type));
        }
        return $this->{"_$type"};
    }

    protected function _prepareToRender() {
        $this->addColumn('service', array(
            'label'    => Mage::helper('coolrunner')->__('Carrier Product Service'),
            'renderer' => $this->_getBlockToRenderer('carrierProductService'),
            'class'    => 'carrier-product-service',
        ));
        $this->addColumn('countries', array(
            'label'    => Mage::helper('coolrunner')->__('Country'),
            'renderer' => $this->_getBlockToRenderer('countries'),
            'class'    => 'countries',
        ));
        $this->addColumn('condition', array(
            'label'    => Mage::helper('coolrunner')->__('Condition'),
            'renderer' => $this->_getBlockToRenderer('conditions'),
            'class'    => 'condition',
        ));
        $this->addColumn('condition_from', array(
            'label'    => Mage::helper('coolrunner')->__('Condition from (incl.)'),
            'renderer' => 'text',
            'class'    => 'price',
        ));
        $this->addColumn('condition_to', array(
            'label'    => Mage::helper('coolrunner')->__('Condition to (excl.)'),
            'renderer' => 'text',
            'class'    => 'price',
        ));
        $this->addColumn('title', array(
            'label'    => Mage::helper('coolrunner')->__('Your Title'),
            'renderer' => 'text',
            'class'    => 'title',
        ));
        $this->addColumn('price', array(
            'label'    => Mage::helper('coolrunner')->__('Customer Price'),
            'renderer' => 'price',
            'class'    => 'price',
        ));
        $this->addColumn('sortable', array(
            'label'    => Mage::helper('coolrunner')->__('Move'),
            'renderer' => $this->_getBlockToRenderer('sortable'),
            'style'    => 'width:100px',
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('coolrunner')->__('Add');
    }

    protected function _prepareArrayRow(Varien_Object $row) {
        $row->setData(
            'option_extra_attr_' . $this->_getBlockToRenderer('carrierProductService')->calcOptionHash($row->getData('carrier_product_service')),
            'selected="selected"'
        );

        $row->setData(
            'option_extra_attr_' . $this->_getBlockToRenderer('countries')->calcOptionHash($row->getData('countries')),
            'selected="selected"'
        );

        $row->setData(
            'option_extra_attr_' . $this->_getBlockToRenderer('conditions')->calcOptionHash($row->getData('condition')),
            'selected="selected"'
        );

    }
}