<?php

class CoolRunner_CoolShipping_Block_Adminhtml_Grid_Widget_Column_Renderer_Shipping
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    public function render(Varien_Object $row) {
        $id = $row->getId();
        /** @var CoolRunner_CoolShipping_Model_Order_Pdf $pdf */
        $pdf = Mage::getModel('coolrunner/order_pdf');
        /** @var CoolRunner_CoolShipping_Model_Order_Info $info */
        $info = Mage::getModel('coolrunner/order_info');

        $base = $img = $text = null;

        $dl = 1;

        $dl = intval($dl);

        $info_exists = $info->infoExists($id);
        $label_exists = $pdf->labelExists($id);

        if ($info_exists) {
            $base = Mage::helper('adminhtml')->getUrl('coolrunner/shipping/createlabel', array('_secure' => true));
            $img = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN) . 'adminhtml/default/default/coolshipping/images/coolrunner-create.png';
            $text = Mage::helper('coolrunner')->__('Create');

            $event = "onclick='CoolRunner.GetLabel(event)'";
        }

        if ($label_exists) {
            $base = Mage::helper('adminhtml')->getUrl('coolrunner/shipping/getlabel', array('_secure' => true));
            $img = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN) . 'adminhtml/default/default/coolshipping/images/coolrunner-download.png';
            $text = Mage::helper('coolrunner')->__('Download');

            $dl = $dl === 1 ? 'download/1/' : '';

            $event = $dl === 1 ? '' : "onclick='CoolRunner.GetLabel(event)'";
        }

        /** @var CoolRunner_CoolShipping_Helper_Information $helper */
        $helper = Mage::helper('coolrunner/information');

        $order = $helper->getOrderByEntityId($id);

        if (!is_null($base) && !is_null($img) && !is_null($text) && $order && !$order->isCanceled()) {
            Mage::helper('coolrunner/logger')->log('Returned Shipping column for order #', $row->getIncrementId());
            return
                "<a style='display: block; white-space: nowrap; vertical-align: middle;' href='javascript:void(0)' 
                    data-href='{$base}order_id/{$id}/$dl' 
                    $event>
                        <img style='height: 20px; vertical-align: middle;' src='{$img}' $event > 
                        {$text}
                </a>";
        } else {
            Mage::helper('coolrunner/logger')->log('Could\'t return Shipping column for order #', $row->getIncrementId());
            return null;
        }
    }
}