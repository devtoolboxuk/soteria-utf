<?php

namespace devtoolboxuk\soteriautf\Resources;

class JavaScript extends Resources
{
    private $ANTI_XSS_STYLE = 'anti-xss::STYLE';

    public function removeDisallowedJavascript($str)
    {
        do {
            $original = $str;

            if (stripos($str, '<a') !== false) {
                $str = (string)preg_replace_callback(
                    '#<a[^a-z0-9>]+([^>]*?)(?:>|$)#i',
                    function ($matches) {
                        return $this->jsLinkRemovalCallback($matches);
                    },
                    $str
                );
            }

            if (stripos($str, '<img') !== false) {
                $str = (string)preg_replace_callback(
                    '#<img[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#i',
                    function ($matches) {
                        return $this->jsSrcRemovalCallback($matches);
                    },
                    $str
                );
            }

            if (stripos($str, '<audio') !== false) {
                $str = (string)preg_replace_callback(
                    '#<audio[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#i',
                    function ($matches) {
                        return $this->jsSrcRemovalCallback($matches);
                    },
                    $str
                );
            }

            if (stripos($str, '<video') !== false) {
                $str = (string)preg_replace_callback(
                    '#<video[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#i',
                    function ($matches) {
                        return $this->jsSrcRemovalCallback($matches);
                    },
                    $str
                );
            }

            if (stripos($str, '<source') !== false) {
                $str = (string)preg_replace_callback(
                    '#<source[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#i',
                    function ($matches) {
                        return $this->jsSrcRemovalCallback($matches);
                    },
                    $str
                );
            }

            if (stripos($str, 'script') !== false) {
                // US-ASCII: ¼ === <
                $str = (string)preg_replace(
                    '#(?:¼|<)/*(?:script).*(?:¾|>)#isuU',
                    $this->_replacement,
                    $str
                );
            }
        } while ($original !== $str);

        return (string)$str;
    }

    private function jsLinkRemovalCallback(array $match)
    {
        return $this->jsRemovalCallback($match, 'href');
    }

    private function jsRemovalCallback(array $match, $search)
    {
        if (!$match[0]) {
            return '';
        }

        // init
        $match_style_matched = false;
        $match_style = [];

        // hack for style attributes v1
        if ($search === 'href' && stripos($match[0], 'style') !== false) {
            preg_match('/style=".*?"/i', $match[0], $match_style);
            $match_style_matched = (count($match_style) > 0);
            if ($match_style_matched) {
                $match[0] = str_replace($match_style[0], $this->ANTI_XSS_STYLE, $match[0]);
            }
        }

        $replacer = $this->_filter_attributes(str_replace(['<', '>'], '', $match[1]));

        // filter for "(.*)" but only in the "$search"-attribute
        if (stripos($replacer, $search) !== false) {
            $pattern = '#' . $search . '=(?<wrapper>(?:\'|\047)|(?:"|\042)).*(?:\g{wrapper})#isU';
            $matchInner = [];
            $foundSomethingBad = false;
            preg_match($pattern, $match[1], $matchInner);
            if (count($matchInner) > 0 && preg_match('#(?:\(.*([^\)]*?)(?:\)))#s', $matchInner[0])) {
                $foundSomethingBad = true;

                $replacer = (string)preg_replace($pattern, $search . '="' . $this->_replacement . '"', $replacer);
            }

            if (!$foundSomethingBad) {
                // filter for javascript
                $pattern = '#' . $search . '=.*(?:javascript:|view-source:|livescript:|wscript:|vbscript:|mocha:|charset=|window\.|\(?document\)?\.|\.cookie|<script|d\s*a\s*t\s*a\s*:)#ius';
                $matchInner = [];
                preg_match($pattern, $match[1], $matchInner);
                if (count($matchInner) > 0) {
                    $replacer = (string)preg_replace($pattern, $search . '="' . $this->_replacement . '"', $replacer);
                }
            }
        }

        $return = str_ireplace($match[1], $replacer, (string)$match[0]);

        // hack for style attributes v2
        if ($match_style_matched && $search === 'href') {
            $return = str_replace($this->ANTI_XSS_STYLE, $match_style[0], $return);
        }

        return $return;
    }

    private function jsSrcRemovalCallback(array $match)
    {
        return $this->jsRemovalCallback($match, 'src');
    }

    public function naughtyJavascript($str)
    {
        $str = (string) preg_replace(
            '#(alert|eval|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*)\)#uisU',
            '\\1\\2&#40;\\3&#41;',
            $str
        );
        return (string)$str;
    }

}