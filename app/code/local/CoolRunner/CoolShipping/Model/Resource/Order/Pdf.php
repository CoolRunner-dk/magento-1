<?php

class CoolRunner_CoolShipping_Model_Resource_Order_Pdf
    extends Mage_Core_Model_Resource_Db_Abstract {
    public function _construct() {
        $this->_init('coolrunner/order_pdf', 'id');
    }
}