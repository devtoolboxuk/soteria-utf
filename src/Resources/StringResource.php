<?php

namespace devtoolboxuk\soteriautf\Resources;

class StringResource extends Resources
{

    public function replace($string)
    {
        #7 bit
        $string = preg_replace('/[\x00-\x1F\x7F-\xFF]/', $this->_replacement, $string);

        #8 bit
        $string = preg_replace('/[\x00-\x1F\x7F]/', $this->_replacement, $string);

        #UTF 8
        $string = preg_replace('/[\x00-\x1F\x7F]/u', $this->_replacement, $string);

        return $string;
    }
}