<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

RSTicketsProHelper::tooltipLoad();
?>
<form action="index.php" method="post" name="adminForm">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<div id="dashboard-left">
			<?php echo $this->loadTemplate('buttons'); ?>
		</div>
		<div id="dashboard-right" class="hidden-phone hidden-tablet">
			<?php echo $this->loadTemplate('version'); ?>
			<p align="center"><a href="http://www.rsjoomla.com/joomla-components/joomla-security.html?utm_source=rsticketspro&amp;utm_medium=banner_approved&amp;utm_campaign=rsfirewall" target="_blank"><?php echo JHtml::image('com_rsticketspro/admin/rsfirewall-approved.png', 'RSFirewall! Approved', 'align="middle"', true);?></a></p>
		</div>
	</div>
	
	<input type="hidden" name="option" value="com_rsticketspro" />
	<input type="hidden" name="task" value="" />
</form>