<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2014 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
extract($displayData);

/**
 * Layout variables
 * ---------------------
 * 	$options         : (array)  Optional parameters
 * 	$label           : (string) The html code for the label (not required if $options['hiddenLabel'] is true)
 * 	$input           : (string) The input field html code
 */

if (!empty($options['showonEnabled']))
{
	HTMLHelper::_('jquery.framework');
	HTMLHelper::_('script', 'jui/cms.js', array('version' => 'auto', 'relative' => true));
}

$class = empty($options['class']) ? '' : ' ' . $options['class'];
$rel   = empty($options['rel']) ? '' : ' ' . $options['rel'];

$labelClasses = "control-label";
$inputClasses = "controls";
$controlGroup = "control-group ";

if(Factory::getApplication()->input->get('option') == 'com_tjucm')
{
	if (Factory::getApplication()->input->get('extralayout') == 'rop')
	{
		$labelClasses .= ' col-sm-12 w-100 text-left';
		$inputClasses = ' col-sm-12 rop-inputs w-100';
		$controlGroup .= ' data-subject-field row';
	}
	elseif (Factory::getApplication()->input->get('extralayout') == 'sarlog')
	{
		if (stripos($options['formName'], "subform") === false) 
		{
   			$labelClasses = ' col-sm-4';
			$inputClasses = ' col-sm-5 custom-calender';
   		}
		
		$controlGroup .= ' data-subject-field row';
	}
	elseif (Factory::getApplication()->input->get('extralayout') == 'default')
	{
		if (stripos($options['formName'], "subform") === false) 
		{
   			$labelClasses = ' col-sm-4';
			$inputClasses = ' col-sm-5 custom-calender';
   		}
		$controlGroup .= ' data-subject-field row';
	}
	elseif (Factory::getApplication()->input->get('extralayout') == 'vendors')
	{
		$labelClasses = ' col-sm-12 control-label w-100 text-left';
		$inputClasses = ' col-sm-12 rop-inputs w-100 onboardusers';
		$controlGroup .= ' data-subject-field row';
	}
	elseif (Factory::getApplication()->input->get('extralayout') == 'checklist')
	{
		if (stripos($options['formName'], "subform") === false) 
		{
		$labelClasses = ' col-sm-9';
		$inputClasses = ' col-sm-3 pt-0 px-0 custom-calender';
	}
		$controlGroup .= ' data-subject-field row';
	}
}

?>
<div class="<?php echo $controlGroup; ?> <?php echo $class; ?>"<?php echo $rel; ?>>
	<?php if (empty($options['hiddenLabel'])) : ?>
		<div class="<?php echo $labelClasses; ?>">
			<?php echo $label; ?>
		</div>
	<?php endif; ?>
	<div class="<?php echo $inputClasses; ?>"><?php echo $input; ?></div>
</div>
