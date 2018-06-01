<?php

class CoolRunner_CoolShipping_Block_Shipping_Labels
    extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct() {
        $this->_blockGroup = 'coolshipping';
        $this->_controller = 'labels_shipping';
        $this->_headerText = Mage::helper('coolrunner')->__('Labels - CoolShipping');

        parent::__construct();
    }
}