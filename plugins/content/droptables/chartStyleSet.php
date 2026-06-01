<?php
/**
 * Droptables
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 *
 * @package   Droptables
 * @copyright Copyright (C) 2014 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2014 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license   GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') || die('=;)');

/**
 * Class ChartStyleSet
 */
class ChartStyleSet
{
    /**
     * Background Color
     *
     * @var string
     */
    public $backgroundColor;
    /**
     * Border color
     *
     * @var string
     */
    public $borderColor;
    /**
     * Point background color
     *
     * @var string
     */
    public $pointBackgroundColor;
    /**
     * Point stroke color
     *
     * @var string
     */
    public $pointColor;
    /**
     * Pointhighlight fill
     *
     * @var string
     */
    public $pointHighlightFill;
    /**
     * Poin border color
     *
     * @var string
     */
    public $pointBorderColor;
    /**
     * Poin highlight stroke
     *
     * @var string
     */
    public $highlight;

    /**
     * ChartStyleSet constructor.
     *
     * @param string  $color   Color
     * @param boolean $opacity Opacity
     *
     * @return void
     */
    public function __construct($color, $opacity = false)
    {
        if ($opacity === false) {
            $opacity = 0;
        }
        $this->backgroundColor = $this->hex2rgba($color, 0.7 + $opacity);
        $this->borderColor = $this->hex2rgba($color, 0.8 + $opacity);
        $this->pointBackgroundColor = $this->hex2rgba($color, 0.5 + $opacity);
        $this->pointColor = '#fff';
        $this->pointHighlightFill = '#fff';
        $this->pointBorderColor = $this->hex2rgba($color, 0.4 + $opacity);
        $this->highlight = $this->hex2rgba($color, 1 + $opacity);
    }

    /**
     * Get hex rgba color
     *
     * @param string  $color   Color
     * @param boolean $opacity Opacity
     *
     * @return string
     */
    public function hex2rgba($color, $opacity = false)
    {
        $default = 'rgb(0,0,0)';

        //Return default if no color provided
        if (empty($color)) {
            return $default;
        }
            //Sanitize $color if "#" is provided
        if ($color[0] === '#') {
            $color = substr($color, 1);
        }

        //Check if color has 6 or 3 characters and get values
        if (strlen($color) === 6) {
            $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) === 3) {
            $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return $default;
        }

        //Convert hexadec to rgb
        $rgb = array_map('hexdec', $hex);

        //Check if opacity is set(rgba or rgb)
        if ($opacity) {
            if (abs($opacity) > 1) {
                $opacity = 1.0;
            }
            $output = 'rgba(' . implode(',', $rgb) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode(',', $rgb) . ')';
        }

        //Return rgb(a) color string
        return $output;
    }
}
