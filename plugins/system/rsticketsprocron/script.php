<?php
/**
* @version 2.0.0
* @package RSTickets! Pro 2.0.0
* @copyright (C) 2010 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access');

class plgSystemRsticketsproCronInstallerScript
{
	public function preflight($type, $parent) {
		if ($type == 'uninstall') {
			return true;
		}

		try
        {
            $jversion = new JVersion();
            if (!$jversion->isCompatible('3.9.0'))
            {
                throw new Exception('Please upgrade to at least Joomla! 3.9.0 before continuing!');
            }

            if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php'))
            {
                throw new Exception('Please install the RSTickets!Pro component before continuing.');
            }

            require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/version.php';
            $version = new RSTicketsProVersion;
            if (version_compare((string) $version, '3.0.18', '<'))
            {
                throw new Exception('Please upgrade RSTickets! Pro to at least version 3.0.18 before continuing!');
            }
        }
        catch (Exception $e)
        {
            JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
		
		return true;
	}
	
	public function postflight($type, $parent) {
		$db = JFactory::getDbo();
		
		// #__rsticketspro_accounts updates
		$columns = $db->getTableColumns('#__rsticketspro_accounts');
		if (!isset($columns['security'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_accounts ADD `security` VARCHAR(255) NOT NULL AFTER `port`");
			$db->execute();
		}
		if (!isset($columns['type'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_accounts ADD `type` TINYINT(1) NOT NULL AFTER `password`");
			$db->execute();
		}
		if (isset($columns['offset'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_accounts DROP `offset`");
			$db->execute();
		}
		if (isset($columns['ssl']) && isset($columns['tls'])) {
			$db->setQuery('SELECT '.$db->qn('id').', '.$db->qn('ssl').', '.$db->qn('tls').' FROM '.$db->qn('#__rsticketspro_accounts').'');
			if ($accounts = $db->loadObjectList()) {
				foreach ($accounts as $account) {
					$security = '';
					if ($account->ssl)
						$security = 'ssl';
					elseif ($account->tls)
						$security = 'tls';
					
					$db->setQuery('UPDATE '.$db->qn('#__rsticketspro_accounts').' SET '.$db->qn('security').' =  '.$db->q($security).' WHERE '.$db->qn('id').' = '.$db->q($account->id).' ');
					$db->execute();
				}
				
				$db->setQuery("ALTER TABLE #__rsticketspro_accounts DROP `ssl`");
				$db->execute();
				$db->setQuery("ALTER TABLE #__rsticketspro_accounts DROP `tls`");
				$db->execute();
			}
		}
		
		if (!isset($columns['blacklist'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_accounts ADD `blacklist` TEXT NOT NULL AFTER `priority_id`");
			$db->execute();
		}

		if (!isset($columns['accept_all_replies']))
		{
			$db->setQuery("ALTER TABLE #__rsticketspro_accounts ADD `accept_all_replies` tinyint(1) NOT NULL DEFAULT '0' AFTER `blacklist`");
			$db->execute();
		}
		
		// #__rsticketspro_accounts_log updates
		$columns = $db->getTableColumns('#__rsticketspro_accounts_log');
		if ($columns['date'] == 'int') {
			$db->setQuery("ALTER TABLE #__rsticketspro_accounts_log CHANGE `date` `date` VARCHAR(255) NOT NULL");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_accounts_log SET `date` = FROM_UNIXTIME(`date`)");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_accounts_log SET `date` = '0000-00-00 00:00:00' WHERE `date` LIKE '1970-01-01%'");
			$db->execute();					
			$db->setQuery("ALTER TABLE #__rsticketspro_accounts_log CHANGE `date` ".$db->qn('date')." DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
			$db->execute();
		}
		
	}
	
	public function update($parent) {
		$this->copyFiles($parent);
	}
	
	public function install($parent) {
		$this->copyFiles($parent);
	}
	
	protected function copyFiles($parent) {
		$app = JFactory::getApplication();
		$installer = $parent->getParent();
		$src = $installer->getPath('source').'/admin';
		$dest = JPATH_ADMINISTRATOR.'/components/com_rsticketspro';
		
		if (!JFolder::copy($src, $dest, '', true)) {
			$app->enqueueMessage('Could not copy to '.str_replace(JPATH_SITE, '', $dest).', please make sure destination is writable!', 'error');
		}
	}
}