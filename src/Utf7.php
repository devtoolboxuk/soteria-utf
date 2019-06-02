<?php

namespace devtoolboxuk\soteriautf;

class Utf7 extends Resources
{

    public function repack($str)
    {
        return (string)preg_replace_callback(
            '#\+([\\p{L}0-9]+)\-#ui',
            function ($matches) {
                return $this->callback($matches);
            },
            $str
        );
    }


    private function callback(array $strings)
    {
        $strTmp = base64_decode($strings[1], true);

        if ($strTmp === false) {
            return $strings[0];
        }

        if (rtrim(base64_encode($strTmp), '=') !== rtrim($strings[1], '=')) {
            return $strings[0];
        }

        $string = (string)preg_replace_callback(
            '/^((?:\x00.)*?)((?:[^\x00].)+)/us',
            function ($matches) {
                return $matches[1] . '+' . rtrim(base64_encode($matches[2]), '=') . '-';
            },
            $strTmp
        );

        return (string)preg_replace('/\x00(.)/us', '$1', $string);
    }

}