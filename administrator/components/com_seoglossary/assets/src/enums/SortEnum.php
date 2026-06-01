<?php
/**    
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace ParseCsv\enums;
defined('_JEXEC') or die;
class SortEnum extends AbstractEnum {

    const __DEFAULT = self::SORT_TYPE_REGULAR;

    const SORT_TYPE_REGULAR = 'regular';

    const SORT_TYPE_NUMERIC = 'numeric';

    const SORT_TYPE_STRING = 'string';

    private static $sorting = array(
        self::SORT_TYPE_REGULAR => SORT_REGULAR,
        self::SORT_TYPE_STRING => SORT_STRING,
        self::SORT_TYPE_NUMERIC => SORT_NUMERIC,
    );

    public static function getSorting($type) {
        if (array_key_exists($type, self::$sorting)) {
            return self::$sorting[$type];
        }

        return self::$sorting[self::__DEFAULT];
    }
}
