<?php

class CoolRunner_CoolShipping_Model_Apiv3
    extends Mage_Core_Model_Abstract {
    protected        $_api;
    protected static $_autoloaded = false;

    /**
     * @param $email
     * @param $token
     *
     * @return \CoolRunnerSDK\API
     */
    public function loadAPI($store_id) {
        $this->__autoload();

        $email = Mage::helper('coolrunner')->getConfig('coolrunner/settings/email', $store_id);
        $token = Mage::helper('coolrunner')->getConfig('coolrunner/settings/token', $store_id);

        Mage::helper('coolrunner/logger')->log('Loaded APIv3');
        return \CoolRunnerSDK\API::load($email, $token, 'CoolRunner CoolShipping Magento 1 Module');
    }

    public function autoload() {
        $this->__autoload();
    }

    protected function __autoload() {
        if (!self::$_autoloaded) {
            spl_autoload_register(function ($class_name) {
                if (strpos($class_name, 'CoolRunnerSDK') !== false && !class_exists($class_name)) {
                    $sdk_src = Mage::getBaseDir('base') . '/app/code/local/CoolRunner/CoolShipping/vendor/coolrunner/php-sdk/src/';
                    $class_name = str_replace('CoolRunnerSDK', '', $class_name);
                    $fn = implode(DIRECTORY_SEPARATOR, array_filter(explode('\\', $class_name . '.php')));
                    $fn = preg_replace('/\\+|\/+/i', DIRECTORY_SEPARATOR, $fn);

                    require_once "{$sdk_src}{$fn}";
                }
            }, true, true);

            self::$_autoloaded = true;
        }
    }
}
