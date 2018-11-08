<?php

class CoolRunner_CoolShipping_Helper_Logger {
    /**
     * @param string      $message
     * @param string|null $_
     */
    public function log($message, $_ = null) {
        $date = (new DateTime())->format('Y-m-d');
        $bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $at = "{$bt[0]['file']}:{$bt[0]['line']}";
        $msg = '';
        foreach (func_get_args() as $arg) {
            $msg .= "\n    $arg";
        }
        $msg = "$msg\n        $at\n";

        $enable_logger = Mage::getStoreConfig('coolrunner/information/debug_mode');
        $enable_logger = (bool)$enable_logger;
        if ($enable_logger) {
            Mage::log($msg, Zend_Log::DEBUG, "coolrunner-debug-{$date}.log", true);
        }
    }
}