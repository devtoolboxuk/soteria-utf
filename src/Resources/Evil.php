<?php

namespace devtoolboxuk\soteriautf\Resources;

class Evil extends Resources
{

    private $_evil_attributes_regex = [
        'on\w*',
        'style',
        'xmlns:xdp',
        'formaction',
        'form',
        'xlink:href',
        'seekSegmentTime',
        'FSCommand',
    ];


    private $_evil_html_tags = [
        'applet',
        'alert',
        'audio',
        'basefont',
        'base',
        'behavior',
        'bgsound',
        'blink',
        'body',
        'embed',
        'eval',
        'expression',
        'form',
        'frameset',
        'frame',
        'head',
        'html',
        'ilayer',
        'iframe',
        'input',
        'button',
        'select',
        'isindex',
        'layer',
        'link',
        'meta',
        'keygen',
        'object',
        'plaintext',
        'style',
        'script',
        'textarea',
        'title',
        'math',
        'video',
        'source',
        'svg',
        'xml',
    ];

    public function regEx()
    {
        return $this->_evil_attributes_regex;
    }

    public function html()
    {
        return $this->_evil_html_tags;
    }
}