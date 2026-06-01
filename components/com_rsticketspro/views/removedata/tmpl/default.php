<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.keepalive');
?>

<?php if ($this->params->get('show_page_heading', 1)) { ?>
	<h1 class="rst-heading"><?php echo $this->escape($this->params->get('page_heading', $this->params->get('page_title'))); ?></h1>
<?php }

if ($this->globalMessage && $this->globalMessagePosition === 'top')
{
	?>
	<div class="rst-form-section">
		<?php echo $this->globalMessage; ?>
	</div>
	<?php
}
?>

<form id="rsticketspro_removedata_form" action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=removedata'); ?>" method="post" name="removeDataForm">
	<div id="rsticketspro_remove_data_and_close_account">
		<div class="alert alert-warning">
			<?php if ($this->anonymise_joomla_data) { ?>
			<p><?php echo JText::_('COM_RSTICKETSPRO_REMOVE_DATA_AND_CLOSE_ACCOUNT_SURE_NO_LOGIN'); ?></p>
			<?php } else { ?>
			<p><?php echo JText::_('COM_RSTICKETSPRO_REMOVE_DATA_AND_CLOSE_ACCOUNT_SURE'); ?></p>
			<?php } ?>
			<p><strong><?php echo JText::_('COM_RSTICKETSPRO_REMOVE_DATA_AND_CLOSE_ACCOUNT_SURE_CONT'); ?></strong></p>
			<p><button type="button" onclick="RSTicketsPro.requestRemoveData(this);" class="btn btn-danger"><?php echo JText::sprintf('COM_RSTICKETSPRO_YES_SEND_ME_A_LINK', $this->email); ?></button></>
		</div>
	</div>
	<div class="text-center">
	<button type="button" class="btn btn-danger" onclick="RSTicketsPro.removeData(this);"><?php echo JText::_('COM_RSTICKETSPRO_REMOVE_DATA_AND_CLOSE_ACCOUNT'); ?></button>
	</div>

<?php
if ($this->globalMessage && $this->globalMessagePosition === 'bottom')
{
	?>
	<div class="rst-form-section">
		<?php echo $this->globalMessage; ?>
	</div>
	<?php
}

if ($this->show_footer)
{
	echo $this->footer;
}
?>
</form>