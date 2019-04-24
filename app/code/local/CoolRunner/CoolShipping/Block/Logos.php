<?php
class CoolRunner_CoolShipping_Block_Logos extends CoolRunner_CoolShipping_Block_Servicepoints_Abstract
{
	public function getCarriers() {
		return array('postnord', 'gls', 'dao', 'dhl',' bring');
	}
	
}