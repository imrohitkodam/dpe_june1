<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

/*
 * @package     Extly Infrastructure Support
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2021 Extly, CB. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see         https://www.extly.com
 */

namespace XTP_BUILD\Extly\Infrastructure\Support;

use XTP_BUILD\Extly\Infrastructure\Creator\CreatorTrait;

class HtmlTagsCleaner
{
    use CreatorTrait;

    private $source;

    private $tagBlacklist = [];

    private $tagsArray = [];

    private $tagsMethod = 0;

    public function __construct($source)
    {
        $this->source = $this->remove($this->decode((string) $source));
    }

    public function __toString()
    {
        return $this->source;
    }

    /**
     * Internal method to strip a string of certain tags.
     *
     * @param string $source Input string to be 'cleaned'
     *
     * @return string 'Cleaned' version of input parameter
     */
    protected function cleanTags($source)
    {
        // First, pre-process this for illegal characters inside attribute values
        $source = $this->escapeAttributeValues($source);

        // In the beginning we don't really have a tag, so everything is postTag
        $preTag = null;
        $postTag = $source;

        // Setting to null to deal with undefined variables
        $attr = '';

        // Is there a tag? If so it will certainly start with a '<'.
        $tagOpen_start = strpos($source, '<');

        while (false !== $tagOpen_start) {
            // Get some information about the tag we are processing
            $preTag .= substr($postTag, 0, $tagOpen_start);
            $postTag = substr($postTag, $tagOpen_start);
            $fromTagOpen = substr($postTag, 1);
            $tagOpen_end = strpos($fromTagOpen, '>');

            // Check for mal-formed tag where we have a second '<' before the first '>'
            $nextOpenTag = (\strlen($postTag) > $tagOpen_start) ? strpos($postTag, '<', $tagOpen_start + 1) : false;

            if ((false !== $nextOpenTag) && ($nextOpenTag < $tagOpen_end)) {
                // At this point we have a mal-formed tag -- remove the offending open
                $postTag = substr($postTag, 0, $tagOpen_start).substr($postTag, $tagOpen_start + 1);
                $tagOpen_start = strpos($postTag, '<');

                continue;
            }

            // Let's catch any non-terminated tags and skip over them
            if (false === $tagOpen_end) {
                $postTag = substr($postTag, $tagOpen_start + 1);
                $tagOpen_start = strpos($postTag, '<');

                continue;
            }

            // Do we have a nested tag?
            $tagOpen_nested = strpos($fromTagOpen, '<');

            if ((false !== $tagOpen_nested) && ($tagOpen_nested < $tagOpen_end)) {
                $preTag .= substr($postTag, 0, ($tagOpen_nested + 1));
                $postTag = substr($postTag, ($tagOpen_nested + 1));
                $tagOpen_start = strpos($postTag, '<');

                continue;
            }

            // Let's get some information about our tag and setup attribute pairs
            $tagOpen_nested = (strpos($fromTagOpen, '<') + $tagOpen_start + 1);
            $currentTag = substr($fromTagOpen, 0, $tagOpen_end);
            $tagLength = \strlen($currentTag);
            $tagLeft = $currentTag;
            $attrSet = [];
            $currentSpace = strpos($tagLeft, ' ');

            // Are we an open tag or a close tag?
            if ('/' === substr($currentTag, 0, 1)) {
                // Close Tag
                $isCloseTag = true;
                list($tagName) = explode(' ', $currentTag);
                $tagName = substr($tagName, 1);
            } else {
                // Open Tag
                $isCloseTag = false;
                list($tagName) = explode(' ', $currentTag);
            }

            /*
             * Exclude all "non-regular" tagnames
             * OR no tagname
             * OR remove if xssauto is on and tag is blacklisted
             */
            if ((!preg_match('/^[a-z][a-z0-9]*$/i', $tagName)) || (!$tagName) || ((\in_array(strtolower($tagName), $this->tagBlacklist, true)) && ($this->xssAuto))) {
                $postTag = substr($postTag, ($tagLength + 2));
                $tagOpen_start = strpos($postTag, '<');

                // Strip tag
                continue;
            }

            /*
             * Time to grab any attributes from the tag... need this section in
             * case attributes have spaces in the values.
             */
            while (false !== $currentSpace) {
                $attr = '';
                $fromSpace = substr($tagLeft, ($currentSpace + 1));
                $nextEqual = strpos($fromSpace, '=');
                $nextSpace = strpos($fromSpace, ' ');
                $openQuotes = strpos($fromSpace, '"');
                $closeQuotes = strpos(substr($fromSpace, ($openQuotes + 1)), '"') + $openQuotes + 1;
                $startAtt = '';
                $startAttPosition = 0;

                // Find position of equal and open quotes ignoring
                if (preg_match('#\s*=\s*\"#', $fromSpace, $matches, PREG_OFFSET_CAPTURE)) {
                    $startAtt = $matches[0][0];
                    $startAttPosition = $matches[0][1];
                    $closeQuotes = strpos(substr($fromSpace, ($startAttPosition + \strlen($startAtt))), '"') + $startAttPosition + \strlen($startAtt);
                    $nextEqual = $startAttPosition + strpos($startAtt, '=');
                    $openQuotes = $startAttPosition + strpos($startAtt, '"');
                    $nextSpace = strpos(substr($fromSpace, $closeQuotes), ' ') + $closeQuotes;
                }

                // Do we have an attribute to process? [check for equal sign]
                if ('/' !== $fromSpace && (($nextEqual && $nextSpace && $nextSpace < $nextEqual) || !$nextEqual)) {
                    if (!$nextEqual) {
                        $attribEnd = strpos($fromSpace, '/') - 1;
                    } else {
                        $attribEnd = $nextSpace - 1;
                    }

                    // If there is an ending, use this, if not, do not worry.
                    if ($attribEnd > 0) {
                        $fromSpace = substr($fromSpace, $attribEnd + 1);
                    }
                }

                if (false !== strpos($fromSpace, '=')) {
                    /*
                     * If the attribute value is wrapped in quotes we need to grab the substring from
                     * the closing quote, otherwise grab until the next space.
                     */
                    if ((false !== $openQuotes) && (false !== strpos(substr($fromSpace, ($openQuotes + 1)), '"'))) {
                        $attr = substr($fromSpace, 0, ($closeQuotes + 1));
                    } else {
                        $attr = substr($fromSpace, 0, $nextSpace);
                    }
                } else {
                    // No more equal signs so add any extra text in the tag into the attribute array [eg. checked]
                    if ('/' !== $fromSpace) {
                        $attr = substr($fromSpace, 0, $nextSpace);
                    }
                }

                // Last Attribute Pair
                if (!$attr && '/' !== $fromSpace) {
                    $attr = $fromSpace;
                }

                // Add attribute pair to the attribute array
                $attrSet[] = $attr;

                // Move search point and continue iteration
                $tagLeft = substr($fromSpace, \strlen($attr));
                $currentSpace = strpos($tagLeft, ' ');
            }

            // Is our tag in the user input array?
            $tagFound = \in_array(strtolower($tagName), $this->tagsArray, true);

            // If the tag is allowed let's append it to the output string.
            if ((!$tagFound && $this->tagsMethod) || ($tagFound && !$this->tagsMethod)) {
                // Reconstruct tag with allowed attributes
                if (!$isCloseTag) {
                    // Open or single tag
                    $attrSet = $this->_cleanAttributes($attrSet);
                    $preTag .= '<'.$tagName;
                    for ($i = 0, $count = \count($attrSet); $i < $count; ++$i) {
                        $preTag .= ' '.$attrSet[$i];
                    }

                    // Reformat single tags to XHTML
                    if (strpos($fromTagOpen, '</'.$tagName)) {
                        $preTag .= '>';
                    } else {
                        $preTag .= ' />';
                    }
                } else {
                    // Closing tag
                    $preTag .= '</'.$tagName.'>';
                }
            }

            // Find next tag's start and continue iteration
            $postTag = substr($postTag, ($tagLength + 2));
            $tagOpen_start = strpos($postTag, '<');
        }

        // Append any code after the end of tags and return
        if ('<' !== $postTag) {
            $preTag .= $postTag;
        }

        return $preTag;
    }

    /**
     * Try to convert to plaintext.
     *
     * @param string $source the source string
     *
     * @return string Plaintext string
     */
    private function decode($source)
    {
        static $ttr;

        if (!\is_array($ttr)) {
            // Entity decode
            $trans_tbl = get_html_translation_table(HTML_ENTITIES, ENT_COMPAT, 'ISO-8859-1');

            foreach ($trans_tbl as $k => $v) {
                $ttr[$v] = utf8_encode($k);
            }
        }

        $source = strtr($source, $ttr);

        // Convert decimal
        $source = preg_replace_callback(
            '/&#(\d+);/m',
            function ($m) {
                return utf8_encode(\chr($m[1]));
            },
            $source
        );

        // Convert hex
        $source = preg_replace_callback(
            '/&#x([a-f0-9]+);/mi',
            function ($m) {
                return utf8_encode(\chr('0x'.$m[1]));
            },
            $source
        );

        return $source;
    }

    /**
     * Method to iteratively remove all unwanted tags and attributes.
     *
     * @param string $source Input string to be 'cleaned'
     *
     * @return string 'Cleaned' version of input parameter
     */
    private function remove($source)
    {
        // Iteration provides nested tag protection
        do {
            $temp = $source;
            $source = $this->cleanTags($source);
        } while ($temp !== $source);

        return $source;
    }

    /**
     * Escape < > and " inside attribute values.
     *
     * @param string $source the source string
     *
     * @return string Filtered string
     */
    private function escapeAttributeValues($source)
    {
        $alreadyFiltered = '';
        $remainder = $source;
        $badChars = ['<', '"', '>'];
        $escapedChars = ['&lt;', '&quot;', '&gt;'];

        /*
         * Process each portion based on presence of =" and "<space>, "/>, or ">
         * See if there are any more attributes to process
         */
        while (preg_match('#<[^>]*?=\s*?(\"|\')#s', $remainder, $matches, PREG_OFFSET_CAPTURE)) {
            // Get the portion before the attribute value
            $quotePosition = $matches[0][1];
            $nextBefore = $quotePosition + \strlen($matches[0][0]);

            /*
             * Figure out if we have a single or double quote and look for the matching closing quote
             * Closing quote should be "/>, ">, "<space>, or " at the end of the string
             */
            $quote = substr($matches[0][0], -1);
            $pregMatch = ('"' === $quote) ? '#(\"\s*/\s*>|\"\s*>|\"\s+|\"$)#' : "#(\\'\\s*/\\s*>|\\'\\s*>|\\'\\s+|\\'$)#";

            // Get the portion after attribute value
            if (preg_match($pregMatch, substr($remainder, $nextBefore), $matches, PREG_OFFSET_CAPTURE)) {
                // We have a closing quote
                $nextAfter = $nextBefore + $matches[0][1];
            } else {
                // No closing quote
                $nextAfter = \strlen($remainder);
            }

            // Get the actual attribute value
            $attributeValue = substr($remainder, $nextBefore, $nextAfter - $nextBefore);

            // Escape bad chars
            $attributeValue = str_replace($badChars, $escapedChars, $attributeValue);
            $attributeValue = $this->stripCSSExpressions($attributeValue);
            $alreadyFiltered .= substr($remainder, 0, $nextBefore).$attributeValue.$quote;
            $remainder = substr($remainder, $nextAfter + 1);
        }

        // At this point, we just have to return the $alreadyFiltered and the $remainder
        return $alreadyFiltered.$remainder;
    }
}
