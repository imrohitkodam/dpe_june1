<?php
/**
* @package RSform!Pro
* @copyright (C) www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class plgSystemRsfprsticketsproInstallerScript
{
	protected static $minJoomla = '3.7.0';
	protected static $minComponent = '3.0.0';

	public function preflight($type, $parent)
	{
		if ($type == 'uninstall')
		{
			return true;
		}

		try
		{
			$jversion = new JVersion();

			if (!$jversion->isCompatible(static::$minJoomla))
			{
				throw new Exception('Please upgrade to at least Joomla! ' . static::$minJoomla . ' before continuing!');
			}

			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/rsform.php'))
			{
				throw new Exception('Please install the RSForm! Pro component before continuing.');
			}

			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php'))
			{
				throw new Exception('Please install the RSTickets! Pro component before continuing.');
			}

			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/assets.php') || !file_exists(JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/version.php'))
			{
				throw new Exception('Please upgrade RSForm! Pro to at least version ' . static::$minComponent . ' before continuing!');
			}

			require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/version.php';

			if (!class_exists('RSFormProVersion') || version_compare((string) new RSFormProVersion, static::$minComponent, '<'))
			{
				throw new Exception('Please upgrade RSForm! Pro to at least version ' . static::$minComponent . ' before continuing!');
			}

			$this->copyFiles($parent);

			// Update? Run our SQL file
			if ($type == 'update')
			{
				$this->runSQL($parent->getParent()->getPath('source'), 'install');
			}
		}
		catch (Exception $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return false;
		}

		return true;
	}
	
	protected function runSQL($source, $file)
	{
		$db = JFactory::getDbo();
		$sqlfile = $source . '/sql/mysql/' . $file . '.sql';
		
		if (file_exists($sqlfile))
		{
			$buffer = file_get_contents($sqlfile);
			if ($buffer !== false)
			{
				$queries = $db->splitSql($buffer);
				foreach ($queries as $query)
				{
					$query = trim($query);
					if ($query != '')
					{
						$db->setQuery($query)->execute();
					}
				}
			}
		}
	}

	public function update($parent)
	{
		$db 		= JFactory::getDbo();
		$columns 	= $db->getTableColumns('#__rsform_rsticketspro');

		if (!isset($columns['rstp_trigger_on_payment']))
		{
			$db->setQuery("ALTER TABLE `#__rsform_rsticketspro` ADD `rstp_trigger_on_payment` tinyint(1) NOT NULL DEFAULT '0' AFTER `rstp_mappings`");
			$db->execute();
		}
	}
	
	protected function copyFiles($parent)
	{
		$app 		= JFactory::getApplication();
		$src 		= $parent->getParent()->getPath('source').'/admin';
		$dest 		= JPATH_ADMINISTRATOR . '/components/com_rsform';
		
		if (!JFolder::copy($src, $dest, '', true))
		{
			$app->enqueueMessage('Could not copy to '.str_replace(JPATH_SITE, '', $dest).', please make sure destination is writable!', 'error');
		}
	}
}