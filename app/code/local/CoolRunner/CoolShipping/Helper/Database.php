<?php

class CoolRunner_CoolShipping_Helper_Database
    extends Mage_Core_Helper_Abstract {

    /**
     * Get a certain CoolRunner config value
     *
     * @param string $key     Key of the config
     * @param mixed  $default If the config doesn't exists this is returned instead
     *
     * @return mixed Config value or $default on failure
     */
    public function get($key, $default = null) {
        Mage::helper('coolrunner/logger')->log('Fetching CoolRunner Config', $key, (string)$default);
        /** @var Mage_Core_Model_Resource $res */
        $res = Mage::getSingleton('core/resource');
        $read = $res->getConnection('core_read');
        $tablename = $res->getTableName('coolrunner_coolshipping_config_data');

        $select = $read->select()->from($tablename)->where('`key` = ?', $key);

        $result = $read->fetchRow($select);
        if (isset($result['value'])) {
            return unserialize($result['value']);
        }

        return $default;
    }

    public function set($key, $value) {
        Mage::helper('coolrunner/logger')->log('Fetching CoolRunner Config', $key);
        /** @var Mage_Core_Model_Resource $res */
        $res = Mage::getSingleton('core/resource');
        $write = $res->getConnection('core_write');
        $tablename = $res->getTableName('coolrunner_coolshipping_config_data');

        $data = array(
            'key' => $key,
            'value' => serialize($value)
        );

        $result = $write->insertOnDuplicate($tablename,$data,array('value'));
        if($result === 0) {
            return false;
        }

        return true;
    }
}