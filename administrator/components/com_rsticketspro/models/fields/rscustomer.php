<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('user');

class JFormFieldRSCustomer extends JFormFieldUser
{
	public $type = 'RSCustomer';
	
	protected function getGroups()
	{
		return null;
	}

	protected function getInput()
	{
		$this->readonly = false;

		if (JFactory::getApplication()->isClient('site'))
		{
			$replacements = array(
				'"index.php?option=com_users' => '"' . JUri::root(true) . '/index.php?option=com_rsticketspro',
				'&quot;index.php?option=com_users' => '&quot;' . JUri::root(true) . '/index.php?option=com_rsticketspro',
			);
		}
		else
		{
			$replacements = array(
				'?option=com_users' => '?option=com_rsticketspro'
			);
		}

		return str_replace(array_keys($replacements), array_values($replacements), parent::getInput());
	}
}