<?php

namespace CultuurNet\SearchV3Utilities;

/**
 * Provides Unicode-related conversions and operations.
 *
 * @ingroup utility
 * @source Drupal
 */
class Unicode
{

  /**
   * Matches Unicode characters that are word boundaries.
   *
   * Characters with the following General_category (gc) property values are used
   * as word boundaries. While this does not fully conform to the Word Boundaries
   * algorithm described in http://unicode.org/reports/tr29, as PCRE does not
   * contain the Word_Break property table, this simpler algorithm has to do.
   * - Cc, Cf, Cn, Co, Cs: Other.
   * - Pc, Pd, Pe, Pf, Pi, Po, Ps: Punctuation.
   * - Sc, Sk, Sm, So: Symbols.
   * - Zl, Zp, Zs: Separators.
   *
   * Non-boundary characters include the following General_category (gc) property
   * values:
   * - Ll, Lm, Lo, Lt, Lu: Letters.
   * - Mc, Me, Mn: Combining Marks.
   * - Nd, Nl, No: Numbers.
   *
   * Note that the PCRE property matcher is not used because we wanted to be
   * compatible with Unicode 5.2.0 regardless of the PCRE version used (and any
   * bugs in PCRE property tables).
   *
   * @see http://unicode.org/glossary
   */
    const PREG_CLASS_WORD_BOUNDARY = <<<'EOD'
\x{0}-\x{2F}\x{3A}-\x{40}\x{5B}-\x{60}\x{7B}-\x{A9}\x{AB}-\x{B1}\x{B4}
\x{B6}-\x{B8}\x{BB}\x{BF}\x{D7}\x{F7}\x{2C2}-\x{2C5}\x{2D2}-\x{2DF}
\x{2E5}-\x{2EB}\x{2ED}\x{2EF}-\x{2FF}\x{375}\x{37E}-\x{385}\x{387}\x{3F6}
\x{482}\x{55A}-\x{55F}\x{589}-\x{58A}\x{5BE}\x{5C0}\x{5C3}\x{5C6}
\x{5F3}-\x{60F}\x{61B}-\x{61F}\x{66A}-\x{66D}\x{6D4}\x{6DD}\x{6E9}
\x{6FD}-\x{6FE}\x{700}-\x{70F}\x{7F6}-\x{7F9}\x{830}-\x{83E}
\x{964}-\x{965}\x{970}\x{9F2}-\x{9F3}\x{9FA}-\x{9FB}\x{AF1}\x{B70}
\x{BF3}-\x{BFA}\x{C7F}\x{CF1}-\x{CF2}\x{D79}\x{DF4}\x{E3F}\x{E4F}
\x{E5A}-\x{E5B}\x{F01}-\x{F17}\x{F1A}-\x{F1F}\x{F34}\x{F36}\x{F38}
\x{F3A}-\x{F3D}\x{F85}\x{FBE}-\x{FC5}\x{FC7}-\x{FD8}\x{104A}-\x{104F}
\x{109E}-\x{109F}\x{10FB}\x{1360}-\x{1368}\x{1390}-\x{1399}\x{1400}
\x{166D}-\x{166E}\x{1680}\x{169B}-\x{169C}\x{16EB}-\x{16ED}
\x{1735}-\x{1736}\x{17B4}-\x{17B5}\x{17D4}-\x{17D6}\x{17D8}-\x{17DB}
\x{1800}-\x{180A}\x{180E}\x{1940}-\x{1945}\x{19DE}-\x{19FF}
\x{1A1E}-\x{1A1F}\x{1AA0}-\x{1AA6}\x{1AA8}-\x{1AAD}\x{1B5A}-\x{1B6A}
\x{1B74}-\x{1B7C}\x{1C3B}-\x{1C3F}\x{1C7E}-\x{1C7F}\x{1CD3}\x{1FBD}
\x{1FBF}-\x{1FC1}\x{1FCD}-\x{1FCF}\x{1FDD}-\x{1FDF}\x{1FED}-\x{1FEF}
\x{1FFD}-\x{206F}\x{207A}-\x{207E}\x{208A}-\x{208E}\x{20A0}-\x{20B8}
\x{2100}-\x{2101}\x{2103}-\x{2106}\x{2108}-\x{2109}\x{2114}
\x{2116}-\x{2118}\x{211E}-\x{2123}\x{2125}\x{2127}\x{2129}\x{212E}
\x{213A}-\x{213B}\x{2140}-\x{2144}\x{214A}-\x{214D}\x{214F}
\x{2190}-\x{244A}\x{249C}-\x{24E9}\x{2500}-\x{2775}\x{2794}-\x{2B59}
\x{2CE5}-\x{2CEA}\x{2CF9}-\x{2CFC}\x{2CFE}-\x{2CFF}\x{2E00}-\x{2E2E}
\x{2E30}-\x{3004}\x{3008}-\x{3020}\x{3030}\x{3036}-\x{3037}
\x{303D}-\x{303F}\x{309B}-\x{309C}\x{30A0}\x{30FB}\x{3190}-\x{3191}
\x{3196}-\x{319F}\x{31C0}-\x{31E3}\x{3200}-\x{321E}\x{322A}-\x{3250}
\x{3260}-\x{327F}\x{328A}-\x{32B0}\x{32C0}-\x{33FF}\x{4DC0}-\x{4DFF}
\x{A490}-\x{A4C6}\x{A4FE}-\x{A4FF}\x{A60D}-\x{A60F}\x{A673}\x{A67E}
\x{A6F2}-\x{A716}\x{A720}-\x{A721}\x{A789}-\x{A78A}\x{A828}-\x{A82B}
\x{A836}-\x{A839}\x{A874}-\x{A877}\x{A8CE}-\x{A8CF}\x{A8F8}-\x{A8FA}
\x{A92E}-\x{A92F}\x{A95F}\x{A9C1}-\x{A9CD}\x{A9DE}-\x{A9DF}
\x{AA5C}-\x{AA5F}\x{AA77}-\x{AA79}\x{AADE}-\x{AADF}\x{ABEB}
\x{E000}-\x{F8FF}\x{FB29}\x{FD3E}-\x{FD3F}\x{FDFC}-\x{FDFD}
\x{FE10}-\x{FE19}\x{FE30}-\x{FE6B}\x{FEFF}-\x{FF0F}\x{FF1A}-\x{FF20}
\x{FF3B}-\x{FF40}\x{FF5B}-\x{FF65}\x{FFE0}-\x{FFFD}
EOD;

  /**
   * Indicates that standard PHP (emulated) unicode support is being used.
   */
    const STATUS_SINGLEBYTE = 0;

  /**
   * Indicates that full unicode support with the PHP mbstring extension is
   * being used.
   */
    const STATUS_MULTIBYTE = 1;

  /**
   * Indicates an error during check for PHP unicode support.
   */
    const STATUS_ERROR = -1;

  /**
   * Holds the multibyte capabilities of the current environment.
   *
   * @var int
   */
    protected static $status;

  /**
   * Gets the current status of unicode/multibyte support on this environment.
   *
   * @return int
   *   The status of multibyte support. It can be one of:
   *   - Unicode::STATUS_MULTIBYTE
   *     Full unicode support using an extension.
   *   - Unicode::STATUS_SINGLEBYTE
   *     Standard PHP (emulated) unicode support.
   *   - Unicode::STATUS_ERROR
   *     An error occurred. No unicode support.
   */
    public static function getStatus()
    {
        if (empty(static::$status)) {
            static::check();
        }

        return static::$status;
    }

  /**
   * Sets the value for multibyte support status for the current environment.
   *
   * The following status keys are supported:
   *   - Unicode::STATUS_MULTIBYTE
   *     Full unicode support using an extension.
   *   - Unicode::STATUS_SINGLEBYTE
   *     Standard PHP (emulated) unicode support.
   *   - Unicode::STATUS_ERROR
   *     An error occurred. No unicode support.
   *
   * @param int $status
   *   The new status of multibyte support.
   */
    public static function setStatus($status)
    {
        if (!in_array($status, [static::STATUS_SINGLEBYTE, static::STATUS_MULTIBYTE, static::STATUS_ERROR])) {
            throw new \InvalidArgumentException('Invalid status value for unicode support.');
        }
        static::$status = $status;
    }

  /**
   * Checks for Unicode support in PHP and sets the proper settings if possible.
   *
   * Because of the need to be able to handle text in various encodings, we do
   * not support mbstring function overloading. HTTP input/output conversion
   * must be disabled for similar reasons.
   *
   * @return string
   *   A string identifier of a failed multibyte extension check, if any.
   *   Otherwise, an empty string.
   */
    public static function check()
    {
        // Check for mbstring extension.
        if (!function_exists('mb_strlen')) {
            static::$status = static::STATUS_SINGLEBYTE;
            return 'mb_strlen';
        }

        // Check mbstring configuration.
        if (ini_get('mbstring.func_overload') != 0) {
            static::$status = static::STATUS_ERROR;
            return 'mbstring.func_overload';
        }
        if (ini_get('mbstring.encoding_translation') != 0) {
            static::$status = static::STATUS_ERROR;
            return 'mbstring.encoding_translation';
        }
        // mbstring.http_input and mbstring.http_output are deprecated and empty by
        // default in PHP 5.6.
        if (version_compare(PHP_VERSION, '5.6.0') == -1) {
            if (ini_get('mbstring.http_input') != 'pass') {
                static::$status = static::STATUS_ERROR;
                return 'mbstring.http_input';
            }
            if (ini_get('mbstring.http_output') != 'pass') {
                static::$status = static::STATUS_ERROR;
                return 'mbstring.http_output';
            }
        }

        // Set appropriate configuration.
        mb_internal_encoding('utf-8');
        mb_language('uni');
        static::$status = static::STATUS_MULTIBYTE;
        return '';
    }

  /**
   * Counts the number of characters in a UTF-8 string.
   *
   * This is less than or equal to the byte count.
   *
   * @param string $text
   *   The string to run the operation on.
   *
   * @return int
   *   The length of the string.
   */
    public static function strlen($text)
    {
        if (static::getStatus() == static::STATUS_MULTIBYTE) {
            return mb_strlen($text);
        } else {
            // Do not count UTF-8 continuation bytes.
            return strlen(preg_replace("/[\x80-\xBF]/", '', $text));
        }
    }

  /**
   * Cuts off a piece of a string based on character indices and counts.
   *
   * Follows the same behavior as PHP's own substr() function. Note that for
   * cutting off a string at a known character/substring location, the usage of
   * PHP's normal strpos/substr is safe and much faster.
   *
   * @param string $text
   *   The input string.
   * @param int $start
   *   The position at which to start reading.
   * @param int $length
   *   The number of characters to read.
   *
   * @return string
   *   The shortened string.
   */
    public static function substr($text, $start, $length = null)
    {
        if (static::getStatus() == static::STATUS_MULTIBYTE) {
            return $length === null ? mb_substr($text, $start) : mb_substr($text, $start, $length);
        } else {
            $strlen = strlen($text);
            // Find the starting byte offset.
            $bytes = 0;
            if ($start > 0) {
                // Count all the characters except continuation bytes from the start
                // until we have found $start characters or the end of the string.
                $bytes = -1;
                $chars = -1;
                while ($bytes < $strlen - 1 && $chars < $start) {
                    $bytes++;
                    $c = ord($text[$bytes]);
                    if ($c < 0x80 || $c >= 0xC0) {
                        $chars++;
                    }
                }
            } elseif ($start < 0) {
                // Count all the characters except continuation bytes from the end
                // until we have found abs($start) characters.
                $start = abs($start);
                $bytes = $strlen;
                $chars = 0;
                while ($bytes > 0 && $chars < $start) {
                    $bytes--;
                    $c = ord($text[$bytes]);
                    if ($c < 0x80 || $c >= 0xC0) {
                        $chars++;
                    }
                }
            }
            $istart = $bytes;

            // Find the ending byte offset.
            if ($length === null) {
                $iend = $strlen;
            } elseif ($length > 0) {
                // Count all the characters except continuation bytes from the starting
                // index until we have found $length characters or reached the end of
                // the string, then backtrace one byte.
                $iend = $istart - 1;
                $chars = -1;
                $lastReal = false;
                while ($iend < $strlen - 1 && $chars < $length) {
                    $iend++;
                    $c = ord($text[$iend]);
                    $lastReal = false;
                    if ($c < 0x80 || $c >= 0xC0) {
                        $chars++;
                        $lastReal = true;
                    }
                }
                // Backtrace one byte if the last character we found was a real
                // character and we don't need it.
                if ($lastReal && $chars >= $length) {
                    $iend--;
                }
            } elseif ($length < 0) {
                // Count all the characters except continuation bytes from the end
                // until we have found abs($start) characters, then backtrace one byte.
                $length = abs($length);
                $iend = $strlen;
                $chars = 0;
                while ($iend > 0 && $chars < $length) {
                    $iend--;
                    $c = ord($text[$iend]);
                    if ($c < 0x80 || $c >= 0xC0) {
                        $chars++;
                    }
                }
                // Backtrace one byte if we are not at the beginning of the string.
                if ($iend > 0) {
                    $iend--;
                }
            } else {
                // $length == 0, return an empty string.
                return '';
            }

            return substr($text, $istart, max(0, $iend - $istart + 1));
        }
    }

  /**
   * Truncates a UTF-8-encoded string safely to a number of characters.
   *
   * @param string $string
   *   The string to truncate.
   * @param int $maxLength
   *   An upper limit on the returned string length, including trailing ellipsis
   *   if $addEllipsis is true.
   * @param bool $wordsafe
   *   If true, attempt to truncate on a word boundary. Word boundaries are
   *   spaces, punctuation, and Unicode characters used as word boundaries in
   *   non-Latin languages; see Unicode::PREG_CLASS_WORD_BOUNDARY for more
   *   information. If a word boundary cannot be found that would make the length
   *   of the returned string fall within length guidelines (see parameters
   *   $maxLength and $minWordsafeLength), word boundaries are ignored.
   * @param bool $addEllipsis
   *   If true, add '...' to the end of the truncated string (defaults to
   *   false). The string length will still fall within $maxLength.
   * @param int $minWordsafeLength
   *   If $wordsafe is true, the minimum acceptable length for truncation (before
   *   adding an ellipsis, if $addEllipsis is true). Has no effect if $wordsafe
   *   is false. This can be used to prevent having a very short resulting string
   *   that will not be understandable. For instance, if you are truncating the
   *   string "See myverylongurlexample.com for more information" to a word-safe
   *   return length of 20, the only available word boundary within 20 characters
   *   is after the word "See", which wouldn't leave a very informative string. If
   *   you had set $minWordsafeLength to 10, though, the function would realise
   *   that "See" alone is too short, and would then just truncate ignoring word
   *   boundaries, giving you "See myverylongurl..." (assuming you had set
   *   $add_ellipses to true).
   *
   * @return string
   *   The truncated string.
   */
    public static function truncate(
        $string,
        $maxLength,
        $wordsafe = false,
        $addEllipsis = false,
        $minWordsafeLength = 1
    ) {
        $ellipsis = '';
        $maxLength = max($maxLength, 0);
        $minWordsafeLength = max($minWordsafeLength, 0);

        if (static::strlen($string) <= $maxLength) {
            // No truncation needed, so don't add ellipsis, just return.
            return $string;
        }

        if ($addEllipsis) {
            // Truncate ellipsis in case $maxLength is small.
            $ellipsis = static::substr('…', 0, $maxLength);
            $maxLength -= static::strlen($ellipsis);
            $maxLength = max($maxLength, 0);
        }

        if ($maxLength <= $minWordsafeLength) {
            // Do not attempt word-safe if lengths are bad.
            $wordsafe = false;
        }

        if ($wordsafe) {
            $matches = [];
            // Find the last word boundary, if there is one within $minWordsafeLength
            // to $maxLength characters. preg_match() is always greedy, so it will
            // find the longest string possible.
            $found = preg_match('/^(.{' . $minWordsafeLength . ',' . $maxLength . '})
            [' . Unicode::PREG_CLASS_WORD_BOUNDARY . ']/u', $string, $matches);
            if ($found) {
                $string = $matches[1];
            } else {
                $string = static::substr($string, 0, $maxLength);
            }
        } else {
            $string = static::substr($string, 0, $maxLength);
        }

        if ($addEllipsis) {
            // If we're adding an ellipsis, remove any trailing periods.
            $string = rtrim($string, '.');

            $string .= $ellipsis;
        }

        return $string;
    }

  /**
   * Finds the position of the first occurrence of a string in another string.
   *
   * @param string $haystack
   *   The string to search in.
   * @param string $needle
   *   The string to find in $haystack.
   * @param int $offset
   *   If specified, start the search at this number of characters from the
   *   beginning (default 0).
   *
   * @return int|false
   *   The position where $needle occurs in $haystack, always relative to the
   *   beginning (independent of $offset), or false if not found. Note that
   *   a return value of 0 is not the same as false.
   */
    public static function strpos($haystack, $needle, $offset = 0)
    {
        if (static::getStatus() == static::STATUS_MULTIBYTE) {
            return mb_strpos($haystack, $needle, $offset);
        } else {
            // Remove Unicode continuation characters, to be compatible with
            // Unicode::strlen() and Unicode::substr().
            $haystack = preg_replace("/[\x80-\xBF]/", '', $haystack);
            $needle = preg_replace("/[\x80-\xBF]/", '', $needle);
            return strpos($haystack, $needle, $offset);
        }
    }
}
