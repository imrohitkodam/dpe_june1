<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         9.2.5
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

extract($displayData);

/**
 * @var   object $item
 */
?>

<div id="progress" class="progress">
    <div data-extension="<?php echo $item->alias; ?>" data-url="<?php echo base64_encode($item->downloadurl_pro ?: $item->downloadurl); ?>"
         class="progress-bar progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
</div>
