<?php

class CoolRunner_CoolShipping_Block_Adminhtml_Grid_Widget_Column_Renderer_ShippingAddress
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    public function render(Varien_Object $row) {
        $id = $row->getId();
        /** @var Mage_Sales_Model_Resource_Order_Address_Collection $shipping_collection */
        $shipping_collection = Mage::getModel('sales/order_address')->getCollection();
        /** @var CoolRunner_CoolShipping_Model_Tools $tools */
        $tools = Mage::getModel('coolrunner/tools');

        /** @var Mage_Sales_Model_Order_Address $address */
        $address = $shipping_collection->addFieldToFilter('parent_id', array($id))->addFieldToFilter('address_type', array('shipping'))->getFirstItem();

        $street_comps = array();
        for ($i = 1; $i <= 4; $i++) {
            $street_comps[] = $address->getStreet($i);
        }
        $street = implode(', ', array_filter($street_comps));

        $country = $tools->isoToCountry($address->getCountryId());

        Mage::helper('coolrunner/logger')->log('Returned Tracking column for order #', $row->getIncrementId());
        return "{$street}, {$address->getPostcode()} {$address->getCity()}," . ($address->getRegion() ? " {$address->getRegion()}," : '') . " {$country}";
    }
}