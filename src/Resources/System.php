<?php

namespace devtoolboxuk\soteriautf\Resources;

class System
{

    public function mbstring_loaded()
    {
        return extension_loaded('mbstring');
    }

    public function mbstring_overloaded()
    {
        return defined('MB_OVERLOAD_STRING') && ((int)@ini_get('mbstring.func_overload') & \MB_OVERLOAD_STRING);
    }

    public function isPHPCompatible()
    {
        if (in_array(phpversion(), ['5.4.16', '5.4.45'])) {
            return false;
        }

        if (version_compare(PHP_VERSION, '5.6.0') >= 0) {
            return true;
        }
        return false;
    }


    public function iconv_loaded()
    {
        return extension_loaded('iconv');
    }

    public function intl_loaded()
    {
        return extension_loaded('intl');
    }

    public function intlChar_loaded()
    {
        return class_exists('IntlChar');
    }

    public function ctype_loaded()
    {
        return extension_loaded('ctype');
    }

    public function finfo_loaded()
    {
        return class_exists('finfo');
    }

    public function json_loaded()
    {
        return function_exists('json_decode');
    }

    public function pcre_utf8_support()
    {
        return (bool)@preg_match('//u', '');
    }


    public function symfony_polyfill_used()
    {
        // init
        $return = false;

        $returnTmp = extension_loaded('mbstring');
        if ($returnTmp === false && function_exists('mb_strlen')) {
            $return = true;
        }

        $returnTmp = extension_loaded('iconv');
        if ($returnTmp === false && function_exists('iconv')) {
            $return = true;
        }

        return $return;
    }

}