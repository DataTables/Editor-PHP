<?php

// Downloaded from https://github.com/vanilla/htmlawed/blob/v2.2.15/src/Htmlawed.php
// with the following modifications:
// 1. add `DataTables\HtmLawed` namespace
// 2. rename class name from `Htmlawed` to `HtmLawedVanillaWrapper`
// 3. readd PHP 5.3 support - change `[]` array constructor syntax to `array()`
// 4. remove https://github.com/vanilla/htmlawed/blob/v2.2.15/src/Htmlawed.php#L45 line
// 5. update `htmLawed` call on https://github.com/vanilla/htmlawed/blob/v2.2.15/src/Htmlawed.php#L59 line to `HtmLawed::hl`
// 6. add missing `string` type to phpdoc on https://github.com/vanilla/htmlawed/blob/v2.2.15/src/Htmlawed.php#L66 line

/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license LGPL-3.0
 */

namespace DataTables\HtmLawed;

/**
 * A class wrapper for the htmLawed library.
 */
class HtmLawedVanillaWrapper {
    /// Methods ///

    public static $defaultConfig = array(
        'anti_link_spam' => array('`.`', ''),
        'balance' => 1,
        'cdata' => 3,
        'safe' => 1,
        'comment' => 1,
        'css_expression' => 0,
        'deny_attribute' => 'on*,style',
        'direct_list_nest' => 1,
        'elements' => '*-applet-button-form-input-textarea-iframe-script-style-embed-object',
        'keep_bad' => 0,
        'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
        'unique_ids' => 0,
        'valid_xhtml' => 0,
    );

    public static $defaultSpec = array(
        'object=-classid-type, -codebase',
        'embed=type(oneof=application/x-shockwave-flash)'
    );

    /**
     * Filters a string of html with the htmLawed library.
     *
     * @param string $html The text to filter.
     * @param array|null $config Config settings for the array.
     * @param string|array|null $spec A specification to further limit the allowed attribute values in the html.
     * @return string Returns the filtered html.
     * @see http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/htmLawed_README.htm
     */
    public static function filter($html, array $config = null, $spec = null) {
        if ($config === null) {
            $config = self::$defaultConfig;
        }

        if (isset($config['spec']) && !$spec) {
            $spec = $config['spec'];
        }

        if ($spec === null) {
            $spec = static::$defaultSpec;
        }

        return HtmLawed::hl($html, $config, $spec);
    }


    /**
     * Filter a string of html so that it can be put into an rss feed.
     *
     * @param string $html The html text to fitlter.
     * @return string Returns the filtered html.
     * @see Htmlawed::filter().
     */
    public static function filterRSS($html) {
        $config = array(
            'anti_link_spam' => array('`.`', ''),
            'comment' => 1,
            'cdata' => 3,
            'css_expression' => 1,
            'deny_attribute' => 'on*,style,class',
            'elements' => '*-applet-form-input-textarea-iframe-script-style-object-embed-comment-link-listing-meta-noscript-plaintext-xmp',
            'keep_bad' => 0,
            'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
            'valid_xhtml' => 1,
            'balance' => 1
        );
        $spec = static::$defaultSpec;

        $result = static::filter($html, $config, $spec);

        return $result;
    }
}
