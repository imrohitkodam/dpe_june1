<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewPredefinedsearch extends JViewLegacy
{	
	public function display($tpl = null)
	{
		$this->canAccess();
		
		$this->params		= JFactory::getApplication()->getParams('com_rsticketspro');
		$this->form			= $this->get('Form');
		$this->item			= $this->get('Item');
		
		$this->prepareDocument();

		parent::display($tpl);
	}
	
	protected function prepareDocument()
	{
		// Description
		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		// Keywords
		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		// Robots
		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
	}
	
	protected function canAccess()
	{
		$app = JFactory::getApplication();
		
		if (JFactory::getUser()->get('guest'))
		{
			$link = base64_encode((string) JUri::getInstance());
			$app->redirect(RSTicketsProHelper::route('index.php?option=com_users&view=login&return='.$link, false));
		}
		
		if (!RSTicketsProHelper::isStaff())
		{
		    $app->enqueueMessage(JText::_('RST_CUSTOMER_CANNOT_VIEW_SEARCHES'), 'warning');
			$app->redirect(RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=tickets', false));
		}
	}

	protected function getDepartments($ids)
	{
		$db 	 = JFactory::getDbo();
		$results = array();

		if (!is_array($ids))
		{
			$ids = (array) $ids;
		}

		if (!$ids)
		{
			return $results;
		}

		$filtered_ids = array();
		foreach ($ids as $id)
		{
			$filtered_ids[] = $db->q($id);
		}

		// Load departments
		$query = $db->getQuery(true);
		$query->select($db->qn('name'))
			->from($db->qn('#__rsticketspro_departments'))
			->where($db->qn('id') . ' IN ('.implode(',', $filtered_ids).')');

		if ($results = $db->setQuery($query)->loadColumn())
		{
			foreach ($results as $k => $result)
			{
				$results[$k] = JText::_($result);
			}
		}

		return $results;
	}

	protected function getPriorities($ids)
	{
		$db 	 = JFactory::getDbo();
		$results = array();

		if (!is_array($ids))
		{
			$ids = (array) $ids;
		}

		if (!$ids)
		{
			return $results;
		}

		$filtered_ids = array();
		foreach ($ids as $id)
		{
			$filtered_ids[] = $db->q($id);
		}

		// Load priorities
		$query = $db->getQuery(true);
		$query->select($db->qn('name'))
			->from($db->qn('#__rsticketspro_priorities'))
			->where($db->qn('id') . ' IN ('.implode(',', $filtered_ids).')');

		if ($results = $db->setQuery($query)->loadColumn())
		{
			foreach ($results as $k => $result)
			{
				$results[$k] = JText::_($result);
			}
		}

		return $results;
	}

	protected function getStatuses($ids)
	{
		$db 	 = JFactory::getDbo();
		$results = array();

		if (!is_array($ids))
		{
			$ids = (array) $ids;
		}

		if (!$ids)
		{
			return $results;
		}

		$filtered_ids = array();
		foreach ($ids as $id)
		{
			$filtered_ids[] = $db->q($id);
		}

		// Load statuses
		$query = $db->getQuery(true);
		$query->select($db->qn('name'))
			->from($db->qn('#__rsticketspro_statuses'))
			->where($db->qn('id') . ' IN ('.implode(',', $filtered_ids).')');

		if ($results = $db->setQuery($query)->loadColumn())
		{
			foreach ($results as $k => $result)
			{
				$results[$k] = JText::_($result);
			}
		}

		return $results;
	}

	protected function showField($label, $desc)
	{
		echo '<p><strong>' . $label . '</strong><br>' . $desc . '</p>';
	}
}