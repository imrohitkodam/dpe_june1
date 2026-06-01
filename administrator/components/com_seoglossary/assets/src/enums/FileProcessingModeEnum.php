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
/**
 * Class FileProcessingEnum
 *
 * @package ParseCsv\enums
 *
 * todo extends a basic enum class after merging #121
 */
class FileProcessingModeEnum {

    const __default = self::MODE_FILE_OVERWRITE;

    const MODE_FILE_APPEND = true;

    const MODE_FILE_OVERWRITE = false;

    public static function getAppendMode($mode) {
        if ($mode == self::MODE_FILE_APPEND) {
            return 'ab';
        }

        return 'wb';
    }
}
