<?php

class CoolRunner_CoolShipping_Adminhtml_GridController extends Mage_Adminhtml_Controller_Action
{
	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('coolrunner');
	}
	
    public function processAction()
    {

	    $params = $this->getRequest()->getParams();

	    $helper = Mage::helper('coolrunner');
	    if(!isset($params['order_ids'])) {
		    throw new Exception($helper->__("No order ids found to process"));
	    } 
	    if(!isset($params['weight'])) {
		    throw new Exception($helper->__("No package weight found"));
	    }
	    if(!isset($params['height'])) {
		    throw new Exception($helper->__("No package height found"));
	    }
	    if(!isset($params['length'])) {
		    throw new Exception($helper->__("No package length found"));
	    }
	    if(!isset($params['width'])) {
		    throw new Exception($helper->__("No package width found"));
	    }
	    if(!isset($params['download'])) {
			$params['download'] = Mage::helper('coolrunner')->getConfig('coolrunner/settings/download');
	    }
	    
        $orderIds = $params['order_ids'];
        if(!is_array($orderIds)) {
	       	$orderIds = array($orderIds); 
        }

		$weight = $params['weight'];
		$height = $params['height'];
		$length = $params['length'];
		$width = $params['width'];
		$type = 'auto'; //$params['carrier_product_service'];
		
		$receiver = count($orderIds) == 1 && isset($params['receiver_data']) && is_array($params['receiver_data']) ? $params['receiver_data'] : array();
		$droppointData = count($orderIds) == 1 && isset($params['droppoint_data']) && is_array($params['droppoint_data']) ? $params['droppoint_data'] : array();
		
		$response = Mage::getModel('coolrunner/api')->exportOrders($orderIds,$type,$weight,$height,$length,$width,$receiver,$droppointData);

		$pdf = new Zend_Pdf();
		$filename = false;


		if(!empty($response)) {
			
			if(!isset($response['result']['shipments'])) {
				if(isset($response['status']) && $response['status'] == 'ok') {
					$response['result']['shipments'][] = $response['result'];
				}
			}
			
			if(isset($response['result']['shipments']) && is_array($response['result']['shipments'])){
				
				$localxml = simplexml_load_file(Mage::getBaseDir().DS."/app/etc/local.xml");		
				$host = (string)$localxml->global->resources->default_setup->connection->host;
				$user = (string)$localxml->global->resources->default_setup->connection->username;
				$pass = (string)$localxml->global->resources->default_setup->connection->password;
				$dbname = (string)$localxml->global->resources->default_setup->connection->dbname;

				$connection = mysql_connect($host,$user,$pass);
				mysql_select_db($dbname,$connection);
				
				foreach($response['result']['shipments'] as $shipment)
				{
					$increment_id = $shipment['reference'];
					$order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);
					if($order->getId())
					{
						// SAVE IN DB
						$order_id = $order->getId();
						$filename = $increment_id."_".date("Y-m-d_H-i-s",Mage::getModel('core/date')->timestamp(time())).".pdf";
						$package_number = isset($shipment['package_number']) ? $shipment['package_number'] : "";
						$pdf_base64 = isset($shipment['pdf_base64']) ? $shipment['pdf_base64'] : "";
						$pdf_link = isset($shipment['pdf_link']) ? $shipment['pdf_link'] : "";
						$shipment_id = isset($shipment['shipment_id']) ? $shipment['shipment_id'] : "";
						$message = isset($shipment['message']) ? $shipment['message'] : "";

						$resource = Mage::getSingleton('core/resource');
						$table = $resource->getTableName('coolrunner_coolshipping_sales_order_pdf');
						
						$query = "INSERT INTO $table (order_id,filename,package_number,pdf_base64,pdf_link,shipment_id,message) VALUES ($order_id,'$filename','$package_number','$pdf_base64','$pdf_link','$shipment_id','$message')";
						
						$result = mysql_query($query,$connection);					
						
						if($shipment['result'] == 'ok') {
							
							$pdf_content = base64_decode($pdf_base64);
							$pdf_save_path = Mage::getBaseDir('var').DS."coolrunner".DS."pdf".DS;
							$fileIo = new Varien_Io_File();
							$fileIo->checkAndCreateFolder($pdf_save_path);
							$fileIo->open(array('path' => $pdf_save_path));
							if($fileIo->write($filename, $pdf_content)) {
								//var_dump("PDF Stored in $export_path/$pdf_filename");
							}
							
							$pdf_tmp = new Zend_Pdf();
							$pdf_tmp = Zend_Pdf::parse($pdf_content);
							
							foreach($pdf_tmp->pages as $page) {
								$pdf->pages[] = clone $page;					
							}
							
						}
						
						$add_package_number_to_shipment = Mage::helper('coolrunner')->getConfig('coolrunner/settings/add_package_number_to_shipment');
						if($package_number && $add_package_number_to_shipment){
							
							$carrier_code = 'custom';
							$shipping_method = $order->getShippingMethod();
							
							/*if(strpos($shipping_method, 'coolrunner') !== false){
								$carrier_product_service = explode("_",str_replace("coolrunner_","",$shipping_method));
								$carrier = count($carrier_product_service) ? array_shift($carrier_product_service) : "";
								if($carrier){
									$carrier_code = "coolrunner_$carrier";
								}
							}*/
							
							$create_shipment 	= Mage::helper('coolrunner')->getConfig('coolrunner/settings/create_shipment');
							$send_shipment_mail = Mage::helper('coolrunner')->getConfig('coolrunner/settings/send_shipment_mail');
							
							$shipment_collection = Mage::getResourceModel('sales/order_shipment_collection');
							$shipment_collection->addAttributeToFilter('order_id', $order->getId());
							if($shipment_collection->getSize() > 0)
							{
								foreach($shipment_collection as $sc) 
								{
								    $addTrack = true;
								    $shipment = Mage::getModel('sales/order_shipment');
								    $shipment->load($sc->getId());
	
								    foreach($shipment->getTracksCollection() as $track)
								    {
								    	if(is_array($package_number)) {
									    	foreach($package_number as $key => $pNo) {
										    	if($track->getTrackNumber() == $pNo || $track->getNumber() == $pNo) {
													unset($package_number[$key]);
												}
									    	}
									    	if(count($package_number) == 0) {
										    	$addTrack = false;
									    	}
								    	}
								    	else
								    	{
									    	if($track->getTrackNumber() == $package_number || $track->getNumber() == $package_number) {
												$addTrack = false;
									    		break;
											}	
								    	}
								    }   
								    if($addTrack)
								    {
									    if(is_array($package_number)) {
									    	foreach($package_number as $pNo) {
										    	Mage::getModel('sales/order_shipment_api')->addTrack($shipment->getIncrementId(), $carrier_code, $order->getShippingDescription(), $pNo);	
									    	}
								    	} else {
									    	Mage::getModel('sales/order_shipment_api')->addTrack($shipment->getIncrementId(), $carrier_code, $order->getShippingDescription(), $package_number);
								    	}
								    	
									    if($send_shipment_mail)
				                        {
					                        $shipment->save();
											$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipment->getIncrementId());
				                        	$shipment->sendEmail($send_shipment_mail, '');
				                        }
								    }    
								}
							}
							elseif($create_shipment && $order->canShip())
							{
			
								$shipmentIncrementId = Mage::getModel('sales/order_shipment_api')->create($order->getIncrementId());
								$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentIncrementId);
								$shipment->setEmailSent($send_shipment_mail);
								
		
		                        $transactionSave = Mage::getModel('core/resource_transaction')
		                            ->addObject($shipment)
		                            ->addObject($shipment->getOrder())
		                            ->save();
		                        
		                        if(is_array($package_number)) {
							    	foreach($package_number as $pNo) {
								    	Mage::getModel('sales/order_shipment_api')->addTrack($shipment->getIncrementId(), $carrier_code, $order->getShippingDescription(), $pNo);	
							    	}
						    	} else {
							    	Mage::getModel('sales/order_shipment_api')->addTrack($shipment->getIncrementId(), $carrier_code, $order->getShippingDescription(), $package_number);
						    	}
		                        
		                        if($send_shipment_mail)
		                        {
			                        $shipment->save();
			                        $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipment->getIncrementId());
		                        	$shipment->sendEmail($send_shipment_mail, '');
		                        }
							}
						}
						
						Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('coolrunner')->__('Label created for order #%s',$increment_id));
					}
				}
				
				mysql_close($connection);
			}
		} 
		
		if($filename && $params['download'] && count($pdf->pages)){
			if(count($pdf->pages) > 1){
				$filename = 'coolrunner-labels'."_".date("Y-m-d_H-i-s",Mage::getModel('core/date')->timestamp(time())).".pdf";
				$pdf_save_path = Mage::getBaseDir('var').DS."coolrunner".DS."pdf".DS;
				$fileIo = new Varien_Io_File();
				$fileIo->checkAndCreateFolder($pdf_save_path);
				$fileIo->open(array('path' => $pdf_save_path));
				$fileIo->write($filename, $pdf->render());				
			}
			
			$pdfLabelDownloadLink = Mage::helper('adminhtml')->getUrl('coolrunner/adminhtml_grid/downloadPdf',array('file'=>$filename));
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('coolrunner')->__('If download not start automatically, you can download your label here: <a href="%s" id="coolrunnerPdfLabelDownloadLink">Download</a>', $pdfLabelDownloadLink));
		}
		
		$this->_redirect('adminhtml/sales_order');
    }
    
    public function labelsForOrdersAction()
    {
	    $this->getResponse()->setHeader('Content-type', 'text/json; charset=UTF-8');
	    $params = $this->getRequest()->getParams();
	    if(isset($params['order_ids']) && is_array($params['order_ids']) && !empty($params['order_ids'])) {
		    $collection = Mage::getModel('coolrunner/order_pdf')->getCollection()
		    				->addFieldToSelect(array('order_id','filename'))
		    				->addFieldToFilter('order_id',array('in' => $params['order_ids']));
		    				
			$pdf_ids = array();
		    foreach($collection as $pdf) {
			    $pdf_ids[] = array('order_id' => $pdf->getOrderId(), 'pdf_id' => $pdf->getId(), 'pdf_filename' => $pdf->getFilename());
		    }				
			$this->getResponse()->setBody(json_encode($pdf_ids));
	    } else {
			$this->getResponse()->setBody(json_encode(false));    
	    }
    }
    
    public function orderinfoAction() {
	    $order_info = array();
	    
	    if($order_id = $this->getRequest()->getParam('order_id',0)) {
			$order = Mage::getModel('sales/order')->load($order_id);
			$order_info['shipping_method'] = $order->getData('shipping_method');
			$order_info['billing_address'] = $order->getBillingAddress()->getData();
			$order_info['shipping_address'] = $order->getShippingAddress()->getData();
			$order_info['items'] = array();
			foreach($order->getAllVisibleItems() as $item) {
				$order_info['items'][] = array('name' => $item->getName(), 'qty' => (int)$item->getQtyOrdered());
			}
			$this->getResponse()->setBody(json_encode($order_info));
	    } else {
			$this->getResponse()->setBody(json_encode(false));    
	    }
		
    }
    
    public function downloadPdfAction()
    {
	    if($pdf_id = $this->getRequest()->getParam('id',0)) {
		    
		    $pdfItem = Mage::getModel('coolrunner/order_pdf')->load($pdf_id);
		    $pdf_content = base64_decode($pdfItem->getPdfBase64());

			$pdf = new Zend_Pdf();
			$pdf = Zend_Pdf::parse($pdf_content);

			return $this->_prepareDownloadResponse($pdfItem->getFilename(), $pdf->render(), 'application/pdf');
		    		    
	    }
	    elseif($filename = $this->getRequest()->getParam('file',0)){
		    $pdf_read_path = Mage::getBaseDir('var').DS."coolrunner".DS."pdf".DS;
		    $fileIo = new Varien_Io_File();
		    $fileIo->open(array('path' => $pdf_read_path));
			if($pdfContent = $fileIo->read($filename)) {
				$pdf = new Zend_Pdf();
				$pdf = Zend_Pdf::parse($pdfContent);
				return $this->_prepareDownloadResponse($filename, $pdf->render(), 'application/pdf');
			}
	    }
	    else {
		    $this->_redirect('adminhtml/sales_order');
	    }
    }
    
    public function downloadPdfForOrdersAction()
    {
		$params = $this->getRequest()->getParams();
	    if(isset($params['order_ids']) && is_array($params['order_ids']) && !empty($params['order_ids'])) {
	        
	        $pdf = new Zend_Pdf();
	        
		    $collection = Mage::getModel('coolrunner/order_pdf')->getCollection()
		    				->addFieldToFilter('order_id',array('in' => $params['order_ids']));
		    				
		    foreach($collection as $pdfItem)
		    {
				$pdf_content = base64_decode($pdfItem->getPdfBase64());    
				$pdf_tmp = new Zend_Pdf();
				$pdf_tmp = Zend_Pdf::parse($pdf_content);
				
				foreach($pdf_tmp->pages as $page)
				{
					$pdf->pages[] = clone $page;					
				}			
		    }				

			$filename = "CoolRunner_Pdf_Labels".Mage::getModel('core/date')->date("Y-m-d_H-i-s").".pdf";
			return $this->_prepareDownloadResponse($filename, $pdf->render(), 'application/pdf');
		    		    
	    } else {
		    $this->_redirect('adminhtml/sales_order');
	    }
    }
    
}