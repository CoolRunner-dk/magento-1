<?php
class CoolShipping_CoolRunner_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getUrlContent($url)
	{
		$content = "";
		if(function_exists('curl_version')) {
      		$info = curl_init($url);
      		
      		if($info === false){
    			throw new Exception('Something went wrong!');
    			return '';
    		}
    		
    		curl_setopt($info, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($info, CURLOPT_HEADER, 0);
    		curl_setopt($info, CURLOPT_NOSIGNAL, 1); // et krav ved brug af CURLOPT_TIMEOUT_MS
    		curl_setopt($info, CURLOPT_TIMEOUT_MS, 1000);
    		
    		$content = curl_exec($info);
    		
    		$httpCode = curl_getinfo($info, CURLINFO_HTTP_CODE);
			$curl_errno = curl_errno($info);
    		curl_close($info);
    		
    		if($httpCode != '200' || $curl_errno > 0){ return ''; }
    		
    		if($content === false || $content == ''){
    			throw new Exception('Something went wrong!');
    			return '';
    		}
		} else {
			try {
	    		$content = file_get_contents($url);
	    	} catch(Exception $e) {
		    	Mage::log($e->getMessage(),null,'coolrunner.log',true);
	    	}
		}
		return $content;
	}
	
	public function getConfig($path,$storeId = false) 
	{
		if($storeId !== false) 
		{
			return Mage::getStoreConfig($path,$storeId);	
		}
		elseif($storecode = Mage::app()->getRequest()->getParam('store')) 
		{
		    $storeCollection = Mage::getModel('core/store')->getCollection()->addFieldToFilter('code', $storecode);        
		    $storeId = $storeCollection->getFirstItem()->getStoreId();
		    return Mage::getStoreConfig($path,$storeId);
		} 
		elseif($websitecode = Mage::app()->getRequest()->getParam('website')) 
		{
			$websiteCollection = Mage::getModel('core/website')->getCollection()->addFieldToFilter('code', $websitecode);
			$websiteId = $websiteCollection->getFirstItem()->getWebsiteId();
		    return Mage::app()->getWebsite($websiteId)->getConfig($path);
		} 		
		return Mage::getStoreConfig($path);
	}
	
	public function getCarrierProductServiceOptions($withLabels = false,$extraAuto = false)
	{
		$options = array();
		
		if($pricelist = Mage::helper('coolrunner')->getConfig('coolrunner/pricelist/list')) 
	    {
		    $rates = unserialize(Mage::getStoreConfig('coolrunner/rates/carrier_options'));
			if($extraAuto && empty($rates)){
				$extraAuto = false;
			}
			
		    if($extraAuto) {
			    $options['auto'] = array('value' => 'auto','label' => Mage::helper('coolrunner')->__("Customers selected shipping method"));			    
		    }

			$pricelist = unserialize($pricelist);
			foreach($pricelist as $zone_to => $rates) {
				foreach($rates as $rate) {
					$carrier_product_service = trim(implode("_",array($rate['carrier'],$rate['carrier_product'],$rate['carrier_service'])),"_");
					$carrier_product_service_title = trim($rate['title']);
					if($withLabels) {
						$options[$carrier_product_service] = array('value' => $carrier_product_service,
																	'label' => $carrier_product_service_title
																	);
					} else {
						$options[$carrier_product_service] = $carrier_product_service;
					}
						
				}					
			}
	    }
		
		return $options;
	}
	
}