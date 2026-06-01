<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;


/**
 * Make thing clear
 *
 * @var Form   $form       The form instance for render the section
 * @var string  $basegroup  The base group name
 * @var string  $group      Current group name
 * @var array   $buttons    Array of the buttons that will be rendered
 */
extract($displayData);
JLoader::import('tjfields', JPATH_SITE . '/components/com_tjfields/helpers/');
$layout  = new FileLayout('subformrop', JPATH_SITE . '/templates/shaper_helix3/html/layouts/com_tjucm/form');
	$i = 0;
		
$options_str = '{"disable_search_threshold":10,"search_contains":true,"allow_single_deselect":true,"placeholder_text_multiple":"Type or select some options","placeholder_text_single":"Select an option","no_results_text":"No results match"}';
$selector = 'select';
	
// Options array to json options string

JFactory::getDocument()->addScriptDeclaration(
	'
	jQuery(function ($) {
		initChosen();
		jQuery("body").on("subform-row-add", initChosen);

		function initChosen(event, container)
		{
			container = container || document;
			jQuery(container).find(' . json_encode($selector) . ').chosen(' . $options_str . ');
		}
	});
	'
);
?>

<div
	class="subform-repeatable-group subform-repeatable-group-<?php echo $unique_subform_id; ?>"
	data-base-name="<?php echo $basegroup; ?>"
	data-group="<?php echo $group; ?>"
>

<?php

$fieldArray = array();
$tjFieldHelper = new TjfieldsHelper;

foreach ($form->getGroup('') as $field)
{
	$fieldId = $tjFieldHelper->getFieldIdFromName($field->fieldname);

	$tags = new TagsHelper;
	$tags->getItemTags('com_tjfields.field', $fieldId);
	
	$tagId = $tags->itemTags[0]->tag_id;

	if(!empty($tagId))
	{
		$form->setFieldAttribute($field->fieldname, 'tags', $tagId);
	}		
}

foreach ($form->getGroup('') as $field)
{
		if (!empty($field->getAttribute('tags'))) {
			$temp     = new TagsHelper;
			$tagnames = $temp->getTagNames(array(
				$field->getAttribute('tags')
			));
		
			if (array_key_exists($fieldsArray, (array) $tagnames[0])) {
				$fieldArray[$tagnames[0]][] = $field;
			} else {
				$fieldArray[$tagnames[0]][] = $field;
			}
		} else {
			$fieldArray[] = $field;
		}	
}

 $formName = array('formName'=> $form->getName());
 foreach ($fieldArray as $key => $field) : 
 	
	if(!is_array($field))
	{  
		
		 echo $field->renderField($formName); 
		 echo $layout->render($field);
    }?>
		<?php 	
		if(is_array($field))
		{
			?>
			<div class="w-100">
			<div class="accordion" id="accordion<?php echo $i; ?>"><?php echo  ucwords(str_replace('_', ' ', $key)); ?></div>
			<div id="pan" class="panel">

			<?php 
				foreach($field as $fields)
				{
					echo $fields->renderField($formName);
					echo $layout->render($fields);
			}			 
			?>
			</div>
			</div>
			<div class="clearfix"></div>	
		<?php 	


		$i++;
		} ?>


<?php 
endforeach; ?>

	<?php if (!empty($buttons)) : ?>
	<div class="btn-toolbar text-right">
		<div class="btn-group">
			<?php if (!empty($buttons['add'])) : ?>
				<a class="btn btn-mini button btn-success group-add group-add-<?php echo $unique_subform_id; ?>" aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>">
					<span class="fa fa-plus" aria-hidden="true"></span>
				</a>
			<?php endif; ?>
			<?php if (!empty($buttons['remove'])) : ?>
				<a class="btn btn-mini button btn-danger group-remove group-remove-<?php echo $unique_subform_id; ?>" aria-label="<?php echo Text::_('JGLOBAL_FIELD_REMOVE'); ?>">
					<span class="fa fa-minus" aria-hidden="true"></span>
				</a>
			<?php endif; ?>
			<?php if (!empty($buttons['move'])) : ?>
				<a class="btn btn-mini button btn-primary group-move group-move-<?php echo $unique_subform_id; ?>" aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE'); ?>">
					<span class="icon-move" aria-hidden="true"></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

</div>
<div class="clearfix"></div>	
 