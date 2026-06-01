<?php
/**
 * @package     Multiagences
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

// No direct access
JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

$fieldsToShow = $this->params->get('agency_details_fields');

?>
<div class="">
<?php if ($this->item->id):?>
	<h2>
		<?php echo $this->escape($this->item->title); ?>
	</h2>
	<?php foreach($this->item->jcfields as $field) : ?>
		<?php if (in_array($field->id, $fieldsToShow)) { ?>
			<div class="mt-10 font-16">
				<?php echo FieldsHelper::render($field->context, 'field.render', array('field' => $field)); ?>
			</div>
		<?php } ?>
	<?php endforeach ?>
<?php endif; ?>
</div>
