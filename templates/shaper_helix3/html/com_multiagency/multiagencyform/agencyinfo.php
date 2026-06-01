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
use Joomla\CMS\HTML\HTMLHelper;

JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

$fieldsToShow = $this->params->get('agency_details_fields');

$doc = Factory::getDocument();
$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');

?>
<div class="container-fluid">
	<div class="multiagency-info">
		<?php if ($this->item->id):?>
			<h2 class="border-bottom p-2 mb-3 fs-22 fw-bold">
				<?php echo $this->escape($this->item->title); ?>
			</h2>
			<?php foreach($this->item->jcfields as $field) : ?>
				<?php if (in_array($field->id, $fieldsToShow)) { ?>
					<div class="px-2 font-16 mb-2">
						<?php echo FieldsHelper::render($field->context, 'field.render', array('field' => $field)); ?>
					</div>
				<?php } ?>
			<?php endforeach ?>
		<?php endif; ?>
	</div>
</div>
