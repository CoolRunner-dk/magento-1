<?php
class CoolShipping_CoolRunner_Model_Carrier_Coolrunner extends CoolShipping_CoolRunner_Model_Carrier_Abstract
{
	protected $_code = 'coolrunner_coolrunner';
	protected $_default_condition_name = 'package_value';
	protected $_is_tracking_available = false;

	public function __construct() {
		parent::__construct();	
	}
	
}