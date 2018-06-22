<?php

class CoolRunner_CoolShipping_Helper_Information
    extends Mage_Core_Helper_Abstract {

    /**
     * Check if module is active
     *
     * @return bool
     */
    public function isActive() {
        Mage::helper('coolrunner/logger')->log('Checking module active state');
        return !!Mage::getStoreConfig('coolrunner/settings/active');
    }

    /**
     * Get template for CoolRunner
     *
     * @param string $type     Type of template | admin / front
     * @param string $template Name of the template file relative to the base coolrunner dir in app/design
     *
     * @return string
     */
    public function getTemplate($type = 'admin', $template) {
        switch (strtolower($type)) {
            case 'admin':
                $type = 'adminhtml/default/default/template/coolshipping';
                break;
            case 'front':
                $type = 'frontend/base/default/template/coolshipping';
                break;
        }
        $base = Mage::getBaseDir();

        Mage::helper('coolrunner/logger')->log('Fetching template file', $type, $template, "{$base}/app/design/$type/{$template}.phtml");
        return "{$base}/app/design/$type/{$template}.phtml";
    }

    /**
     * Get PDFs
     *
     * @return CoolRunner_CoolShipping_Model_Order_Pdf[]|CoolRunner_CoolShipping_Model_Resource_Order_Pdf_Collection
     */
    public function getOrderPdfCollection() {
        $label = Mage::getModel('coolrunner/order_pdf');
        return $label->getCollection();
    }

    /**
     * @param $order_id
     *
     * @return CoolRunner_CoolShipping_Model_Order_Pdf
     */
    public function getOrderPdf($order_id) {
        return $this->getOrderPdfCollection()->addFieldToFilter('order_id', array($order_id))->getFirstItem();
    }

    /**
     * @return CoolRunner_CoolShipping_Model_Order_Info[]|CoolRunner_CoolShipping_Model_Resource_Order_Info_Collection
     */
    public function getOrderInfoCollection() {
        $info = Mage::getModel('coolrunner/order_info');
        return $info->getCollection();
    }

    /**
     * @param $order_id
     *
     * @return CoolRunner_CoolShipping_Model_Order_Info
     */
    public function getOrderInfo($order_id) {
        return $this->getOrderInfoCollection()->addFieldToFilter('order_id', array($order_id))->getFirstItem();
    }

    /**
     * Get order information
     *
     * @param null $order_id
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrderByEntityId($order_id) {
        return Mage::getModel('sales/order')->load($order_id);
    }

    /**
     * @param $order_id
     *
     * @return bool|Mage_Sales_Model_Order_Shipment
     */
    public function getShipmentByOrderEntityId($order_id) {
        $order = $this->getOrderByEntityId($order_id);
        $col = $order->getShipmentsCollection();

        if (count($col) !== 0) {
            return $col->getFirstItem();
        }

        return false;
    }

    /**
     * Get order items
     *
     * @param int $order_id
     *
     * @return Mage_Sales_Model_Order_Item[]
     */
    public function getOrderItems($order_id) {
        $collection = $this->getOrderByEntityId($order_id)->getItemsCollection();
        $ret = array();
        foreach ($collection as $key => $item) {
            if (floatval($item->getBasePrice()) != 0) {
                $ret[$key] = $item;
            }
        }
        return $ret;
    }

    /**
     * Check if order exists
     *
     * @param int $order_id
     *
     * @return bool
     */
    public function orderExists($order_id) {
        return !empty($this->getOrderByEntityId($order_id)->getData());
    }

    /**
     * Get store sender information
     *
     * @return array|string[]
     */
    public function getSenderInformation() {
        $sender = array(
            'name'      => 'name',
            'attention' => 'attention',
            'street1'   => 'street1',
            'street2'   => 'street2',
            'zip_code'  => 'zipcode',
            'city'      => 'city',
            'country'   => 'country',
            'phone'     => 'phone',
            'email'     => 'email'
        );

        foreach ($sender as $prop => $key) {
            $sender[$prop] = Mage::getStoreConfig("coolrunner/sender/$key");
        }

        return $sender;
    }

    /**
     * @param $order_id
     *
     * @return CoolRunner_CoolShipping_Model_Order_Info|false
     */
    public function getReceiverInformation($order_id) {
        $order_info = $this->getOrderInfoCollection()->addFieldToFilter('order_id', array($order_id));
        if ($order_info->count() !== 0) {
            $order_info = $order_info->getFirstItem();
            return $order_info;
        }

        return false;
    }
}