<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * LOGman installer
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */

require_once __DIR__.'/helper.php';

class com_logmanInstallerScript extends JoomlatoolsInstallerHelper
{
    public function getRequiredDatabasePrivileges()
    {
        return array('ALTER');
    }

    public function getSystemErrors($type, $installer)
    {
        $errors = array();

        if ($type == 'update' && $this->old_version)
        {
            if($failed = $this->_dbUpdate($installer, array('from_ver' => $this->old_version)))
            {
                $errors[] = JText::_('DB schema update failed while processing queries:');
                foreach ($failed as $query) {
                    $errors[] = htmlspecialchars($query, ENT_QUOTES);
                }
            }
        }

        return $errors;
    }

    public function preflight($type, $installer)
    {
        $result = parent::preflight($type, $installer);

        if ($result && version_compare($this->old_version, '1.0.0RC4', '<'))
        {
            $extension_id = $this->getExtensionId(array(
                'type'    => 'plugin',
                'element' => 'logman',
                'folder'  => 'system',
            ));

            if ($extension_id) {
                $i = new JInstaller();
                if (method_exists($i, 'setDatabase')) {
                    $i->setDatabase(JFactory::getDbo());
                }
                $i->uninstall('plugin', $extension_id, 0);
            }
        }
    }

	public function afterInstall($type, $installer)
	{
        $db = JFactory::getDbo();
        $db->setQuery("SELECT id FROM #__modules WHERE module = 'mod_logman' AND published <> -2 AND position = '' AND client_id = 1");
        $id = $db->loadResult();
        if ($id)
        {
            $db->setQuery(sprintf("UPDATE `#__modules` SET position = 'cpanel', ordering = -1, published = 1, params = '{\"limit\":\"10\",\"direction\":\"desc\"}'
				    	WHERE id = %d LIMIT 1", $id));
            $db->execute();
            $db->setQuery("REPLACE INTO #__modules_menu VALUES ($id, 0)");
            $db->execute();
        }

        $products = array(
            'FILEman' => $this->_getComponentVersion('fileman'),
            'DOCman' => $this->_getComponentVersion('docman')
        );
        $incompatible = array();

        foreach ($products as $product => $version) {
            if ($version && version_compare($version, '3.0.0-rc.1', '<')) {
                $incompatible[] = $product.' '.$version;
            }
        }

        if (count($incompatible)) {
            $warning = 'This is important! You need to upgrade %s to 3.0 too or your site will break. Please go to <a target="_blank" href="https://joomlatools.com">https://joomlatools.com</a> and download the latest versions.';
            JFactory::getApplication()->enqueueMessage(sprintf($warning, implode(' and ', $incompatible)), 'warning');
        }
	}

    protected function _migrate()
    {
        parent::_migrate();

        if (version_compare($this->old_version, '2.0', '<')) {
            // Remove old plugin
            $query = "DELETE FROM #__extensions WHERE type='plugin' AND folder='logman' AND element='extension'";
            JFactory::getDbo()->setQuery($query)->execute();

            $this->_enableComponent();

            jimport('joomla.filesystem.folder');
            $path = JPATH_PLUGINS.'/logman/extension';
            if (JFolder::exists($path)) {
                JFolder::delete($path);
            }
        }

        // Check if the activities table got successfully renamed and disable LOGman if it didn't
        if (version_compare($this->old_version, '2.0.2', '<'))
        {
            $prefix = JFactory::getApplication()->getCfg('dbprefix');

            $query = sprintf("SHOW TABLES LIKE '%slogman_activities'", $prefix);

            if (!JFactory::getDbo()->setQuery($query)->loadResult()) {
                $this->_enableComponent(false);
                JLog::add('The installer failed to rename the activities database during the upgrade', JLog::WARNING, 'jerror');
            }
        }
    }

    protected function _enableComponent($state = true)
    {
        $db = JFactory::getDbo();

        $state = (int) $state;

        // Enable logging
        $query = "UPDATE #__extensions SET enabled = {$state} WHERE type='plugin' AND folder='koowa' AND element='logman'";
        $db->setQuery($query)->execute();

        // Enable control panel module
        $query = "UPDATE #__modules SET published = {$state} WHERE module='mod_logman'";
        $db->setQuery($query)->execute();
    }

    protected function _dbUpdate($installer, $config = array())
    {
        $installer_manifest = simplexml_load_file($installer->getParent()->getPath('manifest'));

        $failed      = array();
        $current_ver = (string) $installer_manifest->version;
        $queries     = array();

        $db  = JFactory::getDbo();
        $app = JFactory::getApplication();

        $adapter = KObjectManager::getInstance()->getObject('lib:database.adapter.mysqli');

        $dbname   = $app->get('db');
        $dbprefix = $app->get('dbprefix');

        $query = KObjectManager::getInstance()->getObject('lib:database.query.show')->show('tables')->from($dbname);

        $tables = $adapter->select($query, KDatabase::FETCH_FIELD_LIST);

        // Only update if a newer version is being installed.
        if (in_array($dbprefix . 'activities_activities', $tables) && version_compare($config['from_ver'], $current_ver, '<'))
        {
            // Check that schema isn't already up to date (downgrade and re-install).
            // TODO: Remove when downgrades get disallowed on installers.
            $schema = $adapter->getTableSchema('activities_activities');

            if (!isset($schema->columns['metadata']))
            {
                // Row can now contain non-integer values.
                $queries[] = 'ALTER TABLE `#__activities_activities` MODIFY `row`  VARCHAR(2048) NOT NULL DEFAULT \'\'';

                // Adding indexes.
                $queries[] = 'ALTER TABLE `#__activities_activities` ADD INDEX `package` (`package`)';
                $queries[] = 'ALTER TABLE `#__activities_activities` ADD INDEX `name` (`name`)';
                $queries[] = 'ALTER TABLE `#__activities_activities` ADD INDEX `row` (`row`)';

                // Added ip column.
                $queries[] = 'ALTER TABLE `#__activities_activities` ADD COLUMN `ip` varchar(45) NOT NULL DEFAULT \'\'';
                $queries[] = 'ALTER TABLE `#__activities_activities` ADD INDEX `ip` (`ip`)';

                // Add context row.
                $queries[] = 'ALTER TABLE `#__activities_activities` ADD COLUMN `metadata` text NOT NULL';
            }

            // Fix com_config activities.
            $queries[] = 'UPDATE `#__activities_activities` AS `activities` SET `name`=\'settings\' WHERE `package`=\'config\' AND `name` <> \'settings\'';
        }

        if (in_array($dbprefix . 'logman_activities', $tables) && version_compare($config['from_ver'], '3.0.2', '<'))
        {
            $schema = $adapter->getTableSchema('logman_activities');

            if (isset($schema->columns['row_uuid'])) {
                $queries[] = 'ALTER TABLE `#__logman_activities` DROP COLUMN `row_uuid`';
            }
        }

        if ($this->_tableExists('logman_activities'))
        {
            if ($adapter->getTableSchema('logman_activities')->columns['logman_activity_id']->type == 'int')
            {
                if ($this->_tableExists('logman_activities_impressions'))
                {
                    $queries[] = 'ALTER TABLE `#__logman_activities_impressions` DROP FOREIGN KEY logman_activities_impressions_ibfk_1';
                    $queries[] = 'ALTER TABLE `#__logman_activities_impressions` MODIFY `logman_activity_id` BIGINT(20) UNSIGNED NOT NULL';
                }

                $queries[] = 'ALTER TABLE `#__logman_activities` MODIFY `logman_activity_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT';

                if ($this->_tableExists('logman_activities_impressions')) {
                    $queries[] = 'ALTER TABLE `#__logman_activities_impressions` ADD CONSTRAINT `#__logman_activities_impressions_ibfk_1` FOREIGN KEY (`logman_activity_id`) REFERENCES `#__logman_activities` (`logman_activity_id`) ON DELETE CASCADE';
                }
            }
        }

        if ($this->_tableExists('logman_impressions'))
        {
            if (!$this->_columnExists('logman_impressions', 'metadata')) {
                $queries[] = 'ALTER TABLE `#__logman_impressions` ADD COLUMN `metadata` text NOT NULL';
            }

            if (!$this->_columnExists('logman_impressions', 'title')) {
                $queries[] = 'ALTER TABLE `#__logman_impressions` ADD COLUMN `title` VARCHAR(255) DEFAULT NULL AFTER `name`';
            }
        }

        if ($this->_tableExists('logman_synchronization'))
        {
            $queries[] = 'DELETE `sync` FROM `#__logman_synchronization` AS `sync` LEFT JOIN `#__logman_activities` AS `activities` ON `activities`.`uuid` = `sync`.`uuid` WHERE ISNULL(`activities`.`uuid`)';

			$result = (bool) $db->setQuery(sprintf("SELECT COUNT(*) FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_NAME = '%s' AND TABLE_NAME = '%s' AND CONSTRAINT_SCHEMA = '%s'", 'logman_synchronization_ibfk_1', $db->replacePrefix('#__logman_synchronization'), $app->get('db')))
								->loadResult();

			if ($result)
			{
				$queries[] = 'ALTER TABLE `#__logman_synchronization` DROP FOREIGN KEY logman_synchronization_ibfk_1';
				$queries[] = 'ALTER TABLE `#__logman_synchronization` ADD CONSTRAINT `#__logman_synchronization_ibfk_1` FOREIGN KEY (`uuid`) REFERENCES `#__logman_activities` (`uuid`) ON DELETE CASCADE';
			}
        }

        foreach ($queries as $query)
        {
            $db->setQuery($query);
            if ($db->execute() === false)
            {
                $failed[] = $query;
                // Do not continue with the upgrade. This should facilitate a manual intervention in case
                // anything goes wrong.
                break;
            }
        }

        return $failed;
    }
}
