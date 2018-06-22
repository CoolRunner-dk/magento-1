<?php

/**
 * Class CoolRunner_CoolShipping_Model_Order_Pdf
 *
 * @property int                                      $id
 * @property int                                      $order_id
 * @property string                                   $filename
 * @property string                                   $package_number
 * @property string                                   $pdf_base64
 * @property string                                   $pdf_link
 * @property string                                   $message
 * @property \CoolRunnerSDK\Models\Shipments\Shipment $shipment_obj
 * @property float                                    $excl_tax
 * @property float                                    $incl_tax
 *
 * @method getId()
 * @method getOrderId()
 * @method getFilename()
 * @method getPackageNumber()
 * @method getPdfBase64()
 * @method getPdfLink()
 * @method getMessage()
 * @method getExclTax()
 * @method getInclTax()
 */
class CoolRunner_CoolShipping_Model_Order_Pdf
    extends Mage_Core_Model_Abstract {

    const CREATE_OK                   = 1;
    const CREATE_FAIL                 = 2;
    const CREATE_INVALID_PACKAGE_SIZE = 3;
    const CREATE_INVALID_PARAMS       = 4;
    const CREATE_FAIL_CANCELED        = 5;

    public function _construct() {
        $this->_init('coolrunner/order_pdf');
    }

    /**
     * @param $order_id
     *
     * @return mixed|self
     */
    public function labelExists($order_id) {
        Mage::helper('coolrunner/logger')->log('Checking for label existence', $order_id);
        return !empty($this->getCollection()->addFieldToFilter('order_id', array($order_id))->getFirstItem()->_data);
    }

    public function getCacheSize() {
        $sizes = array('database' => 0, 'disk' => 0);
        $cache_dir = Mage::getBaseDir('var') . "/coolrunner/coolshipping";
        $files = array();
        $pdfs = Mage::getModel('coolrunner/order_pdf')->getCollection();
        if (file_exists($cache_dir)) {
            $files = glob("$cache_dir/*.pdf");
        }

        foreach ($files as $file) {
            $sizes['disk'] += filesize($file);
        }

        /** @var CoolRunner_CoolShipping_Model_Order_Pdf $pdf */
        foreach ($pdfs as $pdf) {
            if ($pdf->getPdfBase64() !== null) {
                $sizes['database'] += strlen($pdf->getPdfBase64());
            }
        }

        foreach ($sizes as $key => &$size) {
            $prefixes = array('B', 'Kb', 'Mb', 'Gb', 'Tb'); // Up to terabytes because reasons
            foreach ($prefixes as $i => $prefix) {
                $fsize = $size;
                $delimiter = pow(1024, $i);

                $res = $fsize / $delimiter;

                if ($res < 1000) {
                    if ($prefix !== 'B') {
                        $res = number_format($res, 2);
                    }
                    $size = "$res $prefix";
                    break;
                }
            }
        }

        Mage::helper('coolrunner/logger')->log('Retrieving cache size', serialize($sizes));

        return $sizes;
    }

    public function clearCacheTypes($list) {
        Mage::helper('coolrunner/logger')->log('Clearing cache');
        $list = array_filter(array_map('strtolower', $list), function ($entry) {
            return in_array($entry, array('database', 'disk'));
        });

        foreach ($list as $type) {
            switch ($type) {
                case 'database':
                    $pdfs = Mage::getModel('coolrunner/order_pdf')->getCollection();
                    /** @var CoolRunner_CoolShipping_Model_Order_Pdf $pdf */
                    foreach ($pdfs as $pdf) {
                        $pdf->setPdfBase64(null);
                        $pdf->save();
                    }
                    break;
                case 'disk':
                    $cache_dir = Mage::getBaseDir('var') . "/coolrunner/coolshipping";
                    if (file_exists($cache_dir)) {
                        foreach (glob("$cache_dir/*.pdf") as $file) {
                            unlink($file);
                        }
                    }
                    break;
            }
        }

        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }

    /**
     * @return CoolRunner_CoolShipping_Model_Resource_Order_Pdf_Collection|CoolRunner_CoolShipping_Model_Order_Pdf[]
     */
    public function getCollection() {
        return parent::getCollection();
    }

    public function createFromOrderId($order_id, $package_size) {
        Mage::helper('coolrunner/logger')->log('Creating PDF from Order ID', $order_id, serialize($package_size));
        /** @var CoolRunner_CoolShipping_Helper_Information $helper */
        $helper = Mage::helper('coolrunner/information');
        /** @var \CoolRunnerSDK\API $apiv3 */
        $apiv3 = Mage::getModel('coolrunner/apiv3')->loadAPI(Mage_Core_Model_App::ADMIN_STORE_ID);
        /** @var Mage_Sales_Model_Order $order_info */
        $order_info = $helper->getOrderByEntityId($order_id);

        $add_package_number_to_shipment = Mage::helper('coolrunner')->getConfig('coolrunner/settings/add_package_number_to_shipment');
        $create_shipment = Mage::helper('coolrunner')->getConfig('coolrunner/settings/create_shipment');
        $send_shipment_mail = Mage::helper('coolrunner')->getConfig('coolrunner/settings/send_shipment_mail');

        if ($order_id !== false && $helper->orderExists($order_id)) {
            if ($helper->getOrderByEntityId($order_id)->getStatus() !== 'canceled') {
                /** @var CoolRunner_CoolShipping_Model_Order_Pdf $pdf */
                $pdf = $helper->getOrderPdfCollection()->addFieldToFilter('order_id', array($order_id))->getFirstItem();
                if ($pdf->labelExists($order_id)) {
                    return true;
                }

                $package_sizes = Mage::getStoreConfig('coolrunner/package/size');
                $package_sizes = unserialize($package_sizes);
                if (!empty($package_sizes) && $package_size && isset($package_sizes[$package_size])) {
                    $receiver_info = $helper->getReceiverInformation($order_id);
                    if ($receiver_info->servicepoint) {
                        $servicepoint = $apiv3->getServicepoint($receiver_info->getCarrier(), $receiver_info->getServicepoint());
                        $receiver = array(
                            'name'      => "{$receiver_info->getFirstname()} {$receiver_info->getLastname()}",
                            'attention' => '',
                            'street1'   => $servicepoint->address->street,
                            'street2'   => '',
                            'zip_code'  => $servicepoint->address->zip_code,
                            'city'      => $servicepoint->address->city,
                            'country'   => $servicepoint->address->country_code,
                            'phone'     => $receiver_info->getTelephone(),
                            'email'     => $order_info->getCustomerEmail()
                        );
                    } else {
                        $order_info->getShippingAddress();
                        $receiver = array(
                            'name'      => $order_info->getCustomerFirstname() . " " . $order_info->getCustomerLastname(),
                            'attention' => '',
                            'street1'   => $order_info->getShippingAddress()->getStreet1(),
                            'street2'   => $order_info->getShippingAddress()->getStreet2(),
                            'zip_code'  => $order_info->getShippingAddress()->getPostcode(),
                            'city'      => $order_info->getShippingAddress()->getCity(),
                            'country'   => $order_info->getShippingAddress()->getCountry(),
                            'phone'     => $order_info->getShippingAddress()->getTelephone(),
                            'email'     => $order_info->getCustomerEmail()
                        );
                    }

                    $package_size = $package_sizes[$package_size];
                    $sender = $helper->getSenderInformation();

                    $method = explode('_', $order_info->getShippingMethod(true)->getMethod());
                    $carrier = $method[0];
                    $product = $method[1];
                    $service = $method[2];

                    $label_format = Mage::helper('coolrunner')->getConfig('coolrunner/settings/print_size', Mage::app()->getStore()->getId());

                    $pkg = array(
                        'sender'          => $sender,
                        'receiver'        => $receiver,
                        'length'          => $package_size['length'],
                        'width'           => $package_size['width'],
                        'height'          => $package_size['height'],
                        'weight'          => (floatval($package_size['weight']) * 1000) + 1,
                        'carrier'         => $carrier,
                        'carrier_product' => $product,
                        'carrier_service' => $service,
                        'reference'       => $helper->__('Order No. ') . ' ' . $helper->getOrderByEntityId($order_id)->getIncrementId(),
                        'comment'         => $helper->__('Order No. ') . ' ' . $helper->getOrderByEntityId($order_id)->getIncrementId(),
                        'description'     => $helper->__('Order No. ') . ' ' . $helper->getOrderByEntityId($order_id)->getIncrementId(),
                        'label_format'    => $label_format,
                        'servicepoint_id' => $receiver_info->getServicepoint()
                    );

                    $pkg = new \CoolRunnerSDK\Models\Shipments\Shipment($pkg);
                    $errors = $pkg->validate();
                    if ($errors === true) {
                        $result = $pkg->create();
                        if ($result !== false) {
                            $pdf = new CoolRunner_CoolShipping_Model_Order_Pdf();

                            $pdf->order_id = $order_id;
                            $pdf->package_number = $result->package_number;
                            $pdf->message = $result->reference;
                            $pdf->excl_tax = $result->price->excl_tax;
                            $pdf->incl_tax = $result->price->incl_tax;
                            $pdf->shipment_obj = $result->toJson();

                            try {
                                $pdf->save();


                                $package_number = $pdf->package_number;
                                if ($add_package_number_to_shipment) {
                                    $shipment_collection = Mage::getResourceModel('sales/order_shipment_collection');
                                    $shipment_collection->addAttributeToFilter('order_id', $order_id);

                                    $carrier_code = 'custom';
                                    $shipping_method = $order_info->getShippingMethod();

                                    if (strpos($shipping_method, 'coolrunner') !== false) {
                                        $shipping_method = explode('_', $shipping_method);
                                        $shipping_method = "{$shipping_method[0]}_{$shipping_method[1]}";

                                        $carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers();

                                        foreach ($carrierInstances as $code => $carrier) {
                                            if ($code === $shipping_method && $carrier->isTrackingAvailable()) {
                                                $carrier_code = $shipping_method;
                                                break;
                                            }
                                        }
                                    }

                                    if ($shipment_collection->getSize() > 0) {

                                        foreach ($shipment_collection as $sc) {
                                            $addTrack = true;
                                            $shipment = Mage::getModel('sales/order_shipment');
                                            $shipment->load($sc->getId());

                                            foreach ($shipment->getTracksCollection() as $track) {
                                                if (is_array($package_number)) {
                                                    foreach ($package_number as $key => $pNo) {
                                                        if ($track->getTrackNumber() == $pNo || $track->getNumber() == $pNo) {
                                                            unset($package_number[$key]);
                                                        }
                                                    }
                                                    if (count($package_number) == 0) {
                                                        $addTrack = false;
                                                    }
                                                } else {
                                                    if ($track->getTrackNumber() == $package_number || $track->getNumber() == $package_number) {
                                                        $addTrack = false;
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($addTrack) {
                                                if (is_array($package_number)) {
                                                    foreach ($package_number as $pNo) {
                                                        Mage::getModel('sales/order_shipment_api')->addTrack($shipment->getIncrementId(), $carrier_code, $order_info->getShippingDescription(), $pNo);
                                                    }
                                                } else {
                                                    Mage::getModel('sales/order_shipment_api')->addTrack($shipment->getIncrementId(), $carrier_code, $order_info->getShippingDescription(), $package_number);
                                                }

                                                if ($send_shipment_mail) {
                                                    $shipment->save();
                                                    $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipment->getIncrementId());
                                                    $shipment->sendEmail($send_shipment_mail, '');
                                                } else {
                                                    $shipment->save();
                                                }
                                            }
                                        }
                                    } else {
                                        if ($create_shipment && $order_info->canShip()) {

                                            $shipmentIncrementId = Mage::getModel('sales/order_shipment_api')->create($order_info->getIncrementId());
                                            /** @var Mage_Sales_Model_Order_Shipment $shipment */
                                            $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentIncrementId);
                                            $shipment->setEmailSent($send_shipment_mail);


                                            $transactionSave = Mage::getModel('core/resource_transaction')
                                                ->addObject($shipment)
                                                ->addObject($shipment->getOrder())
                                                ->save();

                                            if (is_array($package_number)) {
                                                foreach ($package_number as $pNo) {
                                                    Mage::getModel('sales/order_shipment_api')->addTrack($shipment->getIncrementId(), $carrier_code, $order_info->getShippingDescription(), $pNo);
                                                }
                                            } else {
                                                Mage::getModel('sales/order_shipment_api')->addTrack($shipment->getIncrementId(), $carrier_code, $order_info->getShippingDescription(), $package_number);
                                            }

                                            if ($send_shipment_mail) {
                                                $shipment->save();
                                                $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipment->getIncrementId());
                                                $shipment->sendEmail($send_shipment_mail, '');
                                            } else {
                                                $shipment->save();
                                            }
                                        }
                                    }
                                }

                                return self::CREATE_OK;
                            } catch (Exception $exception) {
                                die('Something went wrong | ' . $exception->getMessage());
                            }
                        } else {
                            return self::CREATE_INVALID_PACKAGE_SIZE;
                        }


                    } else {
                        return self::CREATE_INVALID_PARAMS;
                    }
                }
            } else {
                return self::CREATE_FAIL_CANCELED;
            }
        }

        return self::CREATE_FAIL;
    }
}