<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
use Joomla\CMS\Factory;
defined('_JEXEC') or die;

$id       = empty($displayData['id']) ? '' : $displayData['id'];
$active   = empty($displayData['active']) ? '' : $displayData['active'];
$title    = empty($displayData['title']) ? '' : $displayData['title'];
$selector = empty($displayData['selector']) ? '' : $displayData['selector'];

$li = '<li class=" nav-item' . $active . '"><a href="#' . $id . '" data-bs-toggle="tab" role="tab"aria-controls= "' . $id . '" aria-selected="true" class="nav-link' .$active.'">' . $title . '</a></li>';

?>

<script >
    jQuery('document').ready(function() {

        if ( jQuery(".sppb-addon-content").length && jQuery(".checklist_outer").length) {   

            jQuery('#'+ '<?php echo $selector . 'Tabs';?>').append('<?php echo $li; ?>')

            }
         });
</script>

<div id="<?php echo preg_replace('/^[\.#]/', '', $id); ?>"
    class="tab-pane<?php echo $active; ?>"
    data-active="<?php echo trim(htmlspecialchars($active, ENT_COMPAT, 'UTF-8')); ?>"
    data-id="<?php echo  htmlspecialchars($id, ENT_COMPAT, 'UTF-8'); ?>"
    data-title="<?php echo htmlspecialchars($title, ENT_COMPAT, 'UTF-8'); ?>">
