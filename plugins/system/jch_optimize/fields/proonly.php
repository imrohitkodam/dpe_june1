<?php

/**
 * JCH Optimize - Performs several front-end optimizations for fast downloads
 *
 * @package   jchoptimize/joomla-platform
 * @author    Samuel Marshall <samuel@jch-optimize.net>
 * @copyright Copyright (c) 2020 Samuel Marshall / JCH Optimize
 * @license   GNU/GPLv3, or later. See LICENSE file
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */

defined('_JEXEC') or die;

class JFormFieldProonly extends JFormField {
    
    public $type = 'ProOnly';

    protected function getInput() {
        return '<fieldset style="padding: 5px 5px 0 0"><em>Only available in Pro Version!</em></fieldset>';
    }

}
?>
