<?php

class CoolRunner_CoolShipping_Block_Adminhtml_Sales_Order_Grid
    extends Mage_Adminhtml_Block_Sales_Order_Grid {
    protected function _prepareColumns() {
        parent::_prepareColumns();
        Mage::helper('coolrunner/logger')->log('Adding Shipping column to Sales_Order_Grid');

        if (Mage::getStoreConfig('coolrunner/settings/active')) {
            $this->addColumn('cool_shipping',
                             array(
                                 'header'      => 'Shipping',
                                 'width'       => '50px',
                                 'renderer'    => 'CoolRunner_CoolShipping_Block_Adminhtml_Grid_Widget_Column_Renderer_Shipping',
                                 'filter'      => false,
                                 'sortable'    => false,
                                 'index'       => 'stores',
                                 'is_system'   => true,
                                 'data-column' => 'cool_shipping',
                             ));

            $this->addColumnsOrder('cool_shipping', 'action');
        }
        Mage::helper('coolrunner/logger')->log('Added Shipping column to Sales_Order_Grid');

        return $this;
    }
}
