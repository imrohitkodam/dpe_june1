<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

$user = Factory::getUser();
$canEdit = $user->authorise('core.edit', 'com_multiagency.' . $this->item->id);

if (!$canEdit && $user->authorise('core.edit.own', 'com_multiagency' . $this->item->id))
{
	$canEdit = $user->id == $this->item->created_by;
}
?>

<div class="item_fields">
	<table class="table">
		<tr>
			<th><?php echo Text::_('COM_MULTIAGENCY_FORM_LBL_MULTIAGENCY_TITLE'); ?></th>
			<td><?php echo $this->item->title; ?></td>
		</tr>
		<tr>
			<th><?php echo Text::_('COM_MULTIAGENCY_FORM_LBL_MULTIAGENCY_MANAGER_ID'); ?></th>
			<td><?php echo $this->item->manager_id_name; ?></td>
		</tr>
		<tr>
			<th><?php echo Text::_('COM_MULTIAGENCY_FORM_LBL_MULTIAGENCY_COUNTRY_ID'); ?></th>
			<td><?php echo $this->item->country_id; ?></td>
		</tr>
	</table>
</div>
<?php
if ($canEdit && $this->item->checked_out == 0)
{
	?>
	<a class="btn" href="<?php
	echo Route::_('index.php?option=com_multiagency&task=multiagency.edit&id=' . $this->item->id); ?>"><?php
	echo Text::_("COM_MULTIAGENCY_EDIT_ITEM"); ?></a>
	<?php
}

if (Factory::getUser()->authorise('core.delete', 'com_multiagency.multiagency.' . $this->item->id))
{
	?>
	<a class="btn btn-danger" href="#deleteModal" role="button" data-toggle="modal">
		<?php echo Text::_("COM_MULTIAGENCY_DELETE_ITEM"); ?>
	</a>
	<div id="deleteModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="deleteModal" aria-hidden="true">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3><?php echo Text::_('COM_MULTIAGENCY_DELETE_ITEM'); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php echo Text::sprintf('COM_MULTIAGENCY_DELETE_CONFIRM', $this->item->id); ?></p>
		</div>
		<div class="modal-footer">
			<button class="btn" data-dismiss="modal">Close</button>
			<a href="<?php echo Route::_('index.php?option=com_multiagency&task=multiagency.remove&id=' . $this->item->id, false, 2); ?>" class="btn btn-danger">
				<?php echo Text::_('COM_MULTIAGENCY_DELETE_ITEM'); ?>
			</a>
		</div>
	</div>
<?php
}
