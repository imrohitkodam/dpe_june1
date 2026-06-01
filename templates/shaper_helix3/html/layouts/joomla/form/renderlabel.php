<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('bootstrap.tooltip', '.info-cover', []);

extract($displayData);

/**
 * Layout variables
 * ---------------------
 *  $text         : (string)  The label text
 *  $description  : (string)  An optional description to use in a tooltip
 *  $for          : (string)  The id of the input this label is for
 *  $required     : (boolean) True if a required field
 *  $classes      : (array)   A list of classes
 *  $position     : (string)  The tooltip position. Bottom for alias
 */

$classes = array_filter((array) $classes);

$id = $for . '-lbl';
$title = '';

// Core joomla functionality to render label
if(Factory::getApplication()->input->get('option') != 'com_tjucm')
{

        if (!empty($description))
        {
            if ($text && $text !== $description)
            {
                HTMLHelper::_('bootstrap.popover');
                $classes[] = 'hasPopover';
                $title     = ' title="' . htmlspecialchars(trim($text, ':')) . '"'
                    . ' data-content="'. htmlspecialchars($description) . '"';

                if (!$position && Factory::getLanguage()->isRtl())
                {
                    $position = ' data-placement="left" ';
                }
            }
            else
            {
                HTMLHelper::_('bootstrap.tooltip');
                $classes[] = 'hasTooltip';
                $title     = ' title="' . HTMLHelper::_('tooltipText', trim($text, ':'), $description, 0) . '"';
            }
        }
}
else
{
  $fieldDescription = $description;
  $description = '';

}

if ($required)
{
    $classes[] = 'required';
}
?>
<?php
// Custom tooltip functionality for ucm component
if(Factory::getApplication()->input->get('option') == 'com_tjucm')
{
     if(!empty($fieldDescription)) {?>
        <div class="d-inline-block info-cover">
            <i class="fa fa-info-circle"  title="<?php echo $fieldDescription; ?>"  data-toggle="tooltip" data-content="<?php echo $fieldDescription; ?>" data-original-title="<?php echo $fieldDescription; ?>"></i>
        </div>
    <?php
    }
} ?>
<label id="<?php echo $id; ?>" for="<?php echo $for; ?>"<?php if (!empty($classes)) echo ' class="' . implode(' ', $classes) . '"'; ?><?php echo $title; ?><?php echo $position; ?>>
    <?php echo $text; ?><?php if ($required) : ?><span class="star">&#160;*</span><?php endif; ?>
</label>

