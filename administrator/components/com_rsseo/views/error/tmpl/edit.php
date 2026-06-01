<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive'); ?>

<form action="<?php echo Route::_('index.php?option=com_rsseo&view=error&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" autocomplete="off" class="form-validate form-horizontal">
	
	<?php echo $this->form->renderFieldset('general'); ?>

	<?php echo HTMLHelper::_('form.token'); ?>
	<input type="hidden" name="task" value="" />
	<?php echo HTMLHelper::_('behavior.keepalive'); ?>
</form>