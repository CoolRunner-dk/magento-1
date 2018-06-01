<?php

class CoolRunner_CoolShipping_Block_Shipping_Labels_Grid
    extends Mage_Adminhtml_Block_Widget_Grid {

    public function getGridUrl() {
        return Mage::getUrl('*/*/grid', array('_current' => true));
    }

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        $this->setId('coolrunner_label_grid');
        $this->setDefaultSort('label_created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * Retrieve collection class
     *
     * @return string
     */
    protected function _getCollectionClass() {
        return 'sales/order_grid_collection';
    }

    protected function _prepareCollection() {
        /** @var Mage_Sales_Model_Resource_Order_Grid_Collection $collection */
        $collection = Mage::getResourceModel($this->_getCollectionClass());

        $collection
            ->join(array('a' => 'coolrunner/order_info'),
                   'main_table.entity_id = a.order_id', array(
                       'pickup_firstname' => 'firstname',
                       'pickup_lastname'  => 'lastname',
                       'pickup_telephone' => 'telephone',
                       'servicepoint'     => 'servicepoint'
                   ))
            ->join(array('c' => 'sales/order_address'),
                   'main_table.entity_id = c.parent_id AND c.address_type != \'billing\'', array(
                       'ship_firstname'  => 'firstname',
                       'ship_middlename' => 'middlename',
                       'ship_lastname'   => 'lastname',
                       'ship_company'    => 'company',
                       'ship_street'     => 'street',
                       'ship_postcode'   => 'postcode',
                       'ship_city'       => 'city',
                       'ship_region'     => 'region',
                       'ship_country'    => 'country_id'
                   ));

        /** @var CoolRunner_CoolShipping_Model_Order_Pdf $pdf */
        $pdf = Mage::getModel('coolrunner/order_pdf');
        foreach ($collection as &$entry) {
            $entry->shipping_address = sprintf('%s-%s %s', $entry->ship_country, $entry->ship_postcode, $entry->ship_city);

            if ($entry->servicepoint) {
                $entry->shipping_name = "$entry->pickup_firstname $entry->pickup_lastname";
            }
            /** CoolRunner_CoolShipping_Model_Order_Pdf */
            $order_pdf = $pdf->getCollection()->addFieldToFilter('order_id', array($entry->entity_id))->getFirstItem();
            foreach (array(
                         'package_number'   => 'package_number',
                         'price_excl_tax'   => 'excl_tax',
                         'price_incl_tax'   => 'incl_tax',
                         'label_created_at' => 'created_at'
                     ) as $newkey => $oldkey) {
                $entry->{$newkey} = $order_pdf->{$oldkey} ? $order_pdf->{$oldkey} : false;
            }
        }

        $this->setCollection($collection);
        return parent::_prepareColumns();
    }

    protected function _prepareColumns() {
        $this->removeColumn('created_at');

        $this->addColumn('real_order_id', array(
            'header'   => Mage::helper('sales')->__('Order #'),
            'width'    => '80px',
            'type'     => 'text',
            'index'    => 'increment_id',
            'sortable' => false,
            'filter'   => false
        ));

        $this->addColumn('customer*_id', array(
            'header'   => Mage::helper('coolrunner')->__('Customer ID'),
            'width'    => '1px',
            'index'    => 'customer_id',
            'type'     => 'number',
            'sortable' => false,
            'filter'   => false
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'          => Mage::helper('sales')->__('Purchased From (Store)'),
                'index'           => 'store_id',
                'type'            => 'store',
                'store_view'      => true,
                'display_deleted' => true,
                'escape'          => true,
                'sortable'        => false,
                'filter'   => false
            ));
        }

        $this->addColumn('excl_tax', array(
            'header'        => Mage::helper('sales')->__('Price excl. tax'),
            'index'         => 'price_excl_tax',
            'type'          => 'currency',
            'currency_code' => 'DKK',
            'filter'        => false,
            'sortable'      => false,
            'filter'   => false
        ));

        $this->addColumn('incl_tax', array(
            'header'        => Mage::helper('sales')->__('Price incl. tax'),
            'index'         => 'price_incl_tax',
            'type'          => 'currency',
            'width'         => '1px',
            'currency_code' => 'DKK',
            'filter'        => false,
            'sortable'      => false,
            'filter'   => false
        ));

        $this->addColumn('billing_name', array(
            'header'   => Mage::helper('sales')->__('Bill to Name'),
            'index'    => 'billing_name',
            'width'    => '10px',
            'sortable' => false,
            'filter'   => false
        ));

        $this->addColumn('shipping_name', array(
            'header'   => Mage::helper('sales')->__('Ship to Name'),
            'index'    => 'shipping_name',
            'width'    => '10px',
            'sortable' => false,
            'filter'   => false
        ));

        $this->addColumn('shipping_address', array(
            'header'   => Mage::helper('sales')->__('Ship to Address'),
            'index'    => 'shipping_address',
            'renderer' => 'CoolRunner_CoolShipping_Block_Adminhtml_Grid_Widget_Column_Renderer_ShippingAddress',
            'sortable' => false,
            'filter'   => false,
        ));

        $this->addColumn('package_number', array(
            'header'   => $this->__('Package Number'),
            'index'    => 'package_number',
            'renderer' => 'CoolRunner_CoolShipping_Block_Adminhtml_Grid_Widget_Column_Renderer_Tracking',
            'width'    => '10px',
            'sortable' => false,
            'filter'   => false,
        ));

        $this->addColumn('label_at', array(
            'header' => Mage::helper('sales')->__('Label Ordered On'),
            'index'  => 'label_created_at',
            'type'   => 'datetime',
            'width'  => '100px',
            'sortable' => false,
            'filter'   => false
        ));

        $this->addColumn('status', array(
            'header'   => Mage::helper('sales')->__('Order Status'),
            'index'    => 'status',
            'type'     => 'options',
            'width'    => '70px',
            'options'  => Mage::getSingleton('sales/order_config')->getStatuses(),
            'sortable' => false,
            'filter'   => false
        ));

        $this->addColumn('cool_shipping', array(
            'header'   => 'Shipping',
            'width'    => '5px',
            'renderer' => 'CoolRunner_CoolShipping_Block_Adminhtml_Grid_Widget_Column_Renderer_Shipping',
            'filter'   => false,
            'sortable' => false,
        ));

        $this->addColumnsOrder('cool_shipping', 'action');

        return $this;
    }

    protected function _prepareMassaction() {
        $this->setMassactionIdFilter('order_id');
        $this->setMassactionIdField('order_id');
        $this->getMassactionBlock()->setFormFieldName('order_ids');
        $this->getMassactionBlock()->setUseSelectAll(true);
        return $this;
    }

    public function getRowUrl($row) {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getId()));
        }
        return false;
    }
}
