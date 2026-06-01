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
JHtml::_('behavior.formvalidator');

// Load JavaScript message titles
JText::script('ERROR');
JText::script('WARNING');
JText::script('NOTICE');
JText::script('MESSAGE');
JText::script('COM_RSTICKETSPRO_TIME_BUTTON_CONFIRM_START');

if ($this->globalMessage && $this->globalMessagePosition === 'top')
{
	?>
	<div class="<?php echo RsticketsproAdapterGrid::row(); ?>" id="ticket-global-message">
		<?php echo $this->globalMessage; ?>
	</div>
	<?php
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=ticket'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data" autocomplete="off">
	<?php
	if ($this->ticketView == 'plain' || $this->isPrint)
	{
		?>
		<div class="<?php echo RsticketsproAdapterGrid::row(); ?>">
			<div class="<?php echo RsticketsproAdapterGrid::column(7); ?>" id="ticket-left-column">
				<?php
				$this->plain->addTitle(JText::_('RST_MESSAGES'), 'messages');
				$this->plain->addContent($this->loadTemplate('messages'));
				$this->plain->render();

				$this->plain->remove(0);
				?>
			</div>
			<div class="<?php echo RsticketsproAdapterGrid::column(5); ?>" id="ticket-right-column">
				<?php
				foreach ($this->ticketSections as $layout => $title)
				{
					if ($layout == 'messages' || ($layout == 'custom_fields' && empty($this->ticket->fields)))
					{
						continue;
					}

					// add the title
					$this->plain->addTitle($title, $layout);

					$content = $this->loadTemplate($layout);

					// add the content
					$this->plain->addContent($content);
				}

				// allow plugins to inject content here
				RSTicketsProHelper::trigger('onAfterTicketInformation', array($this->ticket, $this->plain));

				// render the plain view
				$this->plain->render();
				?>
			</div>
		</div>
		<?php
	}
	else
	{
		foreach ($this->ticketSections as $layout => $title)
		{
			if (empty($this->ticket->fields) && $layout == 'custom_fields')
			{
				continue;
			}

			$this->handler->addTitle($title, $layout);
			$this->handler->addContent($this->loadTemplate($layout));
		}

		RSTicketsProHelper::trigger('onAfterTicketInformation', array($this->ticket, $this->handler));

		$this->handler->render();
	}

	echo JHtml::_('form.token');
	?>
	<input type="hidden" name="id" value="<?php echo $this->ticket->id; ?>" />
	<input type="hidden" name="cid" value="<?php echo $this->ticket->id; ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="option" value="com_rsticketspro" />
</form>

<?php
if ($this->globalMessage && $this->globalMessagePosition === 'bottom')
{
	?>
	<div class="<?php echo RsticketsproAdapterGrid::row(); ?>" id="ticket-global-message">
		<?php echo $this->globalMessage; ?>
	</div>
	<?php
}
