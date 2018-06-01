<?php
class CoolRunner_CoolShipping_Model_Carrier_Gls extends CoolRunner_CoolShipping_Model_Carrier_Abstract
{
	protected $_code = 'coolrunner_gls';
	protected $_default_condition_name = 'package_value';
	protected $_is_tracking_available = true;
	protected $_tracking_title = 'GLS';

	public function __construct() {
		parent::__construct();	
	}
	
}