<?php

class CoolRunner_CoolShipping_Block_Shipping_Labels_Grid
    extends Mage_Adminhtml_Block_Widget_Grid {

    public function getGridUrl() {
        return Mage::getUrl('*/*/grid', array('_current' => true));
    }

    public function __construct(array $attributes = array()) {
        Mage::helper('coolrunner/logger')->log('Accessing Shipping_Labels_Grid');
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
        Mage::helper('coolrunner/logger')->log('Preparing collection for Shipping_Labels_Grid');
        /** @var Mage_Sales_Model_Resource_Order_Grid_Collection $collection */
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        Mage::helper('coolrunner/logger')->log('ResourceModel loaded for Shipping_Labels_Grid');

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

        Mage::helper('coolrunner/logger')->log('Collection joined for Shipping_Labels_Grid');

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
        Mage::helper('coolrunner/logger')->log('Collection set for Shipping_Labels_Grid');
        return parent::_prepareColumns();
    }

    protected function _prepareColumns() {
        Mage::helper('coolrunner/logger')->log('Preparing columns for Shipping_Labels_Grid');
        $this->removeColumn('created_at');

        $this->addColumn('real_order_id', array(
            'header'   => Mage::helper('sales')->__('Order #'),
            'width'    => '80px',
            'type'     => 'text',
            'index'    => 'increment_id',
            'sortable' => false,
            'filter'   => false
        ));
        Mage::helper('coolrunner/logger')->log('Added column "Order #" for Shipping_Labels_Grid');

        $this->addColumn('customer*_id', array(
            'header'   => Mage::helper('coolrunner')->__('Customer ID'),
            'width'    => '1px',
            'index'    => 'customer_id',
            'type'     => 'number',
            'sortable' => false,
            'filter'   => false
        ));
        Mage::helper('coolrunner/logger')->log('Added column "Customer ID" for Shipping_Labels_Grid');

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
            Mage::helper('coolrunner/logger')->log('Added column "Purchased From (Store)" for Shipping_Labels_Grid');
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
        Mage::helper('coolrunner/logger')->log('Added column "Price excl. tax" for Shipping_Labels_Grid');

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
        Mage::helper('coolrunner/logger')->log('Added column "Price incl. tax" for Shipping_Labels_Grid');

        $this->addColumn('billing_name', array(
            'header'   => Mage::helper('sales')->__('Bill to Name'),
            'index'    => 'billing_name',
            'width'    => '10px',
            'sortable' => false,
            'filter'   => false
        ));
        Mage::helper('coolrunner/logger')->log('Added column "Bill to Name" for Shipping_Labels_Grid');

        $this->addColumn('shipping_name', array(
            'header'   => Mage::helper('sales')->__('Ship to Name'),
            'index'    => 'shipping_name',
            'width'    => '10px',
            'sortable' => false,
            'filter'   => false
        ));
        Mage::helper('coolrunner/logger')->log('Added column "Ship to Name" for Shipping_Labels_Grid');

        $this->addColumn('shipping_address', array(
            'header'   => Mage::helper('sales')->__('Ship to Address'),
            'index'    => 'shipping_address',
            'renderer' => 'CoolRunner_CoolShipping_Block_Adminhtml_Grid_Widget_Column_Renderer_ShippingAddress',
            'sortable' => false,
            'filter'   => false,
        ));
        Mage::helper('coolrunner/logger')->log('Added column "Ship to Address" for Shipping_Labels_Grid');

        $this->addColumn('package_number', array(
            'header'   => $this->__('Package Number'),
            'index'    => 'package_number',
            'renderer' => 'CoolRunner_CoolShipping_Block_Adminhtml_Grid_Widget_Column_Renderer_Tracking',
            'width'    => '10px',
            'sortable' => false,
            'filter'   => false,
        ));
        Mage::helper('coolrunner/logger')->log('Added column "Package Number" for Shipping_Labels_Grid');

        $this->addColumn('label_at', array(
            'header' => Mage::helper('sales')->__('Label Ordered On'),
            'index'  => 'label_created_at',
            'type'   => 'datetime',
            'width'  => '100px',
            'sortable' => false,
            'filter'   => false
        ));
        Mage::helper('coolrunner/logger')->log('Added column "Label Ordered On" for Shipping_Labels_Grid');

        $this->addColumn('status', array(
            'header'   => Mage::helper('sales')->__('Order Status'),
            'index'    => 'status',
            'type'     => 'options',
            'width'    => '70px',
            'options'  => Mage::getSingleton('sales/order_config')->getStatuses(),
            'sortable' => false,
            'filter'   => false
        ));
        Mage::helper('coolrunner/logger')->log('Added column "Order Status" for Shipping_Labels_Grid');

        $this->addColumn('cool_shipping', array(
            'header'   => 'Shipping',
            'width'    => '5px',
            'renderer' => 'CoolRunner_CoolShipping_Block_Adminhtml_Grid_Widget_Column_Renderer_Shipping',
            'filter'   => false,
            'sortable' => false,
        ));
        Mage::helper('coolrunner/logger')->log('Added column "Shipping" for Shipping_Labels_Grid');

        $this->addColumnsOrder('cool_shipping', 'action');
        Mage::helper('coolrunner/logger')->log('Re-ordered columns for Shipping_Labels_Grid');

        return $this;
    }

    protected function _prepareMassaction() {
        $this->setMassactionIdFilter('order_id');
        $this->setMassactionIdField('order_id');
        $this->getMassactionBlock()->setFormFieldName('order_ids');
        $this->getMassactionBlock()->setUseSelectAll(true);
        Mage::helper('coolrunner/logger')->log('Prepared mass action for Shipping_Labels_Grid');
        return $this;
    }

    public function getRowUrl($row) {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getId()));
        }
        return false;
    }
}
