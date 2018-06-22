<?php

/**
 * Class CoolRunner_CoolShipping_Model_Order_Info
 *
 * @property int    $quote_id
 * @property int    $order_id
 * @property string $carrier
 * @property int    $servicepoint
 * @property string $firstname
 * @property string $lastname
 * @property string $telephone
 *
 * @method int getQuoteId
 * @method int getOrderId
 * @method string getCarrier
 * @method int getServicepoint
 * @method string getFirstname
 * @method string getLastname
 * @method string getTelephone
 */
class CoolRunner_CoolShipping_Model_Order_Info extends Mage_Core_Model_Abstract
{
    public function _construct() {
        $this->_init('coolrunner/order_info');
    }

    /**
     * @param $order_id
     *
     * @return mixed|self
     */
    public function infoExists($order_id) {
        Mage::helper('coolrunner/logger')->log('Checking order information existence', $order_id);
        return !empty($this->getCollection()->addFieldToFilter('order_id', array($order_id))->getFirstItem()->_data);
    }

    public function getName() {
        return $this->getFirstname() . ' ' . $this->getLastname();
    }
}