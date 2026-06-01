<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access'); ?>
<div>
	<p><span title="<?php echo RSTicketsProHelper::tooltipText(JText::_('RST_TICKET_USER_AGENT')); ?>" class="<?php echo RSTicketsProHelper::tooltipClass();?>"><i class="icon-cogs icon-rscogs"></i> <?php echo $this->escape($this->ticket->agent); ?></span></p>
	<p><span title="<?php echo RSTicketsProHelper::tooltipText(JText::_('RST_TICKET_REFERER')); ?>" class="<?php echo RSTicketsProHelper::tooltipClass();?>"><i class="icon-refresh icon-rsrefresh"></i> <?php echo $this->escape($this->ticket->referer); ?></span></p>
	<p><span title="<?php echo RSTicketsProHelper::tooltipText(JText::_('RST_TICKET_IP')); ?>" class="<?php echo RSTicketsProHelper::tooltipClass();?>"><i class="icon-broadcast icon-rsbroadcast"></i> <?php echo $this->escape($this->ticket->ip); ?></span></p>
	<p><span title="<?php echo RSTicketsProHelper::tooltipText(JText::_('RST_TICKET_LOGGED')); ?>" class="<?php echo RSTicketsProHelper::tooltipClass();?>"><i class="icon-user icon-rsuser"></i> <?php echo $this->ticket->logged ? JText::_('JYES') : JText::_('JNO'); ?></span></p>
</div>