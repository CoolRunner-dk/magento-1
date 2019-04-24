<?php

class CoolRunner_CoolShipping_Model_Observer
{
    public function __construct()
    {
        Mage::getModel('coolrunner/apiv3')->autoload();
    }

    public function appendDroppointHtml($observer)
    {
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Checkout_Block_Onepage_Shipping_Method_Available ||
            $block instanceof AW_Onestepcheckout_Block_Onestep_Form_Shippingmethod) {
            Mage::helper('coolrunner/logger')->log('Triggered observer method appendDroppointHtml ', get_class($block));
            $transport = $observer->getEvent()->getTransport();
            $html      = $transport->getHtml();
            Mage::helper('coolrunner/logger')->log($html);
            if (strpos($html, 'coolrunner_postnord_private_droppoint') !== false) {
                Mage::helper('coolrunner/logger')->log('Matched coolrunner_postnord_private_droppoint');
                $html .= $block->getLayout()
                               ->createBlock('coolrunner/servicepoints_postnord')
                               ->setTemplate('coolshipping/droppoints.phtml')->toHtml();
            }
            if (strpos($html, 'coolrunner_gls_private_droppoint') !== false) {
                Mage::helper('coolrunner/logger')->log('Matched coolrunner_gls_private_droppoint');
                $html .= $block->getLayout()
                               ->createBlock('coolrunner/servicepoints_gls')
                               ->setTemplate('coolshipping/droppoints.phtml')->toHtml();
            }
            if (strpos($html, 'coolrunner_bring_private_droppoint') !== false) {
                Mage::helper('coolrunner/logger')->log('Matched coolrunner_bring_private_droppoint');
                $html .= $block->getLayout()
                               ->createBlock('coolrunner/servicepoints_bring')
                               ->setTemplate('coolshipping/droppoints.phtml')->toHtml();
            }
            if (strpos($html, 'coolrunner_dao_private_droppoint') !== false) {
                Mage::helper('coolrunner/logger')->log('Matched coolrunner_dao_private_droppoint');
                $html .= $block->getLayout()
                               ->createBlock('coolrunner/servicepoints_dao')
                               ->setTemplate('coolshipping/droppoints.phtml')->toHtml();
            }
            if (strpos($html, 'coolrunner_dhl_private_droppoint') !== false) {
                Mage::helper('coolrunner/logger')->log('Matched coolrunner_dhl_private_droppoint');
                $html .= $block->getLayout()
                               ->createBlock('coolrunner/servicepoints_dhl')
                               ->setTemplate('coolshipping/droppoints.phtml')->toHtml();
            }
            if (strpos($html, 'coolrunner_posti_private_droppoint') !== false) {
                Mage::helper('coolrunner/logger')->log('Matched coolrunner_posti_private_droppoint');
                $html .= $block->getLayout()
                               ->createBlock('coolrunner/servicepoints_posti')
                               ->setTemplate('coolshipping/droppoints.phtml')->toHtml();
            }

            $html .= $block->getLayout()->createBlock('coolrunner/logos')->setTemplate('coolshipping/logos.phtml')->toHtml();
            $transport->setHtml($html);
        }
    }

    public function addMassAction($observer)
    {
        /** @var Mage_Adminhtml_Block_Widget_Grid_Massaction $block */
        $block = $observer->getEvent()->getBlock();

        $isLabelGrid = ($block->getRequest()->getControllerModule() === 'CoolRunner_CoolShipping' &&
                        strpos($block->getRequest()->getControllerName(), 'shipping') !== false);
        if (get_class($block) == 'Mage_Adminhtml_Block_Widget_Grid_Massaction' &&
            (
                strpos($block->getRequest()->getControllerName(), 'sales_order') !== false ||
                $isLabelGrid
            ) &&
            Mage::getStoreConfig('coolrunner/settings/active')) {
            Mage::helper('coolrunner/logger')->log('Triggered observer method addMassAction ', get_class($block));
            /** @var CoolRunner_CoolShipping_Helper_Information $helper */
            $helper = Mage::helper('coolrunner/information');

            if ($helper->getOrderPdfCollection()->count()) {
                $url = Mage::helper('adminhtml')->getUrl('coolrunner/shipping/downloadPdfs', array());
            } else {
                $url = 'javascript:void(0)';
            }

            $types = array(
                'coolrunner_blank_a'         => array(
                    'label' => str_pad('', strlen(Mage::helper('coolrunner')->__('Download CoolShipping Labels')), '-')
                ),
                'coolrunner_bulk_create'     => array(
                    'label' => Mage::helper('coolrunner')->__('Bulk Create Labels'),
                    'url'   => Mage::helper('adminhtml')->getUrl('coolrunner/shipping/bulkCreate', array())
                ),
                'coolrunner_download_labels' => array(
                    'label' => Mage::helper('coolrunner')->__('Download CoolShipping Labels'),
                    'url'   => Mage::helper('adminhtml')->getUrl('coolrunner/shipping/downloadPdfs', array())
                ),
                'coolrunner_blank_b'         => array(
                    'label' => str_pad('', strlen(Mage::helper('coolrunner')->__('Download CoolShipping Labels')), '-')
                )
            );

            foreach ($types as $id => $type) {
                if ( ! $isLabelGrid) {
                    $block->addItem($id, $type);
                } else {
                    if (strpos($id, 'blank') === false) {
                        $type['selected'] = 'selected';
                        $block->addItem($id, $type);
                    }
                }
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @throws Varien_Exception
     */
    public function saveQuoteData($observer)
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $request = Mage::app()->getRequest();
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        $shippingMethod = $quote->getShippingAddress()->getData('shipping_method');

        $prefix   = "coolrunner";
        $carriers = array('dao', "postnord", 'gls', 'dhl', 'coolrunner', 'posti', 'bring');

        if (strpos($shippingMethod, $prefix . "_") !== false) {
            foreach ($carriers as $carrier) {
                $code = $prefix . "_" . $carrier;
                if (strpos($shippingMethod, $code) !== false) {
                    Mage::helper('coolrunner/logger')->log('Triggered observer method saveQuoteData on ' . $code);
                    $quote_id = $quote->getId();

                    // Pickup information
                    $pickup_firstname = trim($request->getPost("$carrier-pickup-firstname", ''));
                    $pickup_lastname  = trim($request->getPost("$carrier-pickup-lastname", ''));
                    $pickup_telephone = trim($request->getPost("$carrier-pickup-phone", ''));

                    // Servicepoint information
                    $servicepoint_id     = trim($request->getPost("$carrier-servicepoint-id", ''));
                    $servicepoint_name   = trim($request->getPost("$carrier-servicepoint-name", ''));
                    $servicepoint_city   = trim($request->getPost("$carrier-servicepoint-city", ''));
                    $servicepoint_zip    = trim($request->getPost("$carrier-servicepoint-zip-code", ''));
                    $servicepoint_street = trim($request->getPost("$carrier-servicepoint-street", ''));

                    /** @var Mage_Core_Model_Resource $resource */
                    $resource = Mage::getSingleton('core/resource');

                    $write = $resource->getConnection('core_write');
                    $table = $resource->getTableName('coolrunner_coolshipping_sales_order_info');

                    $sql = "INSERT INTO $table 
                              (quote_id, carrier, servicepoint, firstname, lastname,telephone)
                              VALUES (:id, :carrier, :servicepoint, :firstname, :lastname, :telephone)
                            ON DUPLICATE KEY UPDATE 
                              carrier=:carrier, 
                              servicepoint=:servicepoint, 
                              firstname=:firstname, 
                              lastname=:lastname, 
                              telephone=:telephone;";

                    $params = array(
                        'id'           => $quote_id,
                        'servicepoint' => $servicepoint_id,
                        'firstname'    => $pickup_firstname,
                        'lastname'     => $pickup_lastname,
                        'telephone'    => $pickup_telephone,
                        'carrier'      => $carrier
                    );

                    $write->query($sql, $params);


                    if (strpos($shippingMethod, 'droppoint') !== false) {
                        try {
                            $quote_shipping_address = $quote->getShippingAddress()
                                                            ->setFirstname($pickup_firstname)
                                                            ->setLastname($pickup_lastname)
                                                            ->setCompany($servicepoint_name)
                                                            ->setStreet($servicepoint_street)
                                                            ->setPostcode($servicepoint_zip)
                                                            ->setCity($servicepoint_city)
                                                            ->setTelephone($pickup_telephone)
                                                            ->setFax('');

                            $table  = $resource->getTableName('sales/quote_address');
                            $sql    = "UPDATE $table SET 
                                        firstname=:firstname, 
                                        lastname=:lastname, 
                                        company=:sp_id, 
                                        street=:sp_street, 
                                        postcode=:sp_zip, 
                                        city=:sp_city, 
                                        telephone=:telephone, 
                                        fax='' 
                                    WHERE quote_id=:q_id AND address_type='shipping'";
                            $params = array(
                                'firstname' => $pickup_firstname,
                                'lastname'  => $pickup_lastname,
                                'sp_id'     => $servicepoint_id,
                                'sp_street' => $servicepoint_street,
                                'sp_zip'    => $servicepoint_zip,
                                'sp_city'   => $servicepoint_city,
                                'telephone' => $pickup_telephone,
                                'q_id'      => $quote_id
                            );
                            error_log(serialize($params));
                            $write->query($sql, $params);
                            $quote->setShippingAddress($quote_shipping_address);
                            $quote->save();
                        } catch (Exception $e) {
                            Mage::log($e->getMessage(), null, 'coolrunner.log', true);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @throws Varien_Exception
     */
    public function saveDataOnOrder($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        $quote_id = $quote->getId();
        $order_id = $order->getId();

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');

        $write  = $resource->getConnection('core_write');
        $table  = $resource->getTableName('coolrunner_coolshipping_sales_order_info');
        $sql    = "UPDATE $table SET 
                  order_id=:order_id 
                WHERE quote_id=:quote_id";
        $params = array(
            'order_id' => $order_id,
            'quote_id' => $quote_id
        );

        $write->query($sql, $params);
    }
}