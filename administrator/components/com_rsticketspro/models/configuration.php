<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelConfiguration extends JModelAdmin
{
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.configuration', 'configuration', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	protected function loadFormData()
	{
		$data = (array) $this->getConfig()->getData();

		return $data;
	}

	public function save($data)
	{
		// get configuration
		$config = $this->getConfig();
		// get configuration keys
		$keys	= $config->getKeys();

		foreach ($keys as $key)
		{
			if (in_array($key, array('autoclose_cron_lastcheck', 'followup_cron_lastcheck', 'export_limit')))
			{
				continue;
			}

			if (isset($data[$key]))
			{
				$value = $data[$key];

				if ($key == 'captcha_characters' && $value < 3)
				{
					JFactory::getApplication()->enqueueMessage(JText::_('RST_CAPTCHA_CHARACTERS_ERROR'), 'warning');
					$value = 3;
				}
				elseif ($key == 'autoclose_cron_interval' && $value < 10)
				{
					JFactory::getApplication()->enqueueMessage(JText::_('RST_AUTOCLOSE_CHECK_ERROR'), 'warning');
					$value = 10;
				}
				elseif ($key == 'autoclose_email_interval' && $value < 1)
				{
					JFactory::getApplication()->enqueueMessage(JText::_('RST_AUTOCLOSE_DAYS_STATUS_ERROR'), 'warning');
					$value = 1;
				}
				elseif ($key == 'autoclose_interval' && $value < 1)
				{
					JFactory::getApplication()->enqueueMessage(JText::_('RST_AUTOCLOSE_DAYS_CLOSED_ERROR'), 'warning');
					$value = 1;
				}
				elseif ($key == 'followup_cron_interval' && $value < 10)
				{
					JFactory::getApplication()->enqueueMessage(JText::_('RST_FEEDBACK_FOLLOWUP_CHECK_ERROR'), 'warning');
					$value = 10;
				}
				elseif ($key == 'followup_enabled_time')
				{
					if ($data['enable_followup'] == 1 && $value == 0)
					{
						$value = JFactory::getDate()->toSql();
					}
					elseif ($data['enable_followup'] == 0 && $value != 0)
					{
						$value = 0;
					}
				}

				$config->set($key, $value);
			}
			else
			{
				$config->set($key, '');
			}
		}

		return true;
	}

	public function getConfig()
	{
		return RSTicketsProConfig::getInstance();
	}

	public function getRSTabs()
	{
		return new RsticketsproAdapterTabs('com-rsticketspro-configuration');
	}
}