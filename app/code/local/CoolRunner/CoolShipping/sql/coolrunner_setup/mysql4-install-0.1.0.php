<?php

$installer = $this;
$installer->startSetup();

error_log('Installer run');

try {
    $installer->run("
		CREATE TABLE IF NOT EXISTS {$this->getTable('coolrunner_coolshipping_sales_order_pdf')} (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `order_id` INT(11) NOT NULL,
            `filename` VARCHAR(255) NOT NULL,
            `package_number` VARCHAR(255) NULL DEFAULT NULL,
            `pdf_base64` LONGTEXT NULL,
            `pdf_link` VARCHAR(255) NULL DEFAULT NULL,
            `shipment_id` INT(11) NULL DEFAULT NULL,
            `message` TEXT NULL,
            `excl_tax` FLOAT NULL DEFAULT NULL,
            `incl_tax` FLOAT NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `order_id` (`order_id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
	");
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'CoolShipping-coolrunner.log', true);
}

try {
    $installer->run("
		CREATE TABLE IF NOT EXISTS {$this->getTable('coolrunner_coolshipping_config_data')} (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(255) NOT NULL,
            `value` MEDIUMTEXT NULL,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `key` (`key`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
	");
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'CoolShipping-coolrunner.log', true);
}

try {
    $installer->run("
		CREATE TABLE IF NOT EXISTS {$this->getTable('coolrunner_coolshipping_sales_order_info')} (
            `quote_id` INT(11) NOT NULL,
            `order_id` INT(11) NULL DEFAULT NULL,
            `carrier` VARCHAR(10) NULL DEFAULT NULL,
            `servicepoint` VARCHAR(32) NULL DEFAULT NULL,
            `firstname` VARCHAR(64) NULL DEFAULT NULL,
            `lastname` VARCHAR(64) NULL DEFAULT NULL,
            `telephone` VARCHAR(64) NULL DEFAULT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`quote_id`),
            INDEX `order_id` (`order_id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
	");
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'CoolShipping-coolrunner.log', true);
}

$installer->endSetup();