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

// Get create Agency list menu
$mainframe          = Factory::getApplication();
$menu               = $mainframe->getMenu();
$agencyListMenuItem = $menu->getItems('link', 'index.php?option=com_dpe&view=schools', true );
?>


<div class="dp-list-detail">
<?php if ($this->item->id):?>
	<h2>
		<?php echo $this->escape($this->item->title); ?>
	</h2>
	<?php foreach($this->item->jcfields as $field) : ?>
		<div class="mt-10 font-16">
			<?php echo FieldsHelper::render($field->context, 'field.render', array('field' => $field)); ?>
		</div>
	<?php endforeach ?>
	<?php else: ?>
		<h2>
			<?php echo Text::sprintf('COM_MULTIAGENCY_ADD_ITEM', Text::_('COM_MULTIAGENCY_ORGANISATION')); ?>
		</h2>
<?php endif; ?>
</div>

<a class="mt-20 btn btn-primary mobile-space" href="<?php echo Route::_('index.php?option=com_dpe&view=schools&filter[cluster_id]=all&Itemid=' . $agencyListMenuItem->id, false, 0); ?>"><i class="fa fa-arrow-left mr-10" aria-hidden="true"></i><?php echo Text::_('COM_DPE_BACK_BUTTON'); ?></a>

<?php
