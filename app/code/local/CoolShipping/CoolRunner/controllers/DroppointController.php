<?php
class CoolShipping_CoolRunner_DroppointController extends Mage_Core_Controller_Front_Action
{
	public function getDroppointsFromPostalCodeAction()
	{
		$this->getResponse()->setHeader('Content-type', 'text/json; charset=UTF-8');
		$params = $this->getRequest()->getParams();

		if(isset($params['postalCode']) && $params['postalCode'] && isset($params['countryCode']) && $params['countryCode'] && isset($params['carrier']) && $params['carrier'])
		{	
			$street = "";
                        $city = "";
			$quote = Mage::getSingleton('checkout/session')->getQuote();
			$billingAddress = $quote->getBillingAddress();
			if($billingAddress->getPostcode() == $params['postalCode']) {
				$street = $billingAddress->getStreet();
				$street = array_shift($street);
                                $city = $billingAddress->getCity();
			}
			$result = Mage::getModel('coolrunner/droppoint')->getDroppointJson($params['carrier'],$params['postalCode'],$params['countryCode'],$street, $city);
			
                        $this->getResponse()->setBody($result);
		}
		else
		{
			$result = json_encode($params); 
			$this->getResponse()->setBody($result);
		}
	}
	
	public function updateAction()
	{
		//$event = new Varien_Object();
		//$quote = Mage::getModel('checkout/cart')->getQuote();
		//$event->setQuote($quote);
		//Mage::getModel("coolrunner/observer")->saveQuoteData($event);
	}
	
	
	
	
}