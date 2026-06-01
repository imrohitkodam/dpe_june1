<?php
/**
* @version 2.0.0
* @package RSTickets! Pro 2.0.0
* @copyright (C) 2010 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * RSTickets! Pro Cron Email Parser Plugin
 */
class plgSystemRsticketsprocron extends CMSPlugin
{
	protected $autoloadLanguage = true;
	
	public function onAfterTicketsOverview($vars)
	{
		$vars['buttons'][] = array(
			'icon' => 'terminal',
			'link' => JRoute::_('index.php?option=com_rsticketspro&view=crons'),
			'text' => JText::_('RST_CRON_CONFIGURATION'),
			'access' => true
		);
		$vars['buttons'][] = array(
			'icon' => 'history',
			'link' => JRoute::_('index.php?option=com_rsticketspro&view=cronlog'),
			'text' => JText::_('RST_CRON_LOG'),
			'access' => true
		);
	}
	
	public function onAfterTicketsMenu()
	{
		require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/toolbar.php';
		
		$isDefault = JFactory::getApplication()->input->getCmd('view','') == 'crons';
		RSTicketsProToolbarHelper::addEntry('CRON_CONFIGURATION', 'index.php?option=com_rsticketspro&view=crons', $isDefault);
		
		$isDefault = JFactory::getApplication()->input->getCmd('view','') == 'cronlog';
		RSTicketsProToolbarHelper::addEntry('CRON_LOG', 'index.php?option=com_rsticketspro&view=cronlog', $isDefault);
	}
	
	protected function canRun()
	{
		if (file_exists(JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/cron.php'))
		{
			require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/cron.php';
			return true;
		}
		
		return false;
	}
	
	public function onCronTestConnection()
	{
		if (!$this->canRun())
		{
			return;
		}
		
		$id		= JFactory::getApplication()->input->getInt('id',0);
		$cron 	= new RSTicketsProCron();
		
		$cron->test($id);
	}
	
	public function onAfterDispatch()
	{
		if (!$this->canRun())
		{
			return;
		}
		
		$types 	= array(0,2);
		$cron 	= new RSTicketsProCron($types);
		
		$cron->parse();
	}

	public function onPreprocessMenuItems($context, &$items, $params = null, $enabled = true)
    {
        foreach ($items as $i => $item)
        {
            if ($item->element == 'com_rsticketspro' && $item->title == 'COM_RSTICKETSPRO_CONFIGURATION')
            {
	            if (version_compare(JVERSION, '4.0', '>='))
	            {
		            $newItem = new Joomla\CMS\Menu\AdministratorMenuItem();
		            $newItem->class = 'class:component';
		            $newItem->ajaxbadge = null;
		            $newItem->dashboard = null;
		            $newItem->title = 'RST_CRON_CONFIGURATION';
		            $newItem->alias = 'com-rsticketspro-cron-configuration';
		            $newItem->path = 'rsticketspro/com-rsticketspro-cron-configuration';
		            $newItem->link = 'index.php?option=com_rsticketspro&view=crons';

		            $newItem2 = new Joomla\CMS\Menu\AdministratorMenuItem();
		            $newItem2->class = 'class:component';
		            $newItem2->ajaxbadge = null;
		            $newItem2->dashboard = null;
		            $newItem2->title = 'RST_CRON_LOG';
		            $newItem2->alias = 'com-rsticketspro-cron-log';
		            $newItem2->path = 'rsticketspro/com-rsticketspro-cron-log';
		            $newItem2->link = 'index.php?option=com_rsticketspro&view=cronlog';

		            $parent = $item->getParent();
		            $parent->addChild($newItem);
		            $parent->addChild($newItem2);
	            }
	            else
	            {
		            $newItem = clone $item;
		            $newItem->title = 'RST_CRON_CONFIGURATION';
		            $newItem->alias = 'com-rsticketspro-cron-configuration';
		            $newItem->path = 'rsticketspro/com-rsticketspro-cron-configuration';
		            $newItem->link = 'index.php?option=com_rsticketspro&view=crons';

		            $newItem2 = clone $item;
		            $newItem2->title = 'RST_CRON_LOG';
		            $newItem2->alias = 'com-rsticketspro-cron-log';
		            $newItem2->path = 'rsticketspro/com-rsticketspro-cron-log';
		            $newItem2->link = 'index.php?option=com_rsticketspro&view=cronlog';

		            array_push($items, $newItem);
		            array_push($items, $newItem2);
	            }

				break;
            }
        }
    }
}