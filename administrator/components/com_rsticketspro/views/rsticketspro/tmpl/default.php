<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

JHtml::_('stylesheet', 'com_rsticketspro/admin/dashboard.css', array('relative' => true, 'version' => 'auto'));
?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
	<?php echo RsticketsproAdapterGrid::sidebar(); ?>
		<div id="dashboard-left">
			<?php
			$rows = array_chunk($this->buttons, 4);
			foreach ($rows as $buttons)
			{
				?>
				<div class="dashboard-container">
					<?php
					foreach ($buttons as $button)
					{
						if ($button['access'])
						{
							?>
							<div class="dashboard-info dashboard-button">
								<a <?php if (!empty($button['target'])) { ?> target="<?php echo $this->escape($button['target']); ?>"<?php } ?> href="<?php echo $button['link']; ?>"><i class="dashboard-icon rsticketsproicon-<?php echo $button['icon']; ?>"></i><span class="dashboard-title"><?php echo $button['text']; ?></span>
								</a>
							</div>
							<?php
						}
					}
					?>
				</div>
				<?php
			}
			?>
			<h3><?php echo JText::_('RST_KNOWLEDGEBASE'); ?></h3>
			<?php
			$rows = array_chunk($this->kbbuttons, 4);
			foreach ($rows as $buttons)
			{
				?>
				<div class="dashboard-container">
					<?php
					foreach ($buttons as $button)
					{
						if ($button['access'])
						{
							?>
							<div class="dashboard-info dashboard-button">
								<a <?php if (!empty($button['target'])) { ?> target="<?php echo $this->escape($button['target']); ?>"<?php } ?> href="<?php echo $button['link']; ?>"><i class="dashboard-icon rsticketsproicon-<?php echo $button['icon']; ?>"></i><span class="dashboard-title"><?php echo $button['text']; ?></span>
								</a>
							</div>
							<?php
						}
					}
					?>
				</div>
				<?php
			}
			?>
		</div>
		<div id="dashboard-right" class="<?php echo RsticketsproAdapterGrid::show('desktop'); ?>">
			<div class="dashboard-container">
				<div class="dashboard-info">
					<?php echo JHtml::_('image', 'com_rsticketspro/admin/rsticketspro.png', 'RSTickets! Pro', array(), true); ?>
					<table class="dashboard-table">
						<tr>
							<td nowrap="nowrap"><strong><?php echo JText::_('COM_RSTICKETSPRO_PRODUCT_VERSION') ?>: </strong></td>
							<td nowrap="nowrap">RSTickets! Pro <?php echo $this->version; ?></td>
						</tr>
						<tr>
							<td nowrap="nowrap"><strong><?php echo JText::_('COM_RSTICKETSPRO_COPYRIGHT_NAME') ?>: </strong></td>
							<td nowrap="nowrap">&copy; 2010 - <?php echo gmdate('Y'); ?> <a href="https://www.rsjoomla.com" target="_blank">RSJoomla!</a></td>
						</tr>
						<tr>
							<td nowrap="nowrap"><strong><?php echo JText::_('COM_RSTICKETSPRO_LICENSE_NAME') ?>: </strong></td>
							<td nowrap="nowrap"><a href="https://www.gnu.org/licenses/gpl.html" target="_blank">GNU/GPL</a> Commercial</a></td>
						</tr>
						<tr>
							<td nowrap="nowrap"><strong><?php echo JText::_('COM_RSTICKETSPRO_UPDATE_CODE') ?>: </strong></td>
							<?php if (strlen($this->code) == 20) { ?>
								<td nowrap="nowrap" class="correct-code"><?php echo $this->escape($this->code); ?></td>
							<?php } elseif ($this->code) { ?>
								<td nowrap="nowrap" class="incorrect-code"><?php echo $this->escape($this->code); ?>
									<br />
									<strong><a href="https://www.rsjoomla.com/support/documentation/general-faq/where-do-i-find-my-license-code-.html" target="_blank"><?php echo JText::_('COM_RSTICKETSPRO_WHERE_DO_I_FIND_THIS'); ?></a></strong>
								</td>
							<?php } else { ?>
								<td nowrap="nowrap" class="missing-code"><a href="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=configuration'); ?>"><?php echo JText::_('COM_RSTICKETSPRO_PLEASE_ENTER_YOUR_CODE_IN_THE_CONFIGURATION'); ?></a>
									<br />
									<strong><a href="https://www.rsjoomla.com/support/documentation/general-faq/where-do-i-find-my-license-code-.html" target="_blank"><?php echo JText::_('COM_RSTICKETSPRO_WHERE_DO_I_FIND_THIS'); ?></a></strong>
								</td>
							<?php } ?>
						</tr>
					</table>
				</div>
			</div>
			<p class="text-center"><a href="https://www.rsjoomla.com/joomla-components/joomla-security.html?utm_source=rsticketspro&amp;utm_medium=banner_approved&amp;utm_campaign=rsfirewall" target="_blank"><?php echo JHtml::_('image', 'com_rsticketspro/admin/rsfirewall-approved.png', 'RSFirewall! Approved', array(), true); ?></a></p>
		</div>
	</div>
	
	<input type="hidden" name="option" value="com_rsticketspro" />
	<input type="hidden" name="task" value="" />
</form>