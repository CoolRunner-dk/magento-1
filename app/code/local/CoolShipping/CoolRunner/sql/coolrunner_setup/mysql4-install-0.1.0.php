<?php
$installer = $this;
$installer->startSetup();

try
{
	$installer->run("
		CREATE TABLE IF NOT EXISTS {$this->getTable(cm_coolrunner_sales_order_pdf)} (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `order_id` int(11) NOT NULL,
		  `filename` varchar(255) NOT NULL,
		  `package_number` varchar(255) DEFAULT NULL,
		  `pdf_base64` longtext,
		  `pdf_link` varchar(255) DEFAULT NULL,
		  `shipment_id` int(11) DEFAULT NULL,
		  `message` text,
		  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`),
		  KEY `order_id` (`order_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
	");
} catch(Exception $e) {
	Mage::log($e->getMessage(),null,'CoolShipping-coolrunner.log',true);
}

try
{
	$installer->run("
		CREATE TABLE IF NOT EXISTS {$this->getTable(cm_coolrunner_sales_order_info)} (
		  `quote_id` int(11) NOT NULL,
		  `order_id` int(11),
		  `droppoint` varchar(32),
		  `firstname` varchar(64),
		  `lastname` varchar(64),
		  `telephone` varchar(64),
		  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`quote_id`),
		  INDEX (`order_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
	");
} catch(Exception $e) {
	Mage::log($e->getMessage(),null,'CoolShipping-coolrunner.log',true);
}

$installer->endSetup();