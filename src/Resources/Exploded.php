<?php

namespace devtoolboxuk\soteriautf\Resources;

class Exploded extends Resources
{
    private $_exploded_words = [
        'javascript',
        '<script',
        '</script>',
        'base64',
        'document',
        'eval',
    ];

    public function compactExplodedString($str)
    {
        static $WORDS_CACHE;
        $WORDS_CACHE['chunk'] = [];
        $WORDS_CACHE['split'] = [];

        // check if we need to perform the regex-stuff
        if (strlen($str) <= 30) {
            $useStrPos = true;
        } else {
            $useStrPos = false;
        }

        foreach ($this->words() as $word) {
            if (!isset($WORDS_CACHE['chunk'][$word])) {
                $regex = $this->_spacing_regex;
                $WORDS_CACHE['chunk'][$word] = substr(
                    chunk_split($word, 1, $regex),
                    0,
                    -strlen($regex)
                );

                $WORDS_CACHE['split'][$word] = str_split($word, 1);
            }

            if ($useStrPos) {
                foreach ($WORDS_CACHE['split'][$word] as $charTmp) {
                    if (stripos($str, $charTmp) === false) {
                        continue 2;
                    }
                }
            }

            // We only want to do this when it is followed by a non-word character.
            // And if there are no char at the start of the string.
            //
            // That way valid stuff like "dealer to!" does not become "dealerto".

            $regex = '#(?<before>[^\p{L}]|^)(?<word>' . str_replace(['#', '.'], ['\#', '\.'], $WORDS_CACHE['chunk'][$word]) . ')(?<after>[^\p{L}|@|.|!|?| ]|$)#ius';
            $str = (string)preg_replace_callback(
                $regex,
                function ($matches) {
                    return $this->compactExplodedWordsCallback($matches);
                },
                $str
            );
        }

        return $str;
    }

    private function words()
    {
        return $this->_exploded_words;
    }

    private function compactExplodedWordsCallback($matches)
    {
        return $matches['before'] . preg_replace('/' . $this->_spacing_regex . '/is', '', $matches['word']) . $matches['after'];
    }
}