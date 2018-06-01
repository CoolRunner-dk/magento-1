<?php
class CoolRunner_CoolShipping_Model_Carrier_Dao extends CoolRunner_CoolShipping_Model_Carrier_Abstract
{
	protected $_code = 'coolrunner_dao';
	protected $_default_condition_name = 'package_value';
	protected $_is_tracking_available = true;
	protected $_tracking_title = 'DAO';

	public function __construct() {
		parent::__construct();	
	}
	
}