<?php

class CoolRunner_CoolShipping_Model_Resource_Order_Info_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract {
    protected function _construct() {
        $this->_init('coolrunner/order_info');
    }
}