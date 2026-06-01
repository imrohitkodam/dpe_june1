<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelRsticketspro extends JModelLegacy
{
	public function getCode()
	{
		return RSTicketsProConfig::getInstance()->get('global_register_code');
	}

	public function getKbbuttons()
	{
		JFactory::getLanguage()->load('com_rsticketspro.sys', JPATH_ADMINISTRATOR);

		$buttons = array(
			array(
				'link' => JRoute::_('index.php?option=com_rsticketspro&view=kbcategories'),
				'icon' => 'briefcase',
				'text' => JText::_('COM_RSTICKETSPRO_KB_CATEGORIES'),
				'access' => true,
				'target' => ''
			),
			array(
				'link' => JRoute::_('index.php?option=com_rsticketspro&view=kbarticles'),
				'icon' => 'doc-text',
				'text' => JText::_('COM_RSTICKETSPRO_KB_ARTICLES'),
				'access' => true,
				'target' => ''
			),
			array(
				'link' => JRoute::_('index.php?option=com_rsticketspro&view=kbrules'),
				'icon' => 'magic',
				'text' => JText::_('COM_RSTICKETSPRO_KB_CONVERSION_RULES'),
				'access' => true,
				'target' => ''
			)
		);

		return $buttons;
	}
	
	public function getButtons()
	{
		JFactory::getLanguage()->load('com_rsticketspro.sys', JPATH_ADMINISTRATOR);
		
		$buttons = array(
			array(
				'link' => JRoute::_('index.php?option=com_rsticketspro&view=tickets'),
				'icon' => 'clipboard',
				'text' => JText::_('COM_RSTICKETSPRO_MANAGE_TICKETS'),
				'access' => true,
				'target' => ''
			),
			array(
				'link' => JRoute::_('index.php?option=com_rsticketspro&view=departments'),
				'icon' => 'folder',
				'text' => JText::_('COM_RSTICKETSPRO_DEPARTMENTS'),
				'access' => true,
				'target' => ''
			),
			array(
				'link' => JRoute::_('index.php?option=com_rsticketspro&view=groups'),
				'icon' => 'users',
				'text' => JText::_('COM_RSTICKETSPRO_GROUPS'),
				'access' => true,
				'target' => ''
			),
			array(
				'link' => JRoute::_('index.php?option=com_rsticketspro&view=staffs'),
				'icon' => 'user',
				'text' => JText::_('COM_RSTICKETSPRO_STAFF_MEMBERS'),
				'access' => true,
				'target' => ''
			),
			array(
				'link' => JRoute::_('index.php?option=com_rsticketspro&view=priorities'),
				'icon' => 'chart-bar',
				'text' => JText::_('COM_RSTICKETSPRO_PRIORITIES'),
				'access' => true,
				'target' => ''
			),
			array(
				'link' => JRoute::_('index.php?option=com_rsticketspro&view=statuses'),
				'icon' => 'arrows-cw',
				'text' => JText::_('COM_RSTICKETSPRO_STATUSES'),
				'access' => true,
				'target' => ''
			),
			array(
				'link' => JRoute::_('index.php?option=com_rsticketspro&view=emails'),
				'icon' => 'mail',
				'text' => JText::_('COM_RSTICKETSPRO_EMAIL_MESSAGES'),
				'access' => true,
				'target' => ''
			),
			array(
				'link' => JRoute::_('index.php?option=com_rsticketspro&view=configuration'),
				'icon' => 'cogs',
				'text' => JText::_('COM_RSTICKETSPRO_CONFIGURATION'),
				'access' => JFactory::getUser()->authorise('core.admin', 'com_rsticketspro'),
				'target' => ''
			),
			array(
				'link' => JRoute::_('https://www.rsjoomla.com/support.html'),
				'icon' => 'lifebuoy',
				'text' => JText::_('RST_GET_SUPPORT'),
				'access' => true,
				'target' => '_blank'
			)
		);
		
		JFactory::getApplication()->triggerEvent('onAfterTicketsOverview', array(array('buttons' => &$buttons)));
		
		return $buttons;
	}
}