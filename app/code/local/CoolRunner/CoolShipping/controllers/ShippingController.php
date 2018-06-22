<?php

class CoolRunner_CoolShipping_ShippingController
    extends Mage_Adminhtml_Controller_Action {

    protected function __getId() {
        return $this->getRequest()->getParam('order_id', false);
    }

    protected function _isAllowed() {
        Mage::helper('coolrunner/logger')->log('Accessing ShippingController');
        if (Mage::getSingleton('admin/session')->isAllowed('coolrunner')) {
            Mage::helper('coolrunner/logger')->log('Can access ShippingController');
        } else {
            Mage::helper('coolrunner/logger')->log('Cannot access ShippingController');
        }

        return Mage::getSingleton('admin/session')->isAllowed('coolrunner');
    }

    public function indexAction() {
        Mage::helper('coolrunner/logger')->log('Started indexAction');
        if (CoolRunner_CoolShipping_Model_Tools::isActive()) {
            $this->_title($this->__('CoolShipping'))->_title($this->__('Labels'));
            $this->loadLayout();
            $this->_setActiveMenu('sales/labels');
            $this->renderLayout();
            Mage::helper('coolrunner/logger')->log('Rendered indexAction');
        }
        Mage::helper('coolrunner/logger')->log('Stopped indexAction');
    }

    public function gridAction() {
        Mage::helper('coolrunner/logger')->log('Started gridAction');
        if (CoolRunner_CoolShipping_Model_Tools::isActive() && $this->getRequest()->isAjax()) {
            $this->getResponse()->setBody(
                $this->getLayout()->createBlock('coolrunner/shipping_labels_grid')->toHtml()
            );
            Mage::helper('coolrunner/logger')->log('Rendered gridAction');
        }
        Mage::helper('coolrunner/logger')->log('Stopped gridAction');
    }

    public function pricesAction() {
        Mage::helper('coolrunner/logger')->log('Started pricesAction');
        if (CoolRunner_CoolShipping_Model_Tools::isActive()) {
            $this->_title($this->__('CoolShipping'))->_title($this->__('Prices'));
            $this->loadLayout();
            $this->_setActiveMenu('coolrunner/prices');
            $this->renderLayout();
            Mage::helper('coolrunner/logger')->log('Rendered pricesAction');
        }
        Mage::helper('coolrunner/logger')->log('Stopped pricesAction');
    }

    public function getTrackingAction() {
        Mage::helper('coolrunner/logger')->log('Started getTrackingAction');
        if (CoolRunner_CoolShipping_Model_Tools::isActive()) {
            /** @var CoolRunner_CoolShipping_Helper_Information $helper */
            $helper = Mage::helper('coolrunner/information');

            include $helper->getTemplate('admin', 'label/tracking_label');
            Mage::helper('coolrunner/logger')->log('Rendered getTrackingAction');
        }
        Mage::helper('coolrunner/logger')->log('Stopped getTrackingAction');
    }

    public function getLabelAction($download = true) {
        Mage::helper('coolrunner/logger')->log('Started getLabelAction');
        if (CoolRunner_CoolShipping_Model_Tools::isActive()) {
            $order_id = $this->getRequest()->getParam('order_id');
            $dl = $this->getRequest()->getParam('download', false);
            /** @var CoolRunner_CoolShipping_Helper_Information $helper */
            $helper = Mage::helper('coolrunner/information');
            /** @var CoolRunner_CoolShipping_Model_Order_Pdf $order_pdf */
            $order_pdf = $helper->getOrderPdf($order_id);

            $dl = $download && $dl ? $dl : false;

            if ((string)$dl === '1') {
                return $this->_prepareDownloadResponse("coolshipping-{$order_pdf->package_number}-{$order_id}.pdf", $this->fetchPdf($order_pdf));
            }
            header('Content-Type: application/pdf');
            echo $this->fetchPdf($order_pdf);

            Mage::helper('coolrunner/logger')->log('Rendered getLabelAction');
            return true;
        }
        Mage::helper('coolrunner/logger')->log('Stopped getLabelAction');
    }

    public function bulkCreateAction() {
        Mage::helper('coolrunner/logger')->log('Started bulkCreateAction');
        if (CoolRunner_CoolShipping_Model_Tools::isActive()) {
            /** @var CoolRunner_CoolShipping_Model_Order_Pdf $pdf */
            $pdf = Mage::getModel('coolrunner/order_pdf');
            /** @var CoolRunner_CoolShipping_Helper_Information $helper */
            $helper = Mage::helper('coolrunner/information');

            $order_ids = $this->getRequest()->getParams()['order_ids'];
            $order_ids = array_filter($order_ids, function ($e) use ($pdf, $helper) {
                return !($pdf->labelExists($e) || $helper->getOrderByEntityId($e)->isCanceled());
            });

            if (count($order_ids) === 0) {
                $this->_redirect('*/*/index');
            }

            if ($this->getRequest()->getMethod() === 'POST') {
                $this->loadLayout();
                $params = $this->getRequest()->getParams();

                $pdfs = array();
                if (isset($params['package-size-override']) || isset($params['package-size'])) {
                    $ids = $params['order_ids'];
                    foreach ($ids as $id) {
                        if (isset($params['package-size-override']) && $params['package-size-override'] !== '') {
                            $package_size = $params['package-size-override'];
                        } else {
                            $package_size = $params['package-size'][$id];
                        }

                        if (!$pdf->labelExists($id)) {
                            $res = $pdf->createFromOrderId($id, $package_size);

                            $pdfs[$id] = $res;
                        }
                    }

                    if (!in_array($pdf::CREATE_FAIL, $pdfs) &&
                        !in_array($pdf::CREATE_FAIL_CANCELED, $pdfs) &&
                        !in_array($pdf::CREATE_INVALID_PARAMS, $pdfs) &&
                        !in_array($pdf::CREATE_INVALID_PACKAGE_SIZE, $pdfs)) {
                        $this->_redirect('*/*/index');
                    } else {
                        /** @var Mage_Adminhtml_Model_Session $session */
                        $session = Mage::getSingleton('adminhtml/session');
                        /** @var \CoolRunnerSDK\API $api */
                        $api = Mage::getModel('coolrunner/apiv3')->loadAPI(Mage_Core_Model_App::ADMIN_STORE_ID);

                        $i = 0;
                        foreach ($pdfs as $id => $res) {
                            $order = $helper->getOrderByEntityId($id);
                            $msg = $api->getLastResponse()->getErrorMsg();
                            if ($msg) {
                                $msg = " | $msg";
                            }
                            if ($res === $pdf::CREATE_INVALID_PACKAGE_SIZE) {
                                $session->addError($helper->__("Invalid package size for {$order->getIncrementId()}$msg"));
                            }
                            if ($res === $pdf::CREATE_FAIL_CANCELED) {
                                $session->addError($helper->__("Cannot create shipping label for canceled order {$order->getIncrementId()}$msg"));
                            }
                            if ($res === $pdf::CREATE_INVALID_PARAMS) {
                                $session->addError($helper->__("Invalid shipment {$order->getIncrementId()}$msg"));
                            }
                            if ($res === $pdf::CREATE_FAIL) {
                                $session->addError($helper->__("Failed to create shipping label for {$order->getIncrementId()}$msg"));
                            }

                            if ($res === $pdf::CREATE_OK) {
                                $i++;
                            }
                        }

                        if ($i > 0) {
                            $session->addSuccess("Created shipping labels for $i orders");
                        }
                    }
                }

                $this->renderLayout();
                Mage::helper('coolrunner/logger')->log('Rendered bulkCreateAction');
            }
        }
        Mage::helper('coolrunner/logger')->log('Stopped bulkCreateAction');
    }

    public function createLabelAction() {
        Mage::helper('coolrunner/logger')->log('Started createLabelAction');
        if (CoolRunner_CoolShipping_Model_Tools::isActive()) {
            $order_id = $this->__getId();
            $size = $this->getRequest()->getParam('package-size', false);
            /** @var CoolRunner_CoolShipping_Helper_Information $helper */
            $helper = Mage::helper('coolrunner/information');

            $pdf = new CoolRunner_CoolShipping_Model_Order_Pdf();
            switch ($this->getRequest()->getMethod()) {
                case 'POST':
                    $res = $pdf->createFromOrderId($order_id, $size);

                    if ($res === $pdf::CREATE_OK) {
                        $this->_redirect('*/*/getlabel', array('order_id' => $order_id));
                    } else {
                        $this->_redirect('*/*/*', array('order_id' => $order_id, 'fail' => '1'));
                    }
                    break;
                case 'GET':
                    include $helper->getTemplate('admin', 'label/new_label');
                    break;
            }
            Mage::helper('coolrunner/logger')->log('Rendered createLabelAction');
        }
        Mage::helper('coolrunner/logger')->log('Stopped createLabelAction');
    }

    public function downloadPdfsAction() {
        Mage::helper('coolrunner/logger')->log('Stopped downloadPdfsAction');
        if (CoolRunner_CoolShipping_Model_Tools::isActive()) {
            $params = $this->getRequest()->getParams();
            if (isset($params['order_ids']) && is_array($params['order_ids']) && !empty($params['order_ids'])) {
                /** @var CoolRunner_CoolShipping_Helper_Information $helper */
                $helper = Mage::helper('coolrunner/information');
                $ids = $params['order_ids'];
                $pdf = new Zend_Pdf();

                $collection = $helper->getOrderPdfCollection()->addFieldToFilter('order_id', $ids);

                /** @var CoolRunner_CoolShipping_Model_Order_Pdf $order_pdf */
                foreach ($collection as $order_pdf) {
                    $content = $this->fetchPdf($order_pdf);
                    $label = Zend_Pdf::parse($content);
                    foreach ($label->pages as $page) {
                        $pdf->pages[] = clone $page;
                    }
                }

                $filename = "CoolRunner_Pdf_Labels_" . Mage::getModel('core/date')->date("Y-m-d_H-i-s") . ".pdf";

                Mage::helper('coolrunner/logger')->log('Rendered downloadPdfsAction');
                Mage::helper('coolrunner/logger')->log('Stopped downloadPdfsAction');
                return $this->_prepareDownloadResponse($filename, $pdf->render(), 'application/pdf');
//
//            $collection = Mage::getModel('coolrunner/order_pdf')->getCollection()
//                ->addFieldToFilter('order_id',array('in' => $params['order_ids']));
//
//            foreach($collection as $pdfItem)
//            {
//                $pdf_content = base64_decode($pdfItem->getPdfBase64());
//                $pdf_tmp = new Zend_Pdf();
//                $pdf_tmp = Zend_Pdf::parse($pdf_content);
//
//                foreach($pdf_tmp->pages as $page)
//                {
//                    $pdf->pages[] = clone $page;
//                }
//            }
//
//            $filename = "CoolRunner_Pdf_Labels".Mage::getModel('core/date')->date("Y-m-d_H-i-s").".pdf";
//            return $this->_prepareDownloadResponse($filename, $pdf->render(), 'application/pdf');

            } else {
                $this->_redirect('adminhtml/sales_order');
            }
        }
    }

    /**
     * @param CoolRunner_CoolShipping_Model_Order_Pdf $order_pdf_object
     *
     * @return bool|string
     * @throws Varien_Exception
     */
    private function fetchPdf($order_pdf_object) {
        /** @var \CoolRunnerSDK\API $apiv3 */
        $apiv3 = Mage::getModel('coolrunner/apiv3')->loadAPI(Mage_Core_Model_App::ADMIN_STORE_ID);
        $cache_type = Mage::helper('coolrunner')->getConfig('coolrunner/settings/cache', Mage_Core_Model_App::ADMIN_STORE_ID);
        $var_dir = Mage::getBaseDir('var');

        $cache_type = is_null($cache_type) ? 0 : $cache_type;

        switch ($cache_type) {
            case 0: // No cache
                return $apiv3->getShipmentLabel($order_pdf_object->package_number);
            case 1: // Disk cache
                $cache_dir = "$var_dir/coolrunner/coolshipping";
                $cache_file = "$cache_dir/{$order_pdf_object->package_number}.pdf";
                if (!file_exists($cache_dir)) {
                    mkdir($cache_dir, 0755, true);
                }

                if (!file_exists($cache_file)) {
                    file_put_contents($cache_file, $apiv3->getShipmentLabel($order_pdf_object->package_number));
                }

                return file_get_contents($cache_file);
            case 2: // Database cache
                if ($order_pdf_object->pdf_base64 === '' || is_null($order_pdf_object->pdf_base64)) {
                    $order_pdf_object->pdf_base64 = base64_encode($apiv3->getShipmentLabel($order_pdf_object->package_number));
                    $order_pdf_object->save();
                }

                return base64_decode($order_pdf_object->pdf_base64);
            default: // Wot, m8?
                return null;
        }
    }

    public function getPackagePriceAction() {
        Mage::helper('coolrunner/logger')->log('Started getPackagePriceAction');
        $params = $this->getRequest()->getParams();
        /** @var CoolRunnerSDK\API $api */
        $api = Mage::getModel('coolrunner/apiv3')->loadApi(Mage_Core_Model_App::ADMIN_STORE_ID);

        $height = $params['height'];
        $width = $params['width'];
        $length = $params['length'];
        $weight = $params['weight'];
        $carrier_full = explode('_', str_replace('coolrunner_', '', $params['carrier']));
        $servicepoint = $params['servicepoint'];
        $country = $params['country'];

        $shipment = new \CoolRunnerSDK\Models\Shipments\Shipment();

        $shipment->height = $height;
        $shipment->width = $width;
        $shipment->length = $length;
        $shipment->weight = $weight * 1000;
        $shipment->carrier = $carrier_full[0];
        $shipment->servicepoint_id = $servicepoint;

        $result = $api->getProducts('dk')->getCountry($country)->getCarrier($carrier_full[0])->getType($carrier_full[1])->findProduct($shipment);

        if ($result !== false) {
            echo $result->toJson();
        } else {
            echo json_encode($this->__('No matching products for carrier'));
        }
        Mage::helper('coolrunner/logger')->log('Rendered getPackagePriceAction');

        Mage::helper('coolrunner/logger')->log('Stopped getPackagePriceAction');
//        echo $api->getProducts('dk')->getCountry($country)->getCarrier($carrier_full[0])->getType($carrier_full[1])->findProduct($shipment)->toJson();
    }
}