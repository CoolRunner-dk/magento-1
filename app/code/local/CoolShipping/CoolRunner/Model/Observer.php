<?php

class CoolShipping_CoolRunner_Model_Observer
{
	public function appendDroppointHtml($observer)
	{
		$block = $observer->getEvent()->getBlock();
		if($block instanceof Mage_Checkout_Block_Onepage_Shipping_Method_Available || 
			$block instanceof AW_Onestepcheckout_Block_Onestep_Form_Shippingmethod)
		{
			$transport = $observer->getEvent()->getTransport();
			$html = $transport->getHtml();
			if(strpos($html, 'coolrunner_pdk_private_droppoint') !== false) {
				$html .= $block->getLayout()->createBlock('coolrunner/droppoints_pdk')->setTemplate('coolrunner/droppoints.phtml')->toHtml();
			}
			if(strpos($html, 'coolrunner_gls_private_droppoint') !== false) {
				$html .= $block->getLayout()->createBlock('coolrunner/droppoints_gls')->setTemplate('coolrunner/droppoints.phtml')->toHtml();
			}
			if(strpos($html, 'coolrunner_dao_private_droppoint') !== false) {
				$html .= $block->getLayout()->createBlock('coolrunner/droppoints_dao')->setTemplate('coolrunner/droppoints.phtml')->toHtml();
			}
                        if(strpos($html, 'coolrunner_dhl_private_droppoint') !== false) {
				$html .= $block->getLayout()->createBlock('coolrunner/droppoints_dhl')->setTemplate('coolrunner/droppoints.phtml')->toHtml();
			}
                        if(strpos($html, 'coolrunner_posti_private_droppoint') !== false) {
				$html .= $block->getLayout()->createBlock('coolrunner/droppoints_posti')->setTemplate('coolrunner/droppoints.phtml')->toHtml();
			}
			$html .= $block->getLayout()->createBlock('coolrunner/logos')->setTemplate('coolrunner/logos.phtml')->toHtml();
			$transport->setHtml($html);
		}
	}

	public function addMassAction($observer)
    {
        $block = $observer->getEvent()->getBlock();
        if(get_class($block) == 'Mage_Adminhtml_Block_Widget_Grid_Massaction' && strpos($block->getRequest()->getControllerName(),'sales_order') !== false && Mage::getStoreConfig('coolrunner/settings/active'))
        {    
			$block->addItem('coolrunner_start', array(
					'label' => '-- -- -- '.Mage::helper('coolrunner')->__('CoolRunner Shipping Labels').' -- -- --',
					'url'   => '',
				)
			);
			
	        if($data = Mage::getStoreConfig('coolrunner/package/size')) 
	        {
			    $data = unserialize($data);		    
			    if(is_array($data) && !empty($data))
			    {
				    foreach($data as $key => $value)
				    {
						$value['download'] = Mage::getStoreConfig('coolrunner/settings/download');
						$label = ($value['download']) ? Mage::helper('coolrunner')->__('Create Shipping Label: %s',$value['title']) : Mage::helper('coolrunner')->__('Create Shipping Label: %s',$value['title']);
				        $block->addItem('coolrunner_export'.$key, array(
				            'label'=> $label,
				            'url'  => Mage::helper('adminhtml')->getUrl('coolrunner/adminhtml_grid/process',$value),
				            'additional' => array('carrier_product_service' => array(
											            'name'  => 'carrier_product_service',
											            'type'  => 'select',
											            'class' => 'required-entry',
											            'label' => Mage::helper('coolrunner')->__("Type:"),
											            'values' => Mage::helper('coolrunner')->getCarrierProductServiceOptions($withLabels = true,$extraAuto = true),
					        )),
					    ));
				    }
			    }
	        }

	        
	        $block->addItem('coolrunner_download_labels', array(
	            'label'=> Mage::helper('coolrunner')->__('Download Shipping Labels'),
	            'url'  => Mage::helper('adminhtml')->getUrl('coolrunner/adminhtml_grid/downloadPdfForOrders',array()),
		        )
		    );
		    
		    $block->addItem('coolrunner_end', array(
					'label' => ' ',
					'url'   => '',
				)
			);	        
	        
	    }
	}
	
	public function saveQuoteData($observer)
	{
		$request = Mage::app()->getRequest();
		$quote  = $observer->getEvent()->getQuote();
		
		$shippingMethod = $quote->getShippingAddress()->getData('shipping_method');

		$prefix = "coolrunner";
		$carriers = array('dao',"pdk",'gls','dhl', 'coolrunner', 'posti'); 

		if(strpos($shippingMethod,$prefix."_") !== false) 
		{	
			foreach($carriers as $carrier) 
			{
				$code = $prefix."_".$carrier;
				if(strpos($shippingMethod,$code) !== false) 
				{
					$quote_id = $quote->getId();
						
					$firstname = trim($request->getPost($prefix."-".$carrier."-pickup-firstname",''));
					$lastname = trim($request->getPost($prefix."-".$carrier."-pickup-lastname",''));
					$telephone = trim($request->getPost($prefix."-".$carrier."-telephone",''));
					$droppoint = trim($request->getPost($prefix."-".$carrier.'-droppoint-id',''));
					
					$company = trim($request->getPost($prefix."-".$carrier."-droppoint-name",''));
					$city = trim($request->getPost($prefix."-".$carrier."-droppoint-city",''));
					$postcode = trim($request->getPost($prefix."-".$carrier."-droppoint-postalcode",''));
					$street = trim($request->getPost($prefix."-".$carrier."-droppoint-streetname",''));
					
					
					$resource = Mage::getSingleton('core/resource');
					$write = $resource->getConnection('core_write');
					$table = $resource->getTableName('cm_coolrunner_sales_order_info');
					$query = "INSERT INTO $table (quote_id,droppoint,firstname,lastname,telephone) 
								VALUES ($quote_id,'$droppoint','$firstname','$lastname','$telephone')
								ON DUPLICATE KEY UPDATE droppoint='$droppoint', firstname='$firstname', lastname='$lastname', telephone='$telephone'";
					
					$write->query($query);
					
					if(strpos($shippingMethod,'droppoint') !== false) {
						try{
							$quote_shipping_address = $quote->getShippingAddress()
									->setFirstname($firstname)
									->setLastname($lastname)
									->setCompany($company)
									->setStreet($street)
									->setPostcode($postcode)
									->setCity($city)
									->setTelephone($telephone)
									->setFax('');
						
							$table = Mage::getSingleton('core/resource')->getTableName('sales/quote_address');
							$write->query("UPDATE $table SET firstname='$firstname',lastname='$lastname',company='$company',street='$street',postcode='$postcode',city='$city',telephone='$telephone',fax='' WHERE quote_id='".$quote->getId()."' AND address_type='shipping'");
							$quote->setShippingAddress($quote_shipping_address);
							$quote->save();
						}
						catch(Exception $e) {
							Mage::log($e->getMessage(),null,'coolrunner.log',true);
						}
					}
				}
			}
		}
	}
	
	public function saveDataOnOrder($observer)
	{
		$quote = $observer->getEvent()->getQuote();
		$order = $observer->getEvent()->getOrder();
		
		$query_id = $quote->getId();
		$order_id = $order->getId();
		
		$resource = Mage::getSingleton('core/resource');
		$write = $resource->getConnection('core_write');
		$table = $resource->getTableName('cm_coolrunner_sales_order_info');
		$query = "UPDATE $table SET order_id='$order_id' WHERE quote_id='$query_id'";
		$write->query($query);
	}
	
	
}