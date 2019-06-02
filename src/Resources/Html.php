<?php

namespace devtoolboxuk\soteriautf\Resources;

class Html extends Resources
{

    private $_evil_html_tags;

    function __construct()
    {
        $evil = new Evil();
        $this->_evil_html_tags = $evil->html();
    }

    public function naughtyHtml($str)
    {
        $evil_html_tags = implode('|', $this->_evil_html_tags);


        $str = (string)preg_replace_callback(
            '#<(?<start>/*\s*)(?<content>' . $evil_html_tags . ')(?<end>[^><]*)(?<rest>[><]*)#ius',
            function ($matches) {
                return $this->naughtyHtmlCallback($matches);
            },
            $str
        );

        return (string)$str;
    }

    private function naughtyHtmlCallback(array $matches)
    {
        return '&lt;' . $matches['start'] . $matches['content'] . $matches['end'] // encode opening brace
            // encode captured opening or closing brace to prevent recursive vectors:
            . str_replace(
                [
                    '>',
                    '<',
                ],
                [
                    '&gt;',
                    '&lt;',
                ],
                $matches['rest']
            );
    }

}