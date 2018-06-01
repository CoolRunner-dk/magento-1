<?php
class CoolRunner_CoolShipping_Model_Adminhtml_System_Config_Source_PrintSize
{
	public function toOptionArray() 
	{
		$print_sizes = array();
		
		$print_sizes[] = array(
						'value' => 'A4',
						'label' => Mage::helper('coolrunner')->__('A4 Format')
					);

		$print_sizes[] = array(
						'value' => 'LabelPrint',
						'label' => Mage::helper('coolrunner')->__('Label Format')
					);
		
		return $print_sizes;
	}
}