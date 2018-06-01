<?php
class CoolRunner_CoolShipping_Model_Carrier_Posti extends CoolRunner_CoolShipping_Model_Carrier_Abstract
{
	protected $_code = 'coolrunner_posti';
	protected $_default_condition_name = 'package_value';
	protected $_is_tracking_available = false;
	protected $_tracking_title = 'Posti';

	public function __construct() {
		parent::__construct();	
	}
	
}