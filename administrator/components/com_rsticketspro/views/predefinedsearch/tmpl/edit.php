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
?>
<form action="<?php echo JRoute::_('index.php?option=com_rsticketspro&view=predefinedsearch&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal">
	<?php
	foreach ($this->form->getFieldsets() as $fieldset)
	{
		echo $this->form->renderFieldset($fieldset->name);
	}

	if (isset($this->item->params['search']))
	{
		$input = strlen($this->item->params['search']) ? $this->escape($this->item->params['search']) : '<em>' . JText::_('RST_NONE_SUPPLIED') . '</em>';
		$this->showField(JText::_('RST_SEARCH_TEXT'), $input);
	}
	
	if (isset($this->item->params['customer']))
	{
		$input = strlen($this->item->params['customer']) ? $this->escape($this->item->params['customer']) : '<em>' . JText::_('RST_NONE_SUPPLIED') . '</em>';
		$this->showField(JText::_('RST_SEARCH_CUSTOMER'), $input);
	}
	
	if (isset($this->item->params['staff']))
	{
		if (strlen($this->item->params['staff']))
		{
			if ((string) $this->item->params['staff'] === '0')
			{
				$input = '<em>' . JText::_('RST_UNASSIGNED') . '</em>';
			}
			else
			{
				$input = $this->escape($this->item->params['staff']);
			}
		}
		else
		{
			$input = '<em>' . JText::_('RST_NONE_SUPPLIED') . '</em>';
		}
		$this->showField(JText::_('RST_SEARCH_STAFF'), $input);
	}
	
	if (isset($this->item->params['department_id']))
	{
		$departments = $this->getDepartments($this->item->params['department_id']);
		$input = $departments ? $this->escape(implode(', ', $departments)) : '<em>' . JText::_('RST_NONE_SUPPLIED') . '</em>';
		$this->showField(JText::_('RST_SEARCH_DEPARTMENTS'), $input);
	}
	
	if (isset($this->item->params['priority_id']))
	{
		$priorities = $this->getPriorities($this->item->params['priority_id']);
		$input = $priorities ? $this->escape(implode(', ', $priorities)) : '<em>' . JText::_('RST_NONE_SUPPLIED') . '</em>';
		$this->showField(JText::_('RST_SEARCH_PRIORITIES'), $input);
	}
	
	if (isset($this->item->params['status_id']))
	{
		$statuses = $this->getStatuses($this->item->params['status_id']);
		$input = $statuses ? $this->escape(implode(', ', $statuses)) : '<em>' . JText::_('RST_NONE_SUPPLIED') . '</em>';
		$this->showField(JText::_('RST_SEARCH_STATUSES'), $input);
	}
	
	if (isset($this->item->params['flagged']))
	{
		$input = $this->item->params['flagged'] ? JText::_('JYES') : JText::_('JNO');
		$this->showField(JText::_('RST_SEARCH_FLAGGED'), $input);
	}

	if (!empty($this->item->params['from']))
	{
		$input = $this->escape($this->item->params['from']);
		$this->showField(JText::_('COM_RSTICKETSPRO_FROM_DATE'), $input);
	}

	if (!empty($this->item->params['to']))
	{
		$input = $this->escape($this->item->params['to']);
		$this->showField(JText::_('COM_RSTICKETSPRO_TO_DATE'), $input);
	}
	
	if (!empty($this->item->params['ordering']))
	{
		$input = JText::_('RST_TICKET_'.$this->item->params['ordering']);
		if (!empty($this->item->params['direction']))
		{
			$input .= ' ' . ($this->item->params['direction'] == 'asc' ? JText::_('JGLOBAL_ORDER_ASCENDING') : JText::_('JGLOBAL_ORDER_DESCENDING'));
		}
		$this->showField(JText::_('JFIELD_ORDERING_LABEL'), $input);
	}
	?>
	
	<div>
		<?php echo JHtml::_('form.token'); ?>
		<input type="hidden" name="task" value="" />
	</div>
</form>