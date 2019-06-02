<?php

namespace devtoolboxuk\soteriautf;

use devtoolboxuk\soteriautf\Resources\Resources;

class Utf8 extends Resources
{

    private $system;
    private $ENCODINGS;
    private $_supported = [];
    private $BROKEN_UTF8_FIX;
    private $ORD;
    private $CHR;
    private $WIN1252_TO_UTF8;

    private $BOM = [
        "\xef\xbb\xbf" => 3, // UTF-8 BOM
        'ï»¿' => 6, // UTF-8 BOM as "WINDOWS-1252" (one char has [maybe] more then one byte ...)
        "\x00\x00\xfe\xff" => 4, // UTF-32 (BE) BOM
        '  þÿ' => 6, // UTF-32 (BE) BOM as "WINDOWS-1252"
        "\xff\xfe\x00\x00" => 4, // UTF-32 (LE) BOM
        'ÿþ  ' => 6, // UTF-32 (LE) BOM as "WINDOWS-1252"
        "\xfe\xff" => 2, // UTF-16 (BE) BOM
        'þÿ' => 4, // UTF-16 (BE) BOM as "WINDOWS-1252"
        "\xff\xfe" => 2, // UTF-16 (LE) BOM
        'ÿþ' => 4, // UTF-16 (LE) BOM as "WINDOWS-1252"
    ];

    private $BIDI_UNI_CODE_CONTROLS_TABLE = [
        // LEFT-TO-RIGHT EMBEDDING (use -> dir = "ltr")
        8234 => "\xE2\x80\xAA",
        // RIGHT-TO-LEFT EMBEDDING (use -> dir = "rtl")
        8235 => "\xE2\x80\xAB",
        // POP DIRECTIONAL FORMATTING // (use -> </bdo>)
        8236 => "\xE2\x80\xAC",
        // LEFT-TO-RIGHT OVERRIDE // (use -> <bdo dir = "ltr">)
        8237 => "\xE2\x80\xAD",
        // RIGHT-TO-LEFT OVERRIDE // (use -> <bdo dir = "rtl">)
        8238 => "\xE2\x80\xAE",
        // LEFT-TO-RIGHT ISOLATE // (use -> dir = "ltr")
        8294 => "\xE2\x81\xA6",
        // RIGHT-TO-LEFT ISOLATE // (use -> dir = "rtl")
        8295 => "\xE2\x81\xA7",
        // FIRST STRONG ISOLATE // (use -> dir = "auto")
        8296 => "\xE2\x81\xA8",
        // POP DIRECTIONAL ISOLATE
        8297 => "\xE2\x81\xA9",
    ];

    /**
     * @var array
     */
    private $WHITESPACE_TABLE = [
        'SPACE' => "\x20",
        'NO-BREAK SPACE' => "\xc2\xa0",
        'OGHAM SPACE MARK' => "\xe1\x9a\x80",
        'EN QUAD' => "\xe2\x80\x80",
        'EM QUAD' => "\xe2\x80\x81",
        'EN SPACE' => "\xe2\x80\x82",
        'EM SPACE' => "\xe2\x80\x83",
        'THREE-PER-EM SPACE' => "\xe2\x80\x84",
        'FOUR-PER-EM SPACE' => "\xe2\x80\x85",
        'SIX-PER-EM SPACE' => "\xe2\x80\x86",
        'FIGURE SPACE' => "\xe2\x80\x87",
        'PUNCTUATION SPACE' => "\xe2\x80\x88",
        'THIN SPACE' => "\xe2\x80\x89",
        'HAIR SPACE' => "\xe2\x80\x8a",
        'LINE SEPARATOR' => "\xe2\x80\xa8",
        'PARAGRAPH SEPARATOR' => "\xe2\x80\xa9",
        'ZERO WIDTH SPACE' => "\xe2\x80\x8b",
        'NARROW NO-BREAK SPACE' => "\xe2\x80\xaf",
        'MEDIUM MATHEMATICAL SPACE' => "\xe2\x81\x9f",
        'IDEOGRAPHIC SPACE' => "\xe3\x80\x80",
    ];

    function __construct()
    {
        $this->system = new System();
        $this->checkForSupport();
    }

    private function checkForSupport()
    {
        if (!isset($this->_supported['already_checked_via_portable_utf8'])) {
            $this->_supported['already_checked_via_portable_utf8'] = true;

            // http://php.net/manual/en/book.mbstring.php
            $this->_supported['mbstring'] = $this->system->mbstring_loaded();
            $this->_supported['mbstring_func_overload'] = $this->system->mbstring_overloaded();
            if ($this->_supported['mbstring'] === true) {
                \mb_internal_encoding('UTF-8');
                /** @noinspection UnusedFunctionResultInspection */
                /** @noinspection PhpComposerExtensionStubsInspection */
                \mb_regex_encoding('UTF-8');
                $this->_supported['mbstring_internal_encoding'] = 'UTF-8';
            }

            // http://php.net/manual/en/book.iconv.php
            $this->_supported['iconv'] = $this->system->iconv_loaded();

            // http://php.net/manual/en/book.intl.php
            $this->_supported['intl'] = $this->system->intl_loaded();
            $this->_supported['intl__transliterator_list_ids'] = [];

            if (
                $this->_supported['intl'] === true
                &&
                \function_exists('transliterator_list_ids') === true
            ) {
                /** @noinspection PhpComposerExtensionStubsInspection */
                $this->_supported['intl__transliterator_list_ids'] = \transliterator_list_ids();
            }

            // http://php.net/manual/en/class.intlchar.php
            $this->_supported['intlChar'] = $this->system->intlChar_loaded();

            // http://php.net/manual/en/book.ctype.php
            $this->_supported['ctype'] = $this->system->ctype_loaded();

            // http://php.net/manual/en/class.finfo.php
            $this->_supported['finfo'] = $this->system->finfo_loaded();

            // http://php.net/manual/en/book.json.php
            $this->_supported['json'] = $this->system->json_loaded();

            // http://php.net/manual/en/book.pcre.php
            $this->_supported['pcre_utf8'] = $this->system->pcre_utf8_support();

            $this->_supported['symfony_polyfill_used'] = $this->system->symfony_polyfill_used();
            if ($this->_supported['symfony_polyfill_used'] === true) {
                \mb_internal_encoding('UTF-8');
                $this->_supported['mbstring_internal_encoding'] = 'UTF-8';
            }
        }
    }

    public function rawurldecode($str, $multi_decode = true)
    {
        if ($str === '') {
            return '';
        }

        if (strpos($str, '&') === false && strpos($str, '%') === false && strpos($str, '+') === false && strpos($str, '\u') === false) {
            return $this->fixSimpleUtf8($str);
        }

        $pattern = '/%u([0-9a-fA-F]{3,4})/';
        if (preg_match($pattern, $str)) {
            $str = (string)preg_replace($pattern, '&#x\\1;', rawurldecode($str));
        }

        $flags = \ENT_QUOTES | \ENT_HTML5;

        if ($multi_decode === true) {
            do {
                $str_compare = $str;

                /**
                 * @psalm-suppress PossiblyInvalidArgument
                 */
                $str = $this->fixSimpleUtf8(rawurldecode($this->htmlEntityDecode($this->toUtf8($str), $flags)));
            } while ($str_compare !== $str);
        }

        return $str;
    }

    private function fixSimpleUtf8($str)
    {
        if ($str === '') {
            return '';
        }

        static $BROKEN_UTF8_TO_UTF8_KEYS_CACHE = null;
        static $BROKEN_UTF8_TO_UTF8_VALUES_CACHE = null;

        if ($BROKEN_UTF8_TO_UTF8_KEYS_CACHE === null) {
            if ($this->BROKEN_UTF8_FIX === null) {
                $this->BROKEN_UTF8_FIX = $this->getData('utf8_fix');
            }

            $BROKEN_UTF8_TO_UTF8_KEYS_CACHE = array_keys($this->BROKEN_UTF8_FIX);
            $BROKEN_UTF8_TO_UTF8_VALUES_CACHE = array_values($this->BROKEN_UTF8_FIX);
        }

        return str_replace($BROKEN_UTF8_TO_UTF8_KEYS_CACHE, $BROKEN_UTF8_TO_UTF8_VALUES_CACHE, $str);
    }

    /**
     * @param $file
     * @return mixed
     */
    private function getData($file)
    {
        return include __DIR__ . '/../Data/' . $file . '.php';
    }

    /**
     * @param $str
     * @param null $flags
     * @param string $encoding
     * @return bool|false|string|string[]|null
     */
    private function htmlEntityDecode($str, $flags = null, $encoding = 'UTF-8')
    {
        if (
            !isset($str[3]) // examples: &; || &x;
            ||
            strpos($str, '&') === false // no "&"
        ) {
            return $str;
        }

        if ($encoding !== 'UTF-8' && $encoding !== 'CP850') {
            $encoding = $this->normalize_encoding($encoding, 'UTF-8');
        }

        if ($flags === null) {
            $flags = \ENT_QUOTES | \ENT_HTML5;
        }

        if ($encoding !== 'UTF-8' && $encoding !== 'ISO-8859-1' && $encoding !== 'WINDOWS-1252' && $this->_supported['mbstring'] === false) {
            trigger_error('UTF8::htmlEntityDecode() without mbstring cannot handle "' . $encoding . '" encoding', \E_USER_WARNING);
        }

        do {
            $str_compare = $str;

            // INFO: http://stackoverflow.com/questions/35854535/better-explanation-of-convmap-in-mb-encode-numericentity
            if ($this->_supported['mbstring'] === true) {
                if ($encoding === 'UTF-8') {
                    $str = mb_decode_numericentity($str, [0x80, 0xfffff, 0, 0xfffff, 0]);
                } else {
                    $str = mb_decode_numericentity($str, [0x80, 0xfffff, 0, 0xfffff, 0], $encoding);
                }
            } else {
                $str = (string)preg_replace_callback(
                    "/&#\d{2,6};/",
                    /**
                     * @param string[] $matches
                     *
                     * @return string
                     */
                    static function ($matches) use ($encoding) {
                        $returnTmp = \mb_convert_encoding($matches[0], $encoding, 'HTML-ENTITIES');
                        if ($returnTmp !== '"' && $returnTmp !== "'") {
                            return $returnTmp;
                        }

                        return $matches[0];
                    },
                    $str
                );
            }

            if (strpos($str, '&') !== false) {
                if (strpos($str, '&#') !== false) {
                    // decode also numeric & UTF16 two byte entities
                    $str = (string)preg_replace('/(&#(?:x0*[0-9a-fA-F]{2,6}(?![0-9a-fA-F;])|(?:0*\d{2,6}(?![0-9;]))))/S', '$1;', $str);
                }

                $str = html_entity_decode($str, $flags, $encoding);
            }
        } while ($str_compare !== $str);

        return $str;
    }

    /**
     * @param $encoding
     * @param string $fallback
     * @return mixed|string
     */
    private function normalize_encoding($encoding, $fallback = '')
    {
        static $STATIC_NORMALIZE_ENCODING_CACHE = [];

        // init
        $encoding = (string)$encoding;

        if (!$encoding) {
            return $fallback;
        }

        if ($encoding === 'UTF-8' || $encoding === 'UTF8') {
            return 'UTF-8';
        }

        if ($encoding === '8BIT' || $encoding === 'BINARY') {
            return 'CP850';
        }

        if ($encoding === 'HTML' || $encoding === 'HTML-ENTITIES') {
            return 'HTML-ENTITIES';
        }

        if (
            $encoding === '1' // only a fallback, for non "strict_types" usage ...
            ||
            $encoding === '0' // only a fallback, for non "strict_types" usage ...
        ) {
            return $fallback;
        }

        if (isset($STATIC_NORMALIZE_ENCODING_CACHE[$encoding])) {
            return $STATIC_NORMALIZE_ENCODING_CACHE[$encoding];
        }

        if ($this->ENCODINGS === null) {
            $this->ENCODINGS = $this->getData('encodings');
        }

        if (in_array($encoding, $this->ENCODINGS, true)) {
            $STATIC_NORMALIZE_ENCODING_CACHE[$encoding] = $encoding;

            return $encoding;
        }

        $encodingOrig = $encoding;
        $encoding = strtoupper($encoding);
        $encodingUpperHelper = (string)preg_replace('/[^a-zA-Z0-9\s]/u', '', $encoding);

        $equivalences = [
            'ISO8859' => 'ISO-8859-1',
            'ISO88591' => 'ISO-8859-1',
            'ISO' => 'ISO-8859-1',
            'LATIN' => 'ISO-8859-1',
            'LATIN1' => 'ISO-8859-1', // Western European
            'ISO88592' => 'ISO-8859-2',
            'LATIN2' => 'ISO-8859-2', // Central European
            'ISO88593' => 'ISO-8859-3',
            'LATIN3' => 'ISO-8859-3', // Southern European
            'ISO88594' => 'ISO-8859-4',
            'LATIN4' => 'ISO-8859-4', // Northern European
            'ISO88595' => 'ISO-8859-5',
            'ISO88596' => 'ISO-8859-6', // Greek
            'ISO88597' => 'ISO-8859-7',
            'ISO88598' => 'ISO-8859-8', // Hebrew
            'ISO88599' => 'ISO-8859-9',
            'LATIN5' => 'ISO-8859-9', // Turkish
            'ISO885911' => 'ISO-8859-11',
            'TIS620' => 'ISO-8859-11', // Thai
            'ISO885910' => 'ISO-8859-10',
            'LATIN6' => 'ISO-8859-10', // Nordic
            'ISO885913' => 'ISO-8859-13',
            'LATIN7' => 'ISO-8859-13', // Baltic
            'ISO885914' => 'ISO-8859-14',
            'LATIN8' => 'ISO-8859-14', // Celtic
            'ISO885915' => 'ISO-8859-15',
            'LATIN9' => 'ISO-8859-15', // Western European (with some extra chars e.g. €)
            'ISO885916' => 'ISO-8859-16',
            'LATIN10' => 'ISO-8859-16', // Southeast European
            'CP1250' => 'WINDOWS-1250',
            'WIN1250' => 'WINDOWS-1250',
            'WINDOWS1250' => 'WINDOWS-1250',
            'CP1251' => 'WINDOWS-1251',
            'WIN1251' => 'WINDOWS-1251',
            'WINDOWS1251' => 'WINDOWS-1251',
            'CP1252' => 'WINDOWS-1252',
            'WIN1252' => 'WINDOWS-1252',
            'WINDOWS1252' => 'WINDOWS-1252',
            'CP1253' => 'WINDOWS-1253',
            'WIN1253' => 'WINDOWS-1253',
            'WINDOWS1253' => 'WINDOWS-1253',
            'CP1254' => 'WINDOWS-1254',
            'WIN1254' => 'WINDOWS-1254',
            'WINDOWS1254' => 'WINDOWS-1254',
            'CP1255' => 'WINDOWS-1255',
            'WIN1255' => 'WINDOWS-1255',
            'WINDOWS1255' => 'WINDOWS-1255',
            'CP1256' => 'WINDOWS-1256',
            'WIN1256' => 'WINDOWS-1256',
            'WINDOWS1256' => 'WINDOWS-1256',
            'CP1257' => 'WINDOWS-1257',
            'WIN1257' => 'WINDOWS-1257',
            'WINDOWS1257' => 'WINDOWS-1257',
            'CP1258' => 'WINDOWS-1258',
            'WIN1258' => 'WINDOWS-1258',
            'WINDOWS1258' => 'WINDOWS-1258',
            'UTF16' => 'UTF-16',
            'UTF32' => 'UTF-32',
            'UTF8' => 'UTF-8',
            'UTF' => 'UTF-8',
            'UTF7' => 'UTF-7',
            '8BIT' => 'CP850',
            'BINARY' => 'CP850',
        ];

        if (!empty($equivalences[$encodingUpperHelper])) {
            $encoding = $equivalences[$encodingUpperHelper];
        }

        $STATIC_NORMALIZE_ENCODING_CACHE[$encodingOrig] = $encoding;

        return $encoding;
    }

    private function toUtf8($str)
    {

        if (is_array($str) === true) {
            foreach ($str as $key => $value) {
                $str[$key] = $this->toUtf8($value);
            }
            return $str;
        }


        $str = (string)$str;
        if ($str === '') {
            return $str;
        }

        $max = \strlen($str);
        $buf = '';

        for ($i = 0; $i < $max; ++$i) {
            $c1 = $str[$i];

            if ($c1 >= "\xC0") { // should be converted to UTF8, if it's not UTF8 already

                if ($c1 <= "\xDF") { // looks like 2 bytes UTF8

                    $c2 = $i + 1 >= $max ? "\x00" : $str[$i + 1];

                    if ($c2 >= "\x80" && $c2 <= "\xBF") { // yeah, almost sure it's UTF8 already
                        $buf .= $c1 . $c2;
                        ++$i;
                    } else { // not valid UTF8 - convert it
                        $buf .= $this->toUtf8ConvertHelper($c1);
                    }
                } elseif ($c1 >= "\xE0" && $c1 <= "\xEF") { // looks like 3 bytes UTF8

                    $c2 = $i + 1 >= $max ? "\x00" : $str[$i + 1];
                    $c3 = $i + 2 >= $max ? "\x00" : $str[$i + 2];

                    if ($c2 >= "\x80" && $c2 <= "\xBF" && $c3 >= "\x80" && $c3 <= "\xBF") { // yeah, almost sure it's UTF8 already
                        $buf .= $c1 . $c2 . $c3;
                        $i += 2;
                    } else { // not valid UTF8 - convert it
                        $buf .= $this->toUtf8ConvertHelper($c1);
                    }
                } elseif ($c1 >= "\xF0" && $c1 <= "\xF7") { // looks like 4 bytes UTF8

                    $c2 = $i + 1 >= $max ? "\x00" : $str[$i + 1];
                    $c3 = $i + 2 >= $max ? "\x00" : $str[$i + 2];
                    $c4 = $i + 3 >= $max ? "\x00" : $str[$i + 3];

                    if ($c2 >= "\x80" && $c2 <= "\xBF" && $c3 >= "\x80" && $c3 <= "\xBF" && $c4 >= "\x80" && $c4 <= "\xBF") { // yeah, almost sure it's UTF8 already
                        $buf .= $c1 . $c2 . $c3 . $c4;
                        $i += 3;
                    } else { // not valid UTF8 - convert it
                        $buf .= $this->toUtf8ConvertHelper($c1);
                    }
                } else { // doesn't look like UTF8, but should be converted

                    $buf .= $this->toUtf8ConvertHelper($c1);
                }
            } elseif (($c1 & "\xC0") === "\x80") { // needs conversion

                $buf .= $this->toUtf8ConvertHelper($c1);
            } else { // it doesn't need conversion

                $buf .= $c1;
            }
        }

        // decode unicode escape sequences + unicode surrogate pairs
        $buf = preg_replace_callback(
            '/\\\\u([dD][89abAB][0-9a-fA-F]{2})\\\\u([dD][cdefCDEF][\da-fA-F]{2})|\\\\u([0-9a-fA-F]{4})/',
            /**
             * @param array $matches
             *
             * @return string
             */
            function (array $matches) {
                if (isset($matches[3])) {
                    $cp = (int)hexdec($matches[3]);
                } else {
                    // http://unicode.org/faq/utf_bom.html#utf16-4
                    $cp = ((int)hexdec($matches[1]) << 10)
                        + (int)hexdec($matches[2])
                        + 0x10000
                        - (0xD800 << 10)
                        - 0xDC00;
                }

                // https://github.com/php/php-src/blob/php-7.3.2/ext/standard/html.c#L471
                //
                // php_utf32_utf8(unsigned char *buf, unsigned k)

                if ($cp < 0x80) {
                    return (string)$this->chr($cp);
                }

                if ($cp < 0xA0) {
                    /** @noinspection UnnecessaryCastingInspection */
                    return (string)$this->chr(0xC0 | $cp >> 6) . (string)$this->chr(0x80 | $cp & 0x3F);
                }

                return $this->decimalToChr($cp);
            },
            $buf
        );

        if ($buf === null) {
            return '';
        }


        return $buf;
    }

    private function toUtf8ConvertHelper($input)
    {
        // init
        $buf = '';

        if ($this->ORD === null) {
            $this->ORD = $this->getData('ord');
        }

        if ($this->CHR === null) {
            $this->CHR = $this->getData('chr');
        }

        if ($this->WIN1252_TO_UTF8 === null) {
            $this->WIN1252_TO_UTF8 = $this->getData('win1252_to_utf8');
        }

        $ordC1 = $this->ORD[$input];
        if (isset($this->WIN1252_TO_UTF8[$ordC1])) { // found in Windows-1252 special cases
            $buf .= $this->WIN1252_TO_UTF8[$ordC1];
        } else {
            $cc1 = $this->CHR[$ordC1 / 64] | "\xC0";
            $cc2 = ((string)$input & "\x3F") | "\x80";
            $buf .= $cc1 . $cc2;
        }

        return $buf;
    }

    private function chr($code_point, $encoding = 'UTF-8')
    {
        // init
        static $CHAR_CACHE = [];

        if ($encoding !== 'UTF-8' && $encoding !== 'CP850') {
            $encoding = $this->normalize_encoding($encoding, 'UTF-8');
        }

        if ($encoding !== 'UTF-8' && $encoding !== 'ISO-8859-1' && $encoding !== 'WINDOWS-1252' && $this->_supported['mbstring'] === false) {
            trigger_error('UTF8::chr() without mbstring cannot handle "' . $encoding . '" encoding', \E_USER_WARNING);
        }

        $cacheKey = $code_point . $encoding;
        if (isset($CHAR_CACHE[$cacheKey]) === true) {
            return $CHAR_CACHE[$cacheKey];
        }

        if ($code_point <= 127) { // use "simple"-char only until "\x80"

            if ($this->CHR === null) {
                $this->CHR = (array)$this->getData('chr');
            }

            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $chr = $this->CHR[$code_point];

            if ($encoding !== 'UTF-8') {
                $chr = $this->encode($encoding, $chr);
            }

            return $CHAR_CACHE[$cacheKey] = $chr;
        }

        //
        // fallback via "IntlChar"
        //

        if ($this->_supported['intlChar'] === true) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            $chr = IntlChar::chr($code_point);

            if ($encoding !== 'UTF-8') {
                $chr = $this->encode($encoding, $chr);
            }

            return $CHAR_CACHE[$cacheKey] = $chr;
        }

        //
        // fallback via vanilla php
        //

        if ($this->CHR === null) {
            $this->CHR = (array)$this->getData('chr');
        }

        $code_point = (int)$code_point;
        if ($code_point <= 0x7F) {
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $chr = $this->CHR[$code_point];
        } elseif ($code_point <= 0x7FF) {
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $chr = $this->CHR[($code_point >> 6) + 0xC0] .
                $this->CHR[($code_point & 0x3F) + 0x80];
        } elseif ($code_point <= 0xFFFF) {
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $chr = $this->CHR[($code_point >> 12) + 0xE0] .
                $this->CHR[(($code_point >> 6) & 0x3F) + 0x80] .
                $this->CHR[($code_point & 0x3F) + 0x80];
        } else {
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $chr = $this->CHR[($code_point >> 18) + 0xF0] .
                $this->CHR[(($code_point >> 12) & 0x3F) + 0x80] .
                $this->CHR[(($code_point >> 6) & 0x3F) + 0x80] .
                $this->CHR[($code_point & 0x3F) + 0x80];
        }

        if ($encoding !== 'UTF-8') {
            $chr = $this->encode($encoding, $chr);
        }

        return $CHAR_CACHE[$cacheKey] = $chr;
    }

    private function encode($toEncoding, $str)
    {
        if ($str === '' || $toEncoding === '') {
            return $str;
        }

        if ($toEncoding !== 'UTF-8' && $toEncoding !== 'CP850') {
            $toEncoding = $this->normalize_encoding($toEncoding, 'UTF-8');
        }

//        if ($fromEncoding && $fromEncoding !== 'UTF-8' && $fromEncoding !== 'CP850') {
//            $fromEncoding = $this->normalize_encoding($fromEncoding, null);
//        }

//        if ($toEncoding && $fromEncoding && $fromEncoding === $toEncoding) {
//            return $str;
//        }

        if ($toEncoding === 'JSON') {
            $return = $this->jsonEncode($str);
            if ($return === false) {
                throw new InvalidArgumentException('The input string [' . $str . '] can not be used for jsonEncode().');
            }

            return $return;
        }
//        if ($fromEncoding === 'JSON') {
//            $str = $this->json_decode($str);
//            $fromEncoding = '';
//        }

        if ($toEncoding === 'BASE64') {
            return base64_encode($str);
        }
//        if ($fromEncoding === 'BASE64') {
//            $str = base64_decode($str, true);
//            $fromEncoding = '';
//        }

        if ($toEncoding === 'HTML-ENTITIES') {
            return $this->htmlEncode($str, true, 'UTF-8');
        }
//        if ($fromEncoding === 'HTML-ENTITIES') {
//            $str = $this->html_decode($str, \ENT_COMPAT, 'UTF-8');
//            $fromEncoding = '';
//        }

        $fromEncodingDetected = false;
//        if ($autodetectFromEncoding === true || !$fromEncoding) {
//            $fromEncodingDetected = $this->str_detect_encoding($str);
//        }

        // DEBUG
        //var_dump($toEncoding, $fromEncoding, $fromEncodingDetected, $str, "\n\n");

//        if ($fromEncodingDetected !== false) {
//            $fromEncoding = $fromEncodingDetected;
//        } elseif ($autodetectFromEncoding === true) {
//            // fallback for the "autodetect"-mode
//            return $this->toUtf8($str);
//        }

//        if (!$fromEncoding || $fromEncoding === $toEncoding) {
//            return $str;
//        }

//        if ($toEncoding === 'UTF-8' && ($fromEncoding === 'WINDOWS-1252' || $fromEncoding === 'ISO-8859-1')) {
//            return $this->toUtf8($str);
//        }

//        if ($toEncoding === 'ISO-8859-1' && ($fromEncoding === 'WINDOWS-1252' || $fromEncoding === 'UTF-8')) {
//            return $this->to_iso8859($str);
//        }

        if ($toEncoding !== 'UTF-8' && $toEncoding !== 'ISO-8859-1' && $toEncoding !== 'WINDOWS-1252' && $this->_supported['mbstring'] === false) {
            trigger_error('UTF8::encode() without mbstring cannot handle "' . $toEncoding . '" encoding', E_USER_WARNING);
        }
//
//        if ($this->_supported['mbstring'] === true) {
//            // warning: do not use the symfony polyfill here
//            $strEncoded = mb_convert_encoding(
//                $str,
//                $toEncoding,
//                $fromEncoding
//            );
//
//            if ($strEncoded) {
//                return $strEncoded;
//            }
//        }
//
//        $return = \iconv($fromEncoding, $toEncoding, $str);
//        if ($return !== false) {
//            return $return;
//        }

        return $str;
    }

    private function jsonEncode($value)
    {
        $value = $this->filter($value);

        if ($this->_supported['json'] === false) {
            throw new \RuntimeException('ext-json: is not installed');
        }

        /** @noinspection PhpComposerExtensionStubsInspection */
        return json_encode($value, 0, 512);
    }

    private function filter($var, $normalization_form = \Normalizer::NFC, $leading_combining = '◌')
    {
        switch (\gettype($var)) {
            case 'array':
                foreach ($var as $key => $value) {
                    $var[$key] = $this->filter($value, $normalization_form, $leading_combining);
                }
                unset($v);

                break;
            case 'object':
                foreach ($var as $key => $value) {
                    $str[$key] = $this->filter($value, $normalization_form, $leading_combining);
                }
                unset($v);

                break;
            case 'string':

                if (strpos($var, "\r") !== false) {
                    // Workaround https://bugs.php.net/65732
                    $var = $this->normalizeLineEnding($var);
                }

                if ($this->isAscii($var) === false) {
                    if (\Normalizer::isNormalized($var, $normalization_form)) {
                        $n = '-';
                    } else {
                        $n = \Normalizer::normalize($var, $normalization_form);

                        if (isset($n[0])) {
                            $var = $n;
                        } else {
                            $var = $this->encode('UTF-8', $var, true);
                        }
                    }

                    if (
                        $var[0] >= "\x80"
                        &&
                        isset($n[0], $leading_combining[0])
                        &&
                        preg_match('/^\p{Mn}/u', $var)
                    ) {
                        // Prevent leading combining chars
                        // for NFC-safe concatenations.
                        $var = $leading_combining . $var;
                    }
                }

                break;
        }

        return $var;
    }

    private function normalizeLineEnding($str)
    {
        return str_replace(["\r\n", "\r"], "\n", $str);
    }

    private function isAscii($str)
    {
        if ($str === '') {
            return true;
        }

        return !preg_match('/[^\x09\x10\x13\x0A\x0D\x20-\x7E]/', $str);
    }

    private function htmlEncode($str, $keepAsciiChars = false, $encoding = 'UTF-8')
    {
        if ($str === '') {
            return '';
        }

        if ($encoding !== 'UTF-8' && $encoding !== 'CP850') {
            $encoding = $this->normalize_encoding($encoding, 'UTF-8');
        }

        // INFO: http://stackoverflow.com/questions/35854535/better-explanation-of-convmap-in-mb-encode-numericentity
        if ($this->_supported['mbstring'] === true) {
            $startCode = 0x00;
            if ($keepAsciiChars === true) {
                $startCode = 0x80;
            }

            if ($encoding === 'UTF-8') {
                return mb_encode_numericentity(
                    $str,
                    [$startCode, 0xfffff, 0, 0xfffff, 0]
                );
            }

            return mb_encode_numericentity(
                $str,
                [$startCode, 0xfffff, 0, 0xfffff, 0],
                $encoding
            );
        }

        return implode(
            '',
            \array_map(
                function (string $chr) use ($keepAsciiChars, $encoding) {
                    return $this->singleChrHtmlEncode($chr, $keepAsciiChars, $encoding);
                },
                $this->strSplit($str)
            )
        );
    }


    private function singleChrHtmlEncode($char, $keepAsciiChars = false, $encoding = 'UTF-8')
    {
        if ($char === '') {
            return '';
        }

        if ($keepAsciiChars === true && $this->isAscii($char) === true) {
            return $char;
        }

        return '&#' . $this->ord($char, $encoding) . ';';
    }

    private function ord($chr, $encoding = 'UTF-8')
    {
        static $CHAR_CACHE = [];

        // init
        $chr = (string)$chr;

        if ($encoding !== 'UTF-8' && $encoding !== 'CP850') {
            $encoding = $this->normalize_encoding($encoding, 'UTF-8');
        }

        $cacheKey = $chr . $encoding;
        if (isset($CHAR_CACHE[$cacheKey]) === true) {
            return $CHAR_CACHE[$cacheKey];
        }

        // check again, if it's still not UTF-8
        if ($encoding !== 'UTF-8') {
            $chr = $this->encode($encoding, $chr);
        }

        if ($this->ORD === null) {
            $this->ORD = $this->getData('ord');
        }

        if (isset($this->ORD[$chr])) {
            return $CHAR_CACHE[$cacheKey] = $this->ORD[$chr];
        }

        //
        // fallback via "IntlChar"
        //

        if ($this->_supported['intlChar'] === true) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            $code = \IntlChar::ord($chr);
            if ($code) {
                return $CHAR_CACHE[$cacheKey] = $code;
            }
        }

        //
        // fallback via vanilla php
        //

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $chr = \unpack('C*', (string)\substr($chr, 0, 4));
        $code = $chr ? $chr[1] : 0;

        if ($code >= 0xF0 && isset($chr[4])) {
            /** @noinspection UnnecessaryCastingInspection */
            return $CHAR_CACHE[$cacheKey] = (int)((($code - 0xF0) << 18) + (($chr[2] - 0x80) << 12) + (($chr[3] - 0x80) << 6) + $chr[4] - 0x80);
        }

        if ($code >= 0xE0 && isset($chr[3])) {
            /** @noinspection UnnecessaryCastingInspection */
            return $CHAR_CACHE[$cacheKey] = (int)((($code - 0xE0) << 12) + (($chr[2] - 0x80) << 6) + $chr[3] - 0x80);
        }

        if ($code >= 0xC0 && isset($chr[2])) {
            /** @noinspection UnnecessaryCastingInspection */
            return $CHAR_CACHE[$cacheKey] = (int)((($code - 0xC0) << 6) + $chr[2] - 0x80);
        }

        return $CHAR_CACHE[$cacheKey] = $code;
    }

    private function strSplit($str, $length = 1)
    {
        if ($length <= 0) {
            return [];
        }

        if (is_array($str) === true) {
            foreach ($str as $key => $value) {
                $str[$key] = $this->strSplit($value, $length);
            }

            return $str;
        }

        // init
        $str = (string)$str;

        if ($str === '') {
            return [];
        }


        //gere
        $ret = $this->strSplitString($str);

        if ($length > 1) {
            $ret = \array_chunk($ret, $length);

            return array_map(
                static function (&$item) {
                    return implode('', $item);
                },
                $ret
            );
        }

        if (isset($ret[0]) && $ret[0] === '') {
            return [];
        }

        return $ret;
    }

    private function strSplitString($str)
    {
        $supportString = 'default';
        if ($this->_supported['mbstring'] === true) {
            $supportString = 'mbstring';
        }
        if ($this->_supported['pcre_utf8'] === true) {
            $supportString = 'pcre_utf8';
        }

        switch ($supportString) {

            case 'mbstring':

                $iMax = \mb_strlen($str);
                if ($iMax <= 127) {
                    $ret = [];
                    for ($i = 0; $i < $iMax; ++$i) {
                        $ret[] = \mb_substr($str, $i, 1);
                    }
                } else {
                    $retArray = [];
                    preg_match_all('/./us', $str, $retArray);
                    $ret = isset($retArray[0]) ? $retArray[0] : [];
                }

                break;
            case 'pcre_utf8':
                $retArray = [];
                preg_match_all('/./us', $str, $retArray);
                $ret = isset($retArray[0]) ? $retArray[0] : [];
                break;
            default:
                $ret = [];
                $len = \strlen($str);

                /** @noinspection ForeachInvariantsInspection */
                for ($i = 0; $i < $len; ++$i) {
                    if (($str[$i] & "\x80") === "\x00") {
                        $ret[] = $str[$i];
                    } elseif (
                        isset($str[$i + 1])
                        &&
                        ($str[$i] & "\xE0") === "\xC0"
                    ) {
                        if (($str[$i + 1] & "\xC0") === "\x80") {
                            $ret[] = $str[$i] . $str[$i + 1];

                            ++$i;
                        }
                    } elseif (
                        isset($str[$i + 2])
                        &&
                        ($str[$i] & "\xF0") === "\xE0"
                    ) {
                        if (
                            ($str[$i + 1] & "\xC0") === "\x80"
                            &&
                            ($str[$i + 2] & "\xC0") === "\x80"
                        ) {
                            $ret[] = $str[$i] . $str[$i + 1] . $str[$i + 2];

                            $i += 2;
                        }
                    } elseif (
                        isset($str[$i + 3])
                        &&
                        ($str[$i] & "\xF8") === "\xF0"
                    ) {
                        if (
                            ($str[$i + 1] & "\xC0") === "\x80"
                            &&
                            ($str[$i + 2] & "\xC0") === "\x80"
                            &&
                            ($str[$i + 3] & "\xC0") === "\x80"
                        ) {
                            $ret[] = $str[$i] . $str[$i + 1] . $str[$i + 2] . $str[$i + 3];

                            $i += 3;
                        }
                    }
                }
                break;
        }

        return $ret;
    }

    private function decimalToChr($int)
    {
        return $this->htmlEntityDecode('&#' . $int . ';', \ENT_QUOTES | \ENT_HTML5);
    }

    private function clean($str, $remove_bom = false, $normalize_whitespace = false, $normalize_msword = false, $keep_non_breaking_space = false, $replace_diamond_question_mark = false, $remove_invisible_characters = true)
    {
        // http://stackoverflow.com/questions/1401317/remove-non-utf8-characters-from-string
        // caused connection reset problem on larger strings

        $regx = '/
          (
            (?: [\x00-\x7F]               # single-byte sequences   0xxxxxxx
            |   [\xC0-\xDF][\x80-\xBF]    # double-byte sequences   110xxxxx 10xxxxxx
            |   [\xE0-\xEF][\x80-\xBF]{2} # triple-byte sequences   1110xxxx 10xxxxxx * 2
            |   [\xF0-\xF7][\x80-\xBF]{3} # quadruple-byte sequence 11110xxx 10xxxxxx * 3
            ){1,100}                      # ...one or more times
          )
        | ( [\x80-\xBF] )                 # invalid byte in range 10000000 - 10111111
        | ( [\xC0-\xFF] )                 # invalid byte in range 11000000 - 11111111
        /x';
        $str = (string)preg_replace($regx, '$1', $str);

        if ($replace_diamond_question_mark === true) {
            $str = $this->replace_diamond_question_mark($str, '');
        }

        if ($remove_invisible_characters === true) {
            $str = $this->remove_invisible_characters($str);
        }

        if ($normalize_whitespace === true) {
            $str = $this->normalize_whitespace($str, $keep_non_breaking_space);
        }

        if ($normalize_msword === true) {
            $str = $this->normalize_msword($str);
        }

        if ($remove_bom === true) {
            $str = $this->remove_bom($str);
        }

        return $str;
    }

    public function replace_diamond_question_mark($str, $replacementChar = '', $processInvalidUtf8 = true)
    {
        if ($str === '') {
            return '';
        }

        if ($processInvalidUtf8 === true) {
            $replacementCharHelper = $replacementChar;
            if ($replacementChar === '') {
                $replacementCharHelper = 'none';
            }

            if ($this->_supported['mbstring'] === false) {
                // if there is no native support for "mbstring",
                // then we need to clean the string before ...
                $str = $this->clean($str);
            }

            $save = \mb_substitute_character();
            \mb_substitute_character($replacementCharHelper);
            // the polyfill maybe return false, so cast to string
            $str = (string)\mb_convert_encoding($str, 'UTF-8', 'UTF-8');
            \mb_substitute_character($save);
        }

        return str_replace(
            [
                "\xEF\xBF\xBD",
                '�',
            ],
            [
                $replacementChar,
                $replacementChar,
            ],
            $str
        );
    }

    public function remove_invisible_characters($str, $url_encoded = true, $replacement = '')
    {
        // init
        $non_displayables = [];

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcefBCEF]/'; // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-fA-F]/'; // url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127

        do {
            $str = (string)preg_replace($non_displayables, $replacement, $str, -1, $count);
        } while ($count !== 0);

        return $str;
    }

    public function normalize_whitespace($str, $keepNonBreakingSpace = false, $keepBidiUnicodeControls = false)
    {
        if ($str === '') {
            return '';
        }

        static $WHITESPACE_CACHE = [];
        $cacheKey = (int)$keepNonBreakingSpace;

        if (!isset($WHITESPACE_CACHE[$cacheKey])) {
            $WHITESPACE_CACHE[$cacheKey] = $this->WHITESPACE_TABLE;

            if ($keepNonBreakingSpace === true) {
                unset($WHITESPACE_CACHE[$cacheKey]['NO-BREAK SPACE']);
            }

            $WHITESPACE_CACHE[$cacheKey] = array_values($WHITESPACE_CACHE[$cacheKey]);
        }

        if ($keepBidiUnicodeControls === false) {
            static $BIDI_UNICODE_CONTROLS_CACHE = null;

            if ($BIDI_UNICODE_CONTROLS_CACHE === null) {
                $BIDI_UNICODE_CONTROLS_CACHE = array_values($this->BIDI_UNI_CODE_CONTROLS_TABLE);
            }

            $str = \str_replace($BIDI_UNICODE_CONTROLS_CACHE, '', $str);
        }

        return str_replace($WHITESPACE_CACHE[$cacheKey], ' ', $str);
    }

    private function normalize_msword($str)
    {
        if ($str === '') {
            return '';
        }

        $keys = [
            "\xc2\xab", // « (U+00AB) in UTF-8
            "\xc2\xbb", // » (U+00BB) in UTF-8
            "\xe2\x80\x98", // ‘ (U+2018) in UTF-8
            "\xe2\x80\x99", // ’ (U+2019) in UTF-8
            "\xe2\x80\x9a", // ‚ (U+201A) in UTF-8
            "\xe2\x80\x9b", // ‛ (U+201B) in UTF-8
            "\xe2\x80\x9c", // “ (U+201C) in UTF-8
            "\xe2\x80\x9d", // ” (U+201D) in UTF-8
            "\xe2\x80\x9e", // „ (U+201E) in UTF-8
            "\xe2\x80\x9f", // ‟ (U+201F) in UTF-8
            "\xe2\x80\xb9", // ‹ (U+2039) in UTF-8
            "\xe2\x80\xba", // › (U+203A) in UTF-8
            "\xe2\x80\x93", // – (U+2013) in UTF-8
            "\xe2\x80\x94", // — (U+2014) in UTF-8
            "\xe2\x80\xa6", // … (U+2026) in UTF-8
        ];

        $values = [
            '"', // « (U+00AB) in UTF-8
            '"', // » (U+00BB) in UTF-8
            "'", // ‘ (U+2018) in UTF-8
            "'", // ’ (U+2019) in UTF-8
            "'", // ‚ (U+201A) in UTF-8
            "'", // ‛ (U+201B) in UTF-8
            '"', // “ (U+201C) in UTF-8
            '"', // ” (U+201D) in UTF-8
            '"', // „ (U+201E) in UTF-8
            '"', // ‟ (U+201F) in UTF-8
            "'", // ‹ (U+2039) in UTF-8
            "'", // › (U+203A) in UTF-8
            '-', // – (U+2013) in UTF-8
            '-', // — (U+2014) in UTF-8
            '...', // … (U+2026) in UTF-8
        ];

        return str_replace($keys, $values, $str);
    }

    public function remove_bom($str)
    {
        if ($str === '') {
            return '';
        }

        $strLength = \strlen($str);
        foreach ($this->BOM as $bomString => $bomByteLength) {
            if (strpos($str, $bomString, 0) === 0) {
                $strTmp = \substr($str, $bomByteLength, $strLength);
                if ($strTmp === false) {
                    return '';
                }

                $strLength -= (int)$bomByteLength;
                $str = (string)$strTmp;
            }
        }

        return $str;
    }

}
