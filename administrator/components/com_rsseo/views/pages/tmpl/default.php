<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;

Text::script('COM_RSSEO_PLEASE_SELECT_BATCH');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive'); ?>

<script>
    batchPages = () => {
        if (parseInt(document.querySelector('input[name="boxchecked"]').value) != 0) {
            Joomla.submitbutton('pages.batch');
        } else {
            alert(Joomla.JText._('COM_RSSEO_PLEASE_SELECT_BATCH'));
        }
    }
</script>

<form action="<?php echo Uri::getInstance(); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo RSSeoAdapterGrid::sidebar(); ?>
			
		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
			
		<?php echo $this->loadTemplate($this->simple ? 'simple' : 'standard'); ?>
	</div>
	
	<?php $footer = '<a href="javascript:void(0)" onclick="batchPages()" class="btn btn-primary">'.Text::_('COM_RSSEO_APPLY').'</a><a href="javascript:void(0)" data-bs-dismiss="modal" data-dismiss="modal" class="btn">'.Text::_('COM_RSSEO_GLOBAL_CLOSE').'</a>'; ?>
	<?php $selector = rsseoHelper::isJ4() ? 'batchpages' : 'modal-batchpages'; ?>
	<?php echo HTMLHelper::_('bootstrap.renderModal', $selector, array('title' => Text::_('COM_RSSEO_BATCH_OPTIONS'), 'footer' => $footer, 'bodyHeight' => 70), $this->loadTemplate('batch')); ?>

	<?php echo HTMLHelper::_( 'form.token' ); ?>
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="hash" value="<?php echo $this->escape($this->hash); ?>" />
</form>