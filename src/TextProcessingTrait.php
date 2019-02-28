<?php

namespace CultuurNet\SearchV3Utilities;

/**
 * Text processing trait.
 */
trait TextProcessingTrait
{

  /**
   * Create a summary for the given text.
   *
   * @param $text
   * @param int $size
   *
   * @return bool|string
   */
    public function createSummary($text, $size = 300)
    {

        // If the size is zero, the entire body is the summary.
        if ($size == 0) {
            return $text;
        }

        // If we have a short body, the entire body is the summary.
        if (Unicode::strlen($text) <= $size) {
            return $text;
        }

        // If the delimiter has not been specified, try to split at paragraph or
        // sentence boundaries.
        // The summary may not be longer than maximum length specified. Initial slice.
        $summary = Unicode::truncate($text, $size);

        // Store the actual length of the UTF8 string -- which might not be the same
        // as $size.
        $maxRpos = strlen($summary);

        // How much to cut off the end of the summary so that it doesn't end in the
        // middle of a paragraph, sentence, or word.
        // Initialize it to maximum in order to find the minimum.
        $minRpos = $maxRpos;

        // Store the reverse of the summary. We use strpos on the reversed needle and
        // haystack for speed and convenience.
        $reversed = strrev($summary);

        // Build an array of arrays of break points grouped by preference.
        $breakPoints = [];

        // A paragraph near the end of sliced summary is most preferable.
        $breakPoints[] = ['</p>' => 0];

        // If no complete paragraph then treat line breaks as paragraphs.
        $lineBreaks = ['<br />' => 6, '<br>' => 4];
        $breakPoints[] = $lineBreaks;

        // If the first paragraph is too long, split at the end of a sentence.
        $breakPoints[] = ['. ' => 1, '! ' => 1, '? ' => 1, '。' => 0, '؟ ' => 1];

        // Iterate over the groups of break points until a break point is found.
        foreach ($breakPoints as $points) {
            // Look for each break point, starting at the end of the summary.
            foreach ($points as $point => $offset) {
                // The summary is already reversed, but the break point isn't.
                $rpos = strpos($reversed, strrev($point));
                if ($rpos !== false) {
                    $minRpos = min($rpos + $offset, $minRpos);
                }
            }

            // If a break point was found in this group, slice and stop searching.
            if ($minRpos !== $maxRpos) {
                // Don't slice with length 0. Length must be <0 to slice from RHS.
                $summary = ($minRpos === 0) ? $summary : substr($summary, 0, 0 - $minRpos);
                break;
            }
        }

        return $summary;
    }

  /**
   * Create a summary respecting the html tags.
   *
   * @param string $html
   * @param int $length
   * @param string $ending
   *
   * @return mixed|null|string|string[]
   */
    public function createHtmlSummary($html = '', $length = 100, $ending = '...')
    {

        if (mb_strlen(strip_tags($html)) <= $length) {
            return $html;
        }
        $total = mb_strlen($ending);
        $openTags = [];
        $return = '';
        $finished = false;
        $finalSegment = '';
        $selfClosingElements = [
        'area',
        'base',
        'br',
        'col',
        'frame',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        ];
        $inlineContainers = [
        'a',
        'b',
        'abbr',
        'cite',
        'em',
        'i',
        'kbd',
        'span',
        'strong',
        'sub',
        'sup',
        ];
        while (!$finished) {
            // Does the remaining string start in an opening tag?
            if (preg_match('/^<(\w+)[^>]*>/', $html, $matches)) {
                // If not self-closing, place tag in $openTags array:
                if (!in_array($matches[1], $selfClosingElements)) {
                    $openTags[] = $matches[1];
                }
                // Remove tag from $html:
                $html = substr_replace($html, '', 0, strlen($matches[0]));
                // Add tag to $return:
                $return .= $matches[0];
                // Does the remaining string start in an end tag?
            } elseif (preg_match('/^<\/(\w+)>/', $html, $matches)) {
                // Remove matching opening tag from $openTags array:
                $key = array_search($matches[1], $openTags);
                if ($key !== false) {
                    unset($openTags[$key]);
                }
                // Remove tag from $html:
                $html = substr_replace($html, '', 0, strlen($matches[0]));
                // Add tag to $return:
                $return .= $matches[0];
            } else {
                // Extract text up to next tag as $segment:
                if (preg_match('/^([^<]+)(<\/?(\w+)[^>]*>)?/', $html, $matches)) {
                    $segment = $matches[1];
                    // Following code taken from
                    // https://trac.cakephp.org/browser/tags/1.2.1.8004/cake/libs/view/helpers/text.php?rev=8005.
                    // Not 100% sure about it, but assume it deals with
                    // utf and html entities/multi-byte characters to get accureate string length.
                    $segmentLength = mb_strlen(
                        preg_replace(
                            '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i',
                            ' ',
                            $segment
                        )
                    );
                    // Compare $segmentLength + $total to $length:
                    // Truncate $segment and set as $finalSegment:
                    if ($segmentLength + $total > $length) {
                        $remainder = $length - $total;
                        $entitiesLength = 0;
                        if (preg_match_all(
                            '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i',
                            $segment,
                            $entities,
                            PREG_OFFSET_CAPTURE
                        )
                        ) {
                            foreach ($entities[0] as $entity) {
                                if ($entity[1] + 1 - $entitiesLength <= $remainder) {
                                    $remainder--;
                                    $entitiesLength += mb_strlen($entity[0]);
                                } else {
                                    break;
                                }
                            }
                        }
                        // Otherwise truncate $segment and set as $finalSegment:
                        $finished = true;
                        $finalSegment = mb_substr($segment, 0, $remainder + $entitiesLength);
                    } else {
                        // Add $segment to $return and increase $total:
                        $return .= $segment;
                        $total += $segmentLength;
                        // Remove $segment from $html:
                        $html = substr_replace($html, '', 0, strlen($segment));
                    }
                } else {
                    $finished = true;
                }
            }
        }
        // Check for spaces in $finalSegment:
        // If none and $return ends in an opening tag: (we ignore $finalSegment)
        if (strpos($finalSegment, ' ') === false && preg_match('/<(\w+)[^>]*>$/', $return)) {
            // Remove opening tag from end of $return:
            $return = preg_replace('/<(\w+)[^>]*>$/', '', $return);
            // Remove opening tag from $openTags:
            $key = array_search($matches[3], $openTags);
            if ($key !== false) {
                unset($openTags[$key]);
            }
            // Otherwise, truncate $finalSegment to last space and add to $return:
        } else {
            // $spacepos = strrpos($finalSegment, ' ');.
            $return .= mb_substr($finalSegment, 0, mb_strrpos($finalSegment, ' '));
        }
        $return = trim($return);
        $len = strlen($return);
        $lastChar = substr($return, $len - 1, 1);
        if (!preg_match('/[a-zA-Z0-9]/', $lastChar)) {
            $return = substr_replace($return, '', $len - 1, 1);
        }
        // Add closing tags:
        $closingTags = array_reverse($openTags);
        $endingAdded = false;
        foreach ($closingTags as $tag) {
            if (!in_array($tag, $inlineContainers) && !$endingAdded) {
                $return .= $ending;
                $endingAdded = true;
            }
            $return .= '</' . $tag . '>';
        }
        return $return;
    }

  /**
   * Filter the given string for XSS.
   *
   * @param $string
   * @param $allowedTags
   *
   * @return mixed
   */
    protected function filterXss(
        $string,
        $allowedTags = [
      'a',
      'div',
      'hr',
      'p',
      'img',
      'em',
      'strong',
      'cite',
      'blockquote',
      'code',
      'ul',
      'ol',
      'li',
      'dl',
      'dt',
      'dd',
      'table',
      'tbody',
      'td',
      'tr',
      'style',
        ]
    ) {
        // Remove NULL characters (ignored by some browsers).
        $string = str_replace(chr(0), '', $string);
        // Remove Netscape 4 JS entities.
        $string = preg_replace('%&\s*\{[^}]*(\}\s*;?|$)%', '', $string);

        // Defuse all HTML entities.
        $string = str_replace('&', '&amp;', $string);
        // Change back only well-formed entities in our whitelist:
        // Decimal numeric entities.
        $string = preg_replace('/&amp;#([0-9]+;)/', '&#\1', $string);
        // Hexadecimal numeric entities.
        $string = preg_replace('/&amp;#[Xx]0*((?:[0-9A-Fa-f]{2})+;)/', '&#x\1', $string);
        // Named entities.
        $string = preg_replace('/&amp;([A-Za-z][A-Za-z0-9]*;)/', '&\1', $string);
        $htmlTags = array_flip($allowedTags);
        // Late static binding does not work inside anonymous functions.
        $class = get_called_class();
        $splitter = function ($matches) use ($htmlTags, $class) {
            return $class::filterXssSplit($matches[1], $htmlTags, $class);
        };
        // Strip any tags that are not in the whitelist.
        return preg_replace_callback(
            '%
      (
      <(?=[^a-zA-Z!/])  # a lone <
      |                 # or
      <!--.*?-->        # a comment
      |                 # or
      <[^>]*(>|$)       # a string that starts with a <, up until the > or the end of the string
      |                 # or
      >                 # just a >
      )%x',
            $splitter,
            $string
        );
    }

  /**
   * Processes an HTML tag.
   *
   * @param string $string
   *   The HTML tag to process.
   * @param array $htmlTags
   *   An array where the keys are the allowed tags and the values are not
   *   used.
   * @param string $class
   *   The called class. This method is called from an anonymous function which
   *   breaks late static binding. See https://bugs.php.net/bug.php?id=66622 for
   *   more information.
   *
   * @return string
   *   If the element isn't allowed, an empty string. Otherwise, the cleaned up
   *   version of the HTML element.
   */
    protected static function filterXssSplit($string, $htmlTags, $class)
    {
        if (substr($string, 0, 1) != '<') {
            // We matched a lone ">" character.
            return '&gt;';
        } elseif (strlen($string) == 1) {
            // We matched a lone "<" character.
            return '&lt;';
        }

        if (!preg_match('%^<\s*(/\s*)?([a-zA-Z0-9\-]+)\s*([^>]*)>?|(<!--.*?-->)$%', $string, $matches)) {
            // Seriously malformed.
            return '';
        }
        $slash = trim($matches[1]);
        $elem = &$matches[2];
        $attrlist = &$matches[3];
        $comment = &$matches[4];

        if ($comment) {
            $elem = '!--';
        }

        // When in whitelist mode, an element is disallowed when not listed.
        if (!isset($htmlTags[strtolower($elem)])) {
            return '';
        }

        if ($comment) {
            return $comment;
        }

        if ($slash != '') {
            return "</$elem>";
        }

        // Is there a closing XHTML slash at the end of the attributes?
        $attrlist = preg_replace('%(\s?)/\s*$%', '\1', $attrlist, -1, $count);
        $xhtmlSlash = $count ? ' /' : '';

        // Clean up attributes.
        $attr2 = implode(' ', $class::filterXssAttributes($attrlist));
        $attr2 = preg_replace('/[<>]/', '', $attr2);
        $attr2 = strlen($attr2) ? ' ' . $attr2 : '';

        return "<$elem$attr2$xhtmlSlash>";
    }

  /**
   * Processes a string of HTML attributes.
   *
   * @param string $attributes
   *   The html attribute to process.
   *
   * @return array
   *   Cleaned up version of the HTML attributes.
   */
    protected static function filterXssAttributes($attributes)
    {
        $attributesArray = [];
        $mode = 0;
        $attributeName = '';
        $skip = false;
        $skipProtocolFiltering = false;

        while (strlen($attributes) != 0) {
            // Was the last operation successful?
            $working = 0;

            switch ($mode) {
                case 0:
                    // Attribute name, href for instance.
                    if (preg_match('/^([-a-zA-Z][-a-zA-Z0-9]*)/', $attributes, $match)) {
                        $attributeName = strtolower($match[1]);
                        $skip = (substr($attributeName, 0, 2) == 'on');

                        // Values for attributes of type URI should be filtered for
                        // potentially malicious protocols (for example, an href-attribute
                        // starting with "javascript:"). However, for some non-URI
                        // attributes performing this filtering causes valid and safe data
                        // to be mangled. We prevent this by skipping protocol filtering on
                        // such attributes.
                        // @see \Drupal\Component\Utility\UrlHelper::filterBadProtocol()
                        // @see http://www.w3.org/TR/html4/index/attributes.html
                        $attributeNamePart = substr($attributeName, 0, 5);
                        $skipProtocolFiltering = $attributeNamePart === 'data-' || in_array(
                            $attributeName,
                            [
                            'title',
                            'alt',
                            'rel',
                            'property',
                            ]
                        );

                        $working = $mode = 1;
                        $attributes = preg_replace('/^[-a-zA-Z][-a-zA-Z0-9]*/', '', $attributes);
                    }
                    break;

                case 1:
                    // Equals sign or valueless ("selected").
                    if (preg_match('/^\s*=\s*/', $attributes)) {
                        $working = 1;
                        $mode = 2;
                        $attributes = preg_replace('/^\s*=\s*/', '', $attributes);
                        break;
                    }

                    if (preg_match('/^\s+/', $attributes)) {
                        $working = 1;
                        $mode = 0;
                        if (!$skip) {
                            $attributesArray[] = $attributeName;
                        }
                        $attributes = preg_replace('/^\s+/', '', $attributes);
                    }
                    break;

                case 2:
                    // Attribute value, a URL after href= for instance.
                    if (preg_match('/^"([^"]*)"(\s+|$)/', $attributes, $match)) {
                        $thisval = $match[1];

                        if (!$skip) {
                            $attributesArray[] = "$attributeName=\"$thisval\"";
                        }
                        $working = 1;
                        $mode = 0;
                        $attributes = preg_replace('/^"[^"]*"(\s+|$)/', '', $attributes);
                        break;
                    }

                    if (preg_match("/^'([^']*)'(\s+|$)/", $attributes, $match)) {
                        $thisval = $match[1];

                        if (!$skip) {
                            $attributesArray[] = "$attributeName='$thisval'";
                        }
                        $working = 1;
                        $mode = 0;
                        $attributes = preg_replace("/^'[^']*'(\s+|$)/", '', $attributes);
                        break;
                    }

                    if (preg_match("%^([^\s\"']+)(\s+|$)%", $attributes, $match)) {
                        $thisval = $match[1];

                        if (!$skip) {
                            $attributesArray[] = "$attributeName=\"$thisval\"";
                        }
                        $working = 1;
                        $mode = 0;
                        $attributes = preg_replace("%^[^\s\"']+(\s+|$)%", '', $attributes);
                    }
                    break;
            }

            if ($working == 0) {
                // Not well formed; remove and try again.
                $attributes = preg_replace(
                    '/
          ^
          (
          "[^"]*("|$)     # - a string that starts with a double quote, til next double quote or end of the string
          |               # or
          \'[^\']*(\'|$)| # - a string that starts with a quote, up until the next quote or the end of the string
          |               # or
          \S              # - a non-whitespace character
          )*              # any number of the above three
          \s*             # any number of whitespaces
          /x',
                    '',
                    $attributes
                );
                $mode = 0;
            }
        }

        // The attribute list ends with a valueless attribute like "selected".
        if ($mode == 1 && !$skip) {
            $attributesArray[] = $attributeName;
        }
        return $attributesArray;
    }
}
