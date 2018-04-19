<?php

class CoolShipping_CoolRunner_Model_Droppoint extends Mage_Core_Model_Abstract
{

    protected $_pathname = "";
    protected $_use_cache = false;
    protected $_cache_type = "";
    protected $_cache_ttl = 86400;
    protected $_prefix = 'coolrunner_droppoints';

    public function __construct()
    {
        $this->_pathname = Mage::getBaseDir('var') . DS . $this->_prefix;
        $this->_use_cache = Mage::getStoreConfig('coolrunner/droppoints/use_cache') ? true : false;
        $this->_cache_type = Mage::getStoreConfig('coolrunner/droppoints/cache_type') ? Mage::getStoreConfig('coolrunner/droppoints/cache_type') : false;
        $this->_cache_ttl = Mage::getStoreConfig('coolrunner/droppoints/cache_ttl') ? Mage::getStoreConfig('coolrunner/droppoints/cache_ttl') : 24 * 60 * 60;
    }

    public function getDroppointJson($carrier = false, $postalcode = false, $countrycode = false, $street = "", $city = "")
    {
        $postalcode = str_replace(" ", "", $postalcode);
        if (!$postalcode) {
            return json_encode(array());
        }
        if (!$countrycode) {
            return json_encode(array());
        }
        if (!$carrier) {
            return json_encode(array());
        }

        if ($this->_use_cache && $json = $this->getLocalDroppointResponse($carrier, $postalcode, $countrycode, $street, $city)) {
            return $json;
        } else {
            return $this->getRemoteDroppointResponse($carrier, $postalcode, $countrycode, $street, $city);
        }
    }

    private function getRemoteDroppointResponse($carrier, $postalcode, $countrycode, $street = "", $city)
    {
        $content = $this->_getRemoteDroppointResponse($carrier, $postalcode, $countrycode, $street, $city);

        if ($content && json_decode($content)) {
            if ($this->_use_cache && $this->_cache_type == 'files') {
                $fileIo = new Varien_Io_File();
                $fileIo->checkAndCreateFolder($this->_pathname);
                $filename = $this->_pathname . "/" . $carrier . "_" . $countrycode . "_" . $postalcode . "_" . $street . ".json";
                file_put_contents($filename, $content);
            } elseif ($this->_use_cache && $this->_cache_type == 'cache') {
                $cachename = $this->_prefix . "_" . $carrier . "_" . $countrycode . "_" . $postalcode . "_" . $street;
                $cache = Mage::getSingleton('core/cache');
                $cache->save($content, $cachename, array(Mage_Core_Model_Config::CACHE_TAG), $this->_cache_ttl);
            }
        }

        return $content;
    }

    private function _getRemoteDroppointResponse($carrier, $postcode, $country_code, $street = "", $city = "")
    {
        $content = false;
        $number_of_droppoints = ($street) ? 10 : 10;
        $url = false;
        $carrier_code = "";
        if ($carrier == "coolrunner_pdk") {
            $url = "https://api.coolrunner.dk/v2/droppoints/";
            $carrier_code = "pdk";
        } elseif ($carrier == "coolrunner_gls") {
            $url = "https://api.coolrunner.dk/v2/droppoints/";
            $carrier_code = "gls";
        } elseif ($carrier == "coolrunner_dao") {
            $url = "https://api.coolrunner.dk/v2/droppoints/";
            $carrier_code = "dao";
        } elseif ($carrier == "coolrunner_posti") {
            $url = "https://api.coolrunner.dk/v2/droppoints/";
            $carrier_code = "posti";
        } elseif ($carrier == "coolrunner_dhl") {
            $url = "https://api.coolrunner.dk/v2/droppoints/";
            $carrier_code = "dhl";
        } else {
            $url = "https://api.coolrunner.dk/v2/droppoints/";
            $carrier_code = "coolrunner";
        }

        $email = Mage::getStoreConfig('coolrunner/settings/email');
        $token = Mage::getStoreConfig('coolrunner/settings/token');

        $params = array(
            "carrier" => $carrier_code,
            "country_code" => $country_code,
            "zipcode" => $postcode,
            "street" => $street,
            "city" => $city,
            "number_of_droppoints" => $number_of_droppoints,
        );



        if (function_exists('curl_version')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_USERPWD, "$email:$token");
            $content = utf8_encode(curl_exec($ch));
            curl_close($ch);
        } else {
            $content = utf8_encode(file_get_contents($url));
        }

        return $content;
    }

    private function getLocalDroppointResponse($carrier, $postalcode, $countrycode, $street = "")
    {
        if ($this->_use_cache) {
            if ($this->_cache_type == 'files') {
                if ($this->createDroppointResponseFolder()) {
                    $filename = $this->_pathname . "/" . $carrier . "_" . $countrycode . "_" . $postalcode . "_" . $street . ".json";
                    $timeToLive = $this->_cache_ttl;
                    if (file_exists($filename) && (time() - filectime($filename) < $timeToLive)) {
                        return file_get_contents($filename);
                    }
                }
            } elseif ($this->_cache_type == 'cache') {
                $cachename = $this->_prefix . "_" . $carrier . "_" . $countrycode . "_" . $postalcode . "_" . $street;
                $cache = Mage::getSingleton('core/cache');

                if ($json = $cache->load($cachename)) {
                    return $json;
                }
            }
        }
        return false;
    }

    private function createDroppointResponseFolder()
    {
        $file = new Varien_Io_File();
        return $file->mkdir($this->_pathname);
    }

}
