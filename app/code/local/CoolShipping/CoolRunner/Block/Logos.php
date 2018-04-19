<?php
class CoolShipping_CoolRunner_Block_Logos extends CoolShipping_CoolRunner_Block_Droppoints_Abstract 
{
	public function getCarriers() {
		return array('pdk', 'gls', 'dao', 'dhl');
	}
	
}