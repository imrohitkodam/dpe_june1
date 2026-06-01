<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class com_rsticketsproInstallerScript
{
	protected $source;

	protected $plugins = array(
		array('element' => 'rsticketspro', 'type' => 'plugin', 'folder' => 'system', 'source' => 'plg_system', 'name' => 'System - RSTickets! Pro Plugin'),
		array('element' => 'rsticketspro', 'type' => 'plugin', 'folder' => 'user', 'source' => 'plg_user', 'name' => 'User - RSTickets! Pro Staff'),
		array('element' => 'rsticketspro', 'type' => 'plugin', 'folder' => 'privacy', 'source' => 'plg_rsticketsproprivacy', 'name' => 'Privacy - RSTickets! Pro'),
		array('element' => 'rsticketspro', 'type' => 'plugin', 'folder' => 'installer', 'source' => 'plg_installer', 'name' => 'Installer - RSTickets! Pro'),
		array('element' => 'rsticketsprocontent', 'type' => 'plugin', 'folder' => 'search', 'source' => 'plg_search', 'name' => 'Search - RSTickets! Pro Knowledgebase'),
	);

	public function uninstall($parent)
	{
		// Get Dbo
		$db = JFactory::getDbo();
		
		// Get a new installer
		foreach ($this->plugins as $plugin)
		{
			$query = $db->getQuery(true)
				->select($db->qn('extension_id'))
				->from($db->qn('#__extensions'))
				->where($db->qn('element') . ' = ' . $db->q($plugin['element']))
				->where($db->qn('type') . ' = ' . $db->q($plugin['type']))
				->where($db->qn('folder') . ' = ' . $db->q($plugin['folder']));
			if ($extension_id = $db->setQuery($query)->loadResult())
			{
				$installer = new JInstaller();

				$installer->uninstall('plugin', $extension_id);
			}
		}
	}
	
	
	public function preflight($type, $parent) {		
		$jversion = new JVersion();
		
		if (!$jversion->isCompatible('3.9.0')) {
			JFactory::getApplication()->enqueueMessage('Please upgrade to at least Joomla! 3.9.0 before continuing!', 'error');
			return false;
		}
		
		return true;
	}
	
	public function postflight($type, $parent)
	{
		if ($type == 'uninstall')
		{
			return true;
		}
		
		$db 			= JFactory::getDbo();
		$this->source 	= $parent->getParent()->getPath('source');

		$messages = array(
			'plugins' => array()
		);

		foreach ($this->plugins as $plugin)
		{
			$tmp = (object) array(
				'name' 		=> $plugin['name'],
				'status' 	=> 'not-ok',
				'text' 		=> 'Not installed'
			);

			$installer = new JInstaller();

			if ($installer->install($this->source . '/other/' . $plugin['source']))
			{
				$query = $db->getQuery(true)
					->update('#__extensions')
					->set($db->qn('enabled').'='.$db->q(1))
					->where($db->qn('element') . ' = ' . $db->q($plugin['element']))
					->where($db->qn('type') . ' = ' . $db->q($plugin['type']))
					->where($db->qn('folder') . ' = ' . $db->q($plugin['folder']));

				$db->setQuery($query)->execute();

				$tmp->status = 'ok';
				$tmp->text = 'Installed';
			}

			$messages['plugins'][] = $tmp;
		}

		if (file_exists(JPATH_SITE.'/plugins/user/rsticketspro/rsticketspro.php'))
		{
			require_once JPATH_SITE . '/plugins/user/rsticketspro/rsticketspro.php';

			if (class_exists('plgUserRsticketspro'))
			{
				plgUserRsticketspro::onUserLogin($user=array(), $options=array());
			}
		}
		
		if ($type == 'update')
		{
			$this->updateProcess();
			
			$sqlfile = JPATH_ADMINISTRATOR . '/components/com_rsticketspro/sql/install.sql';
			$buffer = file_get_contents($sqlfile);
			if ($buffer === false)
			{
                JFactory::getApplication()->enqueueMessage(JText::_('JLIB_INSTALLER_ERROR_SQL_READBUFFER'), 'warning');
			}
			else
			{
				// Process each query in the $queries array (split out of sql file).
				if ($queries = $db->splitSql($buffer))
				{
					foreach ($queries as $query)
					{
						$db->setQuery($query);
						try
						{
							$db->execute();
						}
						catch (Exception $e)
						{
							JFactory::getApplication()->enqueueMessage(JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $e->getMessage()), 'warning');
						}
					}
				}
			}
		}

		$this->showInstallMessage($messages);

		return true;
	}
	
	protected function escape($string) {
		return htmlentities($string, ENT_COMPAT, 'utf-8');
	}
	
	protected function isColumnInt($column) {
		return substr(strtolower($column), 0, 3) == 'int';
	}
	
	protected function updateProcess() {
		$db = JFactory::getDbo();
		
		// #__rsticketspro_kb_content updates
		$columns = $db->getTableColumns('#__rsticketspro_kb_content');
		if ($this->isColumnInt($columns['created'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_content CHANGE `created` `created` VARCHAR(255) NOT NULL");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_kb_content SET `created` = IFNULL(CONVERT_TZ(FROM_UNIXTIME(".$db->qn('created')."), @@session.time_zone, 'UTC'), FROM_UNIXTIME(".$db->qn('created')."))");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_kb_content SET `created` = '0000-00-00 00:00:00' WHERE `created` LIKE '1970-01-01%'");
			$db->execute();					
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_content CHANGE `created` ".$db->qn('created')." DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
			$db->execute();
		}
		if ($this->isColumnInt($columns['modified'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_content CHANGE `modified` `modified` VARCHAR(255) NOT NULL");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_kb_content SET `modified` = IFNULL(CONVERT_TZ(FROM_UNIXTIME(".$db->qn('modified')."), @@session.time_zone, 'UTC'), FROM_UNIXTIME(".$db->qn('modified')."))");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_kb_content SET `modified` = '0000-00-00 00:00:00' WHERE `modified` LIKE '1970-01-01%'");
			$db->execute();					
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_content CHANGE `modified` ".$db->qn('modified')." DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
			$db->execute();
		}
		if (!isset($columns['alias'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_content ADD `alias` VARCHAR( 255 ) NOT NULL DEFAULT ''");
			$db->execute();
		}
		if (!isset($columns['draft'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_content ADD `draft` TEXT NOT NULL");
			$db->execute();
		}
		if (!isset($columns['thumb'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_content ADD `thumb` VARCHAR(64) NOT NULL");
			$db->execute();
		}
		if (!isset($columns['positive_feedback'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_content ADD positive_feedback INT(10) unsigned NOT NULL DEFAULT '0'");
			$db->execute();
		}
		
		// #__rsticketspro_ticket_notes updates
		$columns = $db->getTableColumns('#__rsticketspro_ticket_notes');
		if ($this->isColumnInt($columns['date'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_ticket_notes CHANGE `date` `date` VARCHAR(255) NOT NULL");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_ticket_notes SET `date` = IFNULL(CONVERT_TZ(FROM_UNIXTIME(".$db->qn('date')."), @@session.time_zone, 'UTC'), FROM_UNIXTIME(".$db->qn('date')."))");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_ticket_notes SET `date` = '0000-00-00 00:00:00' WHERE `date` LIKE '1970-01-01%'");
			$db->execute();					
			$db->setQuery("ALTER TABLE #__rsticketspro_ticket_notes CHANGE `date` ".$db->qn('date')." DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
			$db->execute();
		}
		
		// #__rsticketspro_ticket_history updates
		$columns = $db->getTableColumns('#__rsticketspro_ticket_history');
		if ($this->isColumnInt($columns['date'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_ticket_history CHANGE `date` `date` VARCHAR(255) NOT NULL");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_ticket_history SET `date` = IFNULL(CONVERT_TZ(FROM_UNIXTIME(".$db->qn('date')."), @@session.time_zone, 'UTC'), FROM_UNIXTIME(".$db->qn('date')."))");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_ticket_history SET `date` = '0000-00-00 00:00:00' WHERE `date` LIKE '1970-01-01%'");
			$db->execute();					
			$db->setQuery("ALTER TABLE #__rsticketspro_ticket_history CHANGE `date` ".$db->qn('date')." DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
			$db->execute();
		}
		if (!isset($columns['type'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_ticket_history ADD `type` VARCHAR(64) NOT NULL AFTER `date`");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_ticket_history SET `type` = 'view' WHERE `type`= ''");
			$db->execute();
		}
		
		// #__rsticketspro_tickets updates
		$columns = $db->getTableColumns('#__rsticketspro_tickets');
		if ($this->isColumnInt($columns['date'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_tickets CHANGE `date` `date` VARCHAR(255) NOT NULL");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_tickets SET `date` = IFNULL(CONVERT_TZ(FROM_UNIXTIME(".$db->qn('date')."), @@session.time_zone, 'UTC'), FROM_UNIXTIME(".$db->qn('date')."))");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_tickets SET `date` = '0000-00-00 00:00:00' WHERE `date` LIKE '1970-01-01%'");
			$db->execute();					
			$db->setQuery("ALTER TABLE #__rsticketspro_tickets CHANGE `date` ".$db->qn('date')." DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
			$db->execute();
		}
		if (!isset($columns['closed'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_tickets ADD `closed` DATETIME NOT NULL AFTER `autoclose_sent`");
			$db->execute();
		}

		if (!isset($columns['followup_sent'])){
			$db->setQuery("ALTER TABLE #__rsticketspro_tickets ADD `followup_sent` TINYINT NOT NULL DEFAULT '0' AFTER `feedback`");
			$db->execute();
		}
		
		if (!isset($columns['alternative_email'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_tickets ADD `alternative_email` VARCHAR(255) NOT NULL AFTER `date`");
			$db->execute();
		}

		if ($this->isColumnInt($columns['last_reply'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_tickets CHANGE `last_reply` `last_reply` VARCHAR(255) NOT NULL");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_tickets SET `last_reply` = IFNULL(CONVERT_TZ(FROM_UNIXTIME(".$db->qn('last_reply')."), @@session.time_zone, 'UTC'), FROM_UNIXTIME(".$db->qn('last_reply')."))");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_tickets SET `last_reply` = '0000-00-00 00:00:00' WHERE `last_reply` LIKE '1970-01-01%'");
			$db->execute();					
			$db->setQuery("ALTER TABLE #__rsticketspro_tickets CHANGE `last_reply` ".$db->qn('last_reply')." DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
			$db->execute();
		}

		// #__rsticketspro_ticket_messages updates
		$columns = $db->getTableColumns('#__rsticketspro_ticket_messages');
		if ($this->isColumnInt($columns['date'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_ticket_messages CHANGE `date` `date` VARCHAR(255) NOT NULL");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_ticket_messages SET `date` = IFNULL(CONVERT_TZ(FROM_UNIXTIME(".$db->qn('date')."), @@session.time_zone, 'UTC'), FROM_UNIXTIME(".$db->qn('date')."))");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_ticket_messages SET `date` = '0000-00-00 00:00:00' WHERE `date` LIKE '1970-01-01%'");
			$db->execute();					
			$db->setQuery("ALTER TABLE #__rsticketspro_ticket_messages CHANGE `date` ".$db->qn('date')." DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
			$db->execute();
		}
		if (!isset($columns['html'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_ticket_messages ADD `html` TINYINT( 1 ) NOT NULL ");
			$db->execute();
			$db->setQuery("SELECT `value` FROM #__rsticketspro_configuration WHERE `name` = 'allow_rich_editor'");
			$allow_rich_editor = $db->loadResult();
			$db->setQuery("UPDATE #__rsticketspro_ticket_messages SET `html` = '".($allow_rich_editor ? 1 : 2)."'");
			$db->execute();
		}
		if (!isset($columns['submitted_by_staff'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_ticket_messages ADD `submitted_by_staff` INT(11) NOT NULL ");
			$db->execute();
		}
		
		// #__rsticketspro_emails updates
		$columns = $db->getTableColumns('#__rsticketspro_emails');
		if (!isset($columns['id'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_emails DROP PRIMARY KEY");
			$db->execute();
			$db->setQuery("ALTER TABLE #__rsticketspro_emails ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
			$db->execute();
			$db->setQuery("ALTER TABLE `#__rsticketspro_emails` ADD UNIQUE (`lang`,`type`)");
			$db->execute();
		}
		if (!isset($columns['published'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_emails ADD `published` tinyint(1) NOT NULL DEFAULT '1' AFTER `message`");
			$db->execute();
		}

		$columns = $db->getTableColumns('#__rsticketspro_departments');
		if (!isset($columns['download_type'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_departments ADD `download_type` VARCHAR( 255 ) NOT NULL DEFAULT 'attachment' AFTER `upload_files`");
			$db->execute();
		}
        if (!isset($columns['upload_ticket_required'])) {
            $db->setQuery("ALTER TABLE #__rsticketspro_departments ADD `upload_ticket_required` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `upload`");
            $db->execute();
        }
        if (!isset($columns['jgroups']))
		{
			$db->setQuery("ALTER TABLE #__rsticketspro_departments ADD `jgroups` MEDIUMTEXT NOT NULL AFTER `predefined_subjects`");
			$db->execute();
		}
        if (!isset($columns['email_address_reply_to']))
        {
	        $db->setQuery("ALTER TABLE #__rsticketspro_departments ADD `email_address_reply_to` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER `email_address_fullname`");
	        $db->execute();
        }
		if (!isset($columns['email_address_reply_to_name']))
		{
			$db->setQuery("ALTER TABLE #__rsticketspro_departments ADD `email_address_reply_to_name` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER `email_address_reply_to`");
			$db->execute();
		}

		// #__rsticketspro_searches updates$download_type
		$columns = $db->getTableColumns('#__rsticketspro_searches');
		if (!isset($columns['published'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_searches ADD `published` TINYINT( 1 ) NOT NULL AFTER `default`");
			$db->execute();
			$db->setQuery("UPDATE #__rsticketspro_searches SET `published` = 1");
			$db->execute();
		}
		
		// #__rsticketspro_kb_categories updates
		$columns = $db->getTableColumns('#__rsticketspro_kb_categories', false);
		if ($columns['thumb']->Type == 'varchar(16)') {
			$db->setQuery("ALTER TABLE `#__rsticketspro_kb_categories` CHANGE `thumb` `thumb` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
			$db->execute();
		}
		if (!isset($columns['show_name'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_categories ADD `show_name` TINYINT( 1 ) NOT NULL DEFAULT '1'");
			$db->execute();
		}
		if (!isset($columns['alias'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_categories ADD `alias` VARCHAR( 255 ) NOT NULL DEFAULT ''");
			$db->execute();
		}
		if (!isset($columns['layout'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_categories ADD `layout` VARCHAR(64) NOT NULL DEFAULT ''");
			$db->execute();
		}
		if (!isset($columns['columns'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_categories ADD `columns` TINYINT(1) NOT NULL DEFAULT '3'");
			$db->execute();
		}
		if (!isset($columns['tag_sorting'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_kb_categories ADD `tag_sorting` TINYINT(1) NOT NULL DEFAULT '1'");
			$db->execute();
		}
		
		// #__rsticketspro_groups updates
		$columns = $db->getTableColumns('#__rsticketspro_groups');
		if (!isset($columns['export_tickets'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_groups ADD `export_tickets` TINYINT( 1 ) NOT NULL");
			$db->execute();
		}
		
		// #__rsticketspro_staff updates
		$columns = $db->getTableColumns('#__rsticketspro_staff');
		if (!isset($columns['exclude_auto_assign'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_staff ADD `exclude_auto_assign` TINYINT( 1 ) NOT NULL DEFAULT '0'");
			$db->execute();
		}
		if (!isset($columns['can_delete_time_history'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_staff ADD `can_delete_time_history` TINYINT( 1 ) NOT NULL DEFAULT '0'");
			$db->execute();
		}
		if (!isset($columns['can_delete_own_time_history'])) {
			$db->setQuery("ALTER TABLE #__rsticketspro_staff ADD `can_delete_own_time_history` TINYINT( 1 ) NOT NULL DEFAULT '0'");
			$db->execute();
		}
		
		// #__menu update
		$db->setQuery("SELECT `id` FROM #__menu WHERE `link` LIKE 'index.php?option=com_rsticketspro&view=searches'");
		if ($predefinedSearches = $db->loadColumn()) {
			foreach ($predefinedSearches as $search) {
				$db->setQuery("UPDATE `#__menu` SET `link` = ".$db->q('index.php?option=com_rsticketspro&view=predefinedsearches')." WHERE `id` = ".(int) $search." ");
				$db->execute();
			}
		}

		// Department relations has changed
		$tables = $db->getTableList();
		if (in_array($db->getPrefix() . 'rsticketspro_departments_relations', $tables))
		{
			$db->setQuery("SELECT * FROM #__rsticketspro_departments_relations");
			if ($results = $db->loadObjectList())
			{
				$departments = array();
				foreach ($results as $result)
				{
					if (!isset($departments[$result->department_id]))
					{
						$departments[$result->department_id] = array();
					}

					$departments[$result->department_id][] = $result->jgroup_id;
				}

				if ($departments)
				{
					foreach ($departments as $department_id => $groups)
					{
						$db->setQuery("UPDATE #__rsticketspro_departments SET jgroups = " . $db->q(json_encode($groups)) . " WHERE id = " . $db->q($department_id))->execute();
					}
				}
			}
			$db->dropTable('#__rsticketspro_departments_relations');
		}

		$db->setQuery("UPDATE #__rsticketspro_configuration SET `value` = '100' WHERE `name` = 'export_limit' AND `value` = ''");
		$db->execute();

		$db->setQuery("UPDATE #__menu SET `link` = 'index.php?option=com_rsticketspro&view=tickets' WHERE `client_id` = '0' AND `link` = 'index.php?option=com_rsticketspro&view=rsticketspro'");
		$db->execute();

		if (JFolder::exists(JPATH_SITE . '/components/com_rsticketspro/views/rsticketspro'))
		{
			JFolder::delete(JPATH_SITE . '/components/com_rsticketspro/views/rsticketspro');
		}

        // IPv6 fixes
        $columns = $db->getTableColumns('#__rsticketspro_tickets', false);
        if ($columns['ip']->Type === 'varchar(16)')
        {
	        $db->setQuery("ALTER TABLE `#__rsticketspro_tickets` CHANGE `ip` `ip` VARCHAR(128) NOT NULL");
	        $db->execute();
        }
		$columns = $db->getTableColumns('#__rsticketspro_ticket_history', false);
		if ($columns['ip']->Type === 'varchar(16)')
		{
			$db->setQuery("ALTER TABLE `#__rsticketspro_ticket_history` CHANGE `ip` `ip` VARCHAR(128) NOT NULL");
			$db->execute();
		}
	}
	
	protected function showInstallMessage($messages=array()) {
?>
<style type="text/css">
.version-history {
	margin: 0 0 2em 0;
	padding: 0;
	list-style-type: none;
}
.version-history > li {
	margin: 0 0 0.5em 0;
	padding: 0 0 0 4em;
}
.version-new,
.version-fixed,
.version-upgraded {
	float: left;
	font-size: 0.8em;
	margin-left: -4.9em;
	width: 4.5em;
	color: white;
	text-align: center;
	font-weight: bold;
	text-transform: uppercase;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;
}
.version-new {
	background: #7dc35b;
}
.version-fixed {
	background: #e9a130;
}
.version-upgraded {
	background: #61b3de;
}

.install-ok {
	background: #7dc35b;
	color: #fff;
	padding: 3px;
}

.install-not-ok {
	background: #E9452F;
	color: #fff;
	padding: 3px;
}

.install-warning {
	background: #EFBB67;
	color: #fff;
	padding: 3px;
}

.rsticketspro-row {
	width: 100%;
	display: block;
	margin-bottom: 2%;
}

.rsticketspro-row:after {
	clear: both;
	display: block;
	content: "";
}

.rsticketspro-column-2 {
	width: 19%;
	margin-right: 1%;
	float: left;
}

.rsticketspro-column-10 {
	width: 80%;
	float: left;
}
</style>
<div class="rsticketspro-row">
	<div class="rsticketspro-column-2">
		<?php echo JHtml::_('image', 'com_rsticketspro/admin/rstickets-pro-box.png', 'RSTickets! Pro Box', array(), true); ?>
	</div>
	<div class="rsticketspro-column-10">
		<?php if ($messages['plugins']) { ?>
			<?php foreach ($messages['plugins'] as $plugin) { ?>
			<p><?php echo $this->escape($plugin->name); ?> ...
				<b class="install-<?php echo $plugin->status; ?>"><?php echo $plugin->text; ?></b>
			</p>
			<?php } ?>
		<?php } ?>
		<h2>Changelog v3.1.0</h2>
		<ul class="version-history">
            <li><span class="version-new">New</span> 'Global Message Position' and 'Submit Message Position' options allowing you to place messages at the top, bottom or disable their placement.</li>
            <li><span class="version-new">New</span> 'Show Submit Form Headings' option - allows to display headings separating the sections in the 'Ticket - Submit Form' menu item.</li>
            <li><span class="version-new">New</span> 'Thumbs Type' for the 'Tickets - Dashboard' menu item, allowing you to upload images instead of the built-in icons.</li>
            <li><span class="version-new">New</span> 'Show Search' parameter for the 'Tickets - Dashboard' menu item.</li>
            <li><span class="version-new">New</span> 'Show Category Name' option when editing a category.</li>
            <li><span class="version-new">New</span> Option to 'Split Knowledgebase To Tabs' for the 'Tickets - Dashboard' and 'Knowledgebase - List' menu items.</li>
            <li><span class="version-new">New</span> Choose the 'Top Category' for the 'Tickets - Dashboard' and 'Knowledgebase - List' menu items.</li>
            <li><span class="version-new">New</span> 'Advanced SEF' option allowing to create /category-name/article-title SEF links for the Knowledgebase.</li>
            <li><span class="version-new">New</span> 'Feedback Section' allowing you to display a 'Was this article helpful?' message at the end of an article.</li>
            <li><span class="version-new">New</span> Limit the number of subcategories displayed in the 'Tickets - Dashboard' and 'Knowledgebase - List' menu items.</li>
            <li><span class="version-new">New</span> Option to show the category thumb next to the category title when browsing one.</li>
            <li><span class="version-new">New</span> Knowledgebase articles can have 'Draft' versions that can be seen only by staff members.</li>
            <li><span class="version-new">New</span> 'Thumb' option for Knowledgebase articles.</li>
            <li><span class="version-new">New</span> Knowledgebase articles can be tagged and filtered in the frontend based on those tags.</li>
            <li><span class="version-new">New</span> Categories can now show articles either as a list or columns (with a pre-set number of columns available).</li>
            <li><span class="version-new">New</span> Option to auto-calculate the 'Hot' article threshold to 75% of the most viewed article.</li>
            <li><span class="version-new">New</span> Option to skip the 'Consent' checkbox for staff members.</li>
            <li><span class="version-upgraded">Upg</span> Tickets can now be deleted from the ticket toolbar (if staff has permission).</li>
            <li><span class="version-upgraded">Upg</span> Breadcrumb now shows the current ticket.</li>
            <li><span class="version-upgraded">Upg</span> Frontend HTML has been reworked and more classes added to aid in custom styiling.</li>

		</ul>
		<p>
			<a class="btn btn-large btn-primary" href="index.php?option=com_rsticketspro">Start using RSTickets! Pro</a>
			<a class="btn btn-secondary" href="https://www.rsjoomla.com/support/documentation/rsticketspro.html" target="_blank">Read the RSTickets! Pro User Guide</a>
			<a class="btn btn-secondary" href="https://www.rsjoomla.com/support.html" target="_blank">Get Support!</a>
		</p>
	</div>
</div>
		<?php
	}
}