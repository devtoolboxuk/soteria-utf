<?php

namespace devtoolboxuk\soteriautf\Resources;

class NeverAllowed extends Resources
{

    private $_never_allowed_reg_ex = [

        // default javascript
        '(\(?document\)?|\(?window\)?(\.document)?)\.(location|on\w*)',
        // data-attribute + base64
        "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?",
        // remove Netscape 4 JS entities
        '&\s*\{[^}]*(\}\s*;?|$)',
        // old IE, old Netscape
        'expression\s*(\(|&\#40;)',
    ];

    private $_never_allowed_on_events_afterwards = [
        'onAbort',
        'onActivate',
        'onAttribute',
        'onAfterPrint',
        'onAfterScriptExecute',
        'onAfterUpdate',
        'onAnimationCancel',
        'onAnimationEnd',
        'onAnimationIteration',
        'onAnimationStart',
        'onAriaRequest',
        'onAutoComplete',
        'onAutoCompleteError',
        'onAuxClick',
        'onBeforeActivate',
        'onBeforeCopy',
        'onBeforeCut',
        'onBeforeDeactivate',
        'onBeforeEditFocus',
        'onBeforePaste',
        'onBeforePrint',
        'onBeforeScriptExecute',
        'onBeforeUnload',
        'onBeforeUpdate',
        'onBegin',
        'onBlur',
        'onBounce',
        'onCancel',
        'onCanPlay',
        'onCanPlayThrough',
        'onCellChange',
        'onChange',
        'onClick',
        'onClose',
        'onCommand',
        'onCompassNeedsCalibration',
        'onContextMenu',
        'onControlSelect',
        'onCopy',
        'onCueChange',
        'onCut',
        'onDataAvailable',
        'onDataSetChanged',
        'onDataSetComplete',
        'onDblClick',
        'onDeactivate',
        'onDeviceLight',
        'onDeviceMotion',
        'onDeviceOrientation',
        'onDeviceProximity',
        'onDrag',
        'onDragDrop',
        'onDragEnd',
        'onDragEnter',
        'onDragLeave',
        'onDragOver',
        'onDragStart',
        'onDrop',
        'onDurationChange',
        'onEmptied',
        'onEnd',
        'onEnded',
        'onError',
        'onErrorUpdate',
        'onExit',
        'onFilterChange',
        'onFinish',
        'onFocus',
        'onFocusIn',
        'onFocusOut',
        'onFormChange',
        'onFormInput',
        'onFullScreenChange',
        'onFullScreenError',
        'onGotPointerCapture',
        'onHashChange',
        'onHelp',
        'onInput',
        'onInvalid',
        'onKeyDown',
        'onKeyPress',
        'onKeyUp',
        'onLanguageChange',
        'onLayoutComplete',
        'onLoad',
        'onLoadedData',
        'onLoadedMetaData',
        'onLoadStart',
        'onLoseCapture',
        'onLostPointerCapture',
        'onMediaComplete',
        'onMediaError',
        'onMessage',
        'onMouseDown',
        'onMouseEnter',
        'onMouseLeave',
        'onMouseMove',
        'onMouseOut',
        'onMouseOver',
        'onMouseUp',
        'onMouseWheel',
        'onMove',
        'onMoveEnd',
        'onMoveStart',
        'onMozFullScreenChange',
        'onMozFullScreenError',
        'onMozPointerLockChange',
        'onMozPointerLockError',
        'onMsContentZoom',
        'onMsFullScreenChange',
        'onMsFullScreenError',
        'onMsGestureChange',
        'onMsGestureDoubleTap',
        'onMsGestureEnd',
        'onMsGestureHold',
        'onMsGestureStart',
        'onMsGestureTap',
        'onMsGotPointerCapture',
        'onMsInertiaStart',
        'onMsLostPointerCapture',
        'onMsManipulationStateChanged',
        'onMsPointerCancel',
        'onMsPointerDown',
        'onMsPointerEnter',
        'onMsPointerLeave',
        'onMsPointerMove',
        'onMsPointerOut',
        'onMsPointerOver',
        'onMsPointerUp',
        'onMsSiteModeJumpListItemRemoved',
        'onMsThumbnailClick',
        'onOffline',
        'onOnline',
        'onOutOfSync',
        'onPage',
        'onPageHide',
        'onPageShow',
        'onPaste',
        'onPause',
        'onPlay',
        'onPlaying',
        'onPointerCancel',
        'onPointerDown',
        'onPointerEnter',
        'onPointerLeave',
        'onPointerLockChange',
        'onPointerLockError',
        'onPointerMove',
        'onPointerOut',
        'onPointerOver',
        'onPointerUp',
        'onPopState',
        'onProgress',
        'onPropertyChange',
        'onRateChange',
        'onReadyStateChange',
        'onReceived',
        'onRepeat',
        'onReset',
        'onResize',
        'onResizeEnd',
        'onResizeStart',
        'onResume',
        'onReverse',
        'onRowDelete',
        'onRowEnter',
        'onRowExit',
        'onRowInserted',
        'onRowsDelete',
        'onRowsEnter',
        'onRowsExit',
        'onRowsInserted',
        'onScroll',
        'onSearch',
        'onSeek',
        'onSeeked',
        'onSeeking',
        'onSelect',
        'onSelectionChange',
        'onSelectStart',
        'onStalled',
        'onStorage',
        'onStorageCommit',
        'onStart',
        'onStop',
        'onShow',
        'onSyncRestored',
        'onSubmit',
        'onSuspend',
        'onSynchRestored',
        'onTimeError',
        'onTimeUpdate',
        'onTrackChange',
        'onTransitionEnd',
        'onToggle',
        'onTouchCancel',
        'onTouchStart',
        'onTransitionCancel',
        'onTransitionEnd',
        'onUnload',
        'onURLFlip',
        'onUserProximity',
        'onVolumeChange',
        'onWaiting',
        'onWebKitAnimationEnd',
        'onWebKitAnimationIteration',
        'onWebKitAnimationStart',
        'onWebKitFullScreenChange',
        'onWebKitFullScreenError',
        'onWebKitTransitionEnd',
        'onWheel',
    ];

    private $_never_allowed_str_afterwards = [
        'FSCOMMAND',
        '&lt;script&gt;',
        '&lt;/script&gt;',
    ];

    private $_never_allowed_call_statements = [
        // default javascript
        'javascript',
        // Java: jar-protocol is an XSS hazard
        'jar',
        // Mac (will not run the script, but open it in AppleScript Editor)
        'applescript',
        // IE: https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet#VBscript_in_an_image
        'vbscript',
        'vbs',
        // IE, surprise!
        'wscript',
        // IE
        'jscript',
        // https://html5sec.org/#behavior
        'behavior',
        // old Netscape
        'mocha',
        // old Netscape
        'livescript',
        // default view source
        'view-source',
    ];

    public function doNeverAllowedAfterwards($str)
    {
        if (stripos($str, 'on') !== false) {
            foreach ($this->_never_allowed_on_events_afterwards as $event) {
                if (stripos($str, $event) !== false) {
                    $regex = '(?<before>[^\p{L}]|^)(?:' . $event . ')(?<after>\s|[^\p{L}]|$)';

                    do {
                        $count = $temp_count = 0;

                        $str = (string)\preg_replace(
                            '#' . $regex . '#ius',
                            '$1' . $this->_replacement . '$2',
                            $str,
                            -1,
                            $temp_count
                        );
                        $count += $temp_count;
                    } while ($count);
                }
            }
        }

        return (string)str_ireplace(
            $this->_never_allowed_str_afterwards,
            $this->_replacement,
            $str
        );
    }

    public function doNeverAllowed($str)
    {
        static $NEVER_ALLOWED_CACHE = [];

        $NEVER_ALLOWED_CACHE['keys'] = null;
        $NEVER_ALLOWED_CACHE['regex'] = null;

        if ($NEVER_ALLOWED_CACHE['keys'] === null) {
            $NEVER_ALLOWED_CACHE['keys'] = array_keys($this->neverAllowedStrings());
        }

        $str = str_ireplace(
            $NEVER_ALLOWED_CACHE['keys'],
            $this->neverAllowedStrings(),
            $str
        );


        foreach ($this->_never_allowed_call_statements as $call) {
            if (stripos($str, $call) !== false) {
                $str = (string)preg_replace(
                    '#([^\p{L}]|^)' . $call . '\s*:#ius',
                    '$1' . $this->_replacement,
                    $str
                );
            }
        }


        if ($NEVER_ALLOWED_CACHE['regex'] === null) {
            $NEVER_ALLOWED_CACHE['regex'] = implode('|', $this->_never_allowed_reg_ex);
        }

        $str = (string)preg_replace('#' . $NEVER_ALLOWED_CACHE['regex'] . '#ius', $this->_replacement, $str);

        return $str;
    }

    private function neverAllowedStrings()
    {
        return [
            'document.cookie' => $this->_replacement,
            '(document).cookie' => $this->_replacement,
            'document.write' => $this->_replacement,
            '(document).write' => $this->_replacement,
            '.parentNode' => $this->_replacement,
            '.innerHTML' => $this->_replacement,
            '.appendChild' => $this->_replacement,
            '-moz-binding' => $this->_replacement,
            '<!--' => '&lt;!--',
            '-->' => '--&gt;',
            '<?' => '&lt;?',
            '?>' => '?&gt;',
            '<![CDATA[' => '&lt;![CDATA[',
            '<!ENTITY' => '&lt;!ENTITY',
            '<!DOCTYPE' => '&lt;!DOCTYPE',
            '<!ATTLIST' => '&lt;!ATTLIST',
        ];
    }
}