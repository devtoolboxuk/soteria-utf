<?php

namespace devtoolboxuk\soteriautf\Resources;

class Attributes extends Resources
{
    private $_evil_attributes_regex;

    function __construct()
    {
        $evil = new Evil();
        $this->_evil_attributes_regex = $evil->regEx();
    }

    public function removeEvilAttributes($str)
    {
        // replace style-attribute, first (if needed)
        if (stripos($str, 'style') !== false && in_array('style', $this->_evil_attributes_regex, true)) {
            do {
                $count = $temp_count = 0;

                $str = (string)preg_replace('/(<[^>]+)(?<!\p{L})(style\s*=\s*"(?:[^"]*?)"|style\s*=\s*\'(?:[^\']*?)\')/iu', '$1' . $this->_replacement, $str, -1, $temp_count);
                $count += $temp_count;
            } while ($count);
        }

        $evil_attributes_string = implode('|', $this->_evil_attributes_regex);

        do {
            $count = $temp_count = 0;

            // find occurrences of illegal attribute strings with and without quotes (042 ["] and 047 ['] are octal quotes)
            $str = (string)preg_replace('/(.*)((?:<[^>]+)(?<!\p{L}))(?:' . $evil_attributes_string . ')(?:\s*=\s*)(?:(?:\'|\047)(?:.*?)(?:\'|\047)|(?:"|\042)(?:.*?)(?:"|\042))(.*)/ius', '$1$2' . $this->_replacement . '$3$4', $str, -1, $temp_count);
            $count += $temp_count;

            $str = (string)\preg_replace('/(.*)(<[^>]+)(?<!\p{L})(?:' . $evil_attributes_string . ')\s*=\s*(?:[^\s>]*)(.*)/ius', '$1$2' . $this->_replacement . '$3', $str, -1, $temp_count);
            $count += $temp_count;
        } while ($count);

        return (string)$str;
    }

}