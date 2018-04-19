<?php
class CoolShipping_CoolRunner_Block_Droppoints_Abstract extends Mage_Checkout_Block_Onepage_Abstract
{
	protected $_js_model = 'CoolRunner';	

	public function getPostnr(){

		if(isset($_SESSION[$this->_code.'-postnr'])){
			$this->postnr = $_SESSION[$this->_code.'-postnr'];
		}
		elseif(is_null($this->postnr)){
			$this->postnr = ($this->getCheckout()->getQuote()->getShippingAddress()->getData('postcode') != "-") ? $this->getCheckout()->getQuote()->getShippingAddress()->getData('postcode') : "";
		}
		return $this->postnr;
	}
	
	public function getPickupFirstname(){

		if(isset($_SESSION[$this->_code.'-pickup-firstname'])){
			$this->pickup_firstname = $_SESSION[$this->_code.'-pickup-firstname'];
		}
		elseif(is_null($this->pickup_firstname)){
			$this->pickup_firstname = $this->getCheckout()->getQuote()->getShippingAddress()->getData('firstname');
		}
		return $this->pickup_firstname;
	}
	
	public function getPickupLastname(){

		if(isset($_SESSION[$this->_code.'-pickup-lastname'])){
			$this->pickup_lastname = $_SESSION[$this->_code.'-pickup-lastname'];
		}
		elseif(is_null($this->pickup_lastname)){
			$this->pickup_lastname = $this->getCheckout()->getQuote()->getShippingAddress()->getData('lastname');
		}
		return $this->pickup_lastname;
	}
	
	public function getTelephone(){

		if(isset($_SESSION[$this->_code.'-telephone'])){
			$this->telephone = $_SESSION[$this->_code.'-telephone'];
		}
		elseif(is_null($this->telephone)){
			$this->telephone = $this->getCheckout()->getQuote()->getBillingAddress()->getData('telephone');
		}
		return $this->telephone;
	}
	
	
	public function getJsModel()
	{
		return $this->_js_model."('".$this->_code."')";
	}
	
	public function getCode()
	{
		return $this->_code;
	}
}