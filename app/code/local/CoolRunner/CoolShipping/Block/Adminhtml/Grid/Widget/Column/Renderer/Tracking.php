<?php

class CoolRunner_CoolShipping_Block_Adminhtml_Grid_Widget_Column_Renderer_Tracking
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    public function render(Varien_Object $row) {
        $id = $row->getId();

        $text = parent::_getValue($row);
        $base = Mage::helper('adminhtml')->getUrl('coolrunner/shipping/gettracking', array('_secure' => true));
        $title = $this->__('Show Tracking');

        if (!is_null($base) && !is_null($text) && (string)$text !== '') {
            Mage::helper('coolrunner/logger')->log('Returned Tracking column for order #', $row->getIncrementId());
            return
                "<a style='display: block; white-space: nowrap; vertical-align: middle;' href='javascript:void(0)' title='{$title}'
                    data-href='{$base}order_id/{$id}' 
                    onclick='CoolRunner.GetLabel(event)'>
                        {$text}
                </a>";
        } else {
            Mage::helper('coolrunner/logger')->log('Couldn\'t return Tracking column for order #', $row->getIncrementId());
            return null;
        }
    }
}