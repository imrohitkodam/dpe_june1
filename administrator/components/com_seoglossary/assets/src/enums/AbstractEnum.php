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

// No direct access
namespace ParseCsv\enums;
defined('_JEXEC') or die;
abstract class AbstractEnum {

    /**
     * Creates a new value of some type
     *
     * @param mixed $value
     *
     * @throws \UnexpectedValueException if incompatible type is given.
     */
    public function __construct($value) {
        if (!$this->isValid($value)) {
            throw new \UnexpectedValueException("Value '$value' is not part of the enum " . get_called_class());
        }
        $this->value = $value;
    }

    public static function getConstants() {
        $class = get_called_class();
        $reflection = new \ReflectionClass($class);

        return $reflection->getConstants();
    }

    /**
     * Check if enum value is valid
     *
     * @param $value
     *
     * @return bool
     */
    public static function isValid($value) {
        return in_array($value, static::getConstants(), true);
    }
}
