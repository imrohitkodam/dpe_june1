<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.application.component.controller');

if (!defined('DS'))
{
	define('DS', '/');
}

/**
 * Tjlms Installer
 *
 * @since  1.0.0
 */
class Com_TjlmsInstallerScript
{
	/** @var array The list of extra modules and plugins to install */
	private $oldversion = "";

	private $installation_queue = array(
								'modules' => array(

										),
										// Defined as : plugins => { (folder) => { (element) => (published) }* }*
								'plugins' => array(

											),
								'libraries' => array()
								);

	private $uninstall_queue = array(
								// DEFINED as : modules => { (folder) => { (module) => { (position), (published) } }* }*
								'modules' => array(

								),
								// DEFINED as : plugins => { (folder) => { (element) => (published) }* }*
								'plugins' => array()

								);

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @param   JInstaller  $type    type
	 * @param   JInstaller  $parent  parent
	 *
	 * @return void
	 */
	public function preflight($type, $parent)
	{
	}

	/**
	 * method to install the component
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  void
	 */
	public function install($parent)
	{
		// $parent is the class calling this method
		$this->installSqlFiles($parent);

		// Entry for dashboard plugins
		$this->insertDashboardPlugin();

		$this->setshikadefaultBavior();
	}

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   string      $type    install, update or discover_update
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  boolean
	 */
	public function postflight($type, $parent)
	{
		$this->addESGroupToCourseparams();

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$dataBaseModel = BaseDatabaseModel::getInstance('Database', 'TjlmsModel');

		try
		{
			$dataBaseModel->allowEnrolforallGroups();
		}
		catch (Exception $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * method to setshikadefaultBavior
	 *
	 * @return void
	 */
	public function setshikadefaultBavior()
	{
		$user                    = Factory::getUser();
		$db  = Factory::getDbo();

		// Check if tag exists
		$sql = $db->getQuery(true)->select($db->qn('type_id'))
			->from($db->qn('#__content_types'))
			->where($db->qn('type_title') . ' = ' . $db->q('Course'))
			->where($db->qn('type_alias') . ' = ' . $db->q('com_tjlms.course'));
		$db->setQuery($sql);
		$type_id = $db->loadResult();

		// Create tag
		$db                                 = Factory::getDBO();
		$tagobject                          = new stdclass;
		$tagobject->type_id                 = '';
		$tagobject->type_title              = 'Course';
		$tagobject->type_alias              = 'com_tjlms.course';
		$tagobject->table                   = '{"special":{"dbtable":"#__tjlms_courses","key":"id","type":"Tjlmscourse",'
		. '"prefix":"Table","config":"array()"},'
		. '"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"Table","config":"array()"}}';
		$tagobject->rules                   = '';

		$field_mappings_arr = array(
		'common' => array(
					"core_content_item_id" => "id",
					"core_title" => "title",
					"core_state" => "state",
					"core_alias" => "alias",
					"core_created_time" => "created",
					"core_modified_time" => "modified",
					"core_body" => "description",
					"core_hits" => "null",
					"core_publish_up" => "start_date",
					"core_publish_down" => "end_date",
					"core_access" => "access",
					"core_params" => "params",
					"core_featured" => "featured",
					"core_metadata" => "null",
					"core_language" => "null",
					"core_images" => "image",
					"core_urls" => "null",
					"core_version" => "null",
					"core_ordering" => "ordering",
					"core_metakey" => "metakey",
					"core_metadesc" => "metadesc",
					"core_catid" => "cat_id",
					"core_xreference" => "null",
					"asset_id" => "asset_id"
				),
		'special' => array(
					"parent_id" => "parent_id",
					"lft" => "lft",
					"rgt" => "rgt",
					"level" => "level",
					"path" => "path",
					"path" => "path",
					"extension" => "extension",
					"extension" => "extension",
					"note" => "note"
					)
		);
		$tagobject->field_mappings          = json_encode($field_mappings_arr);

		$tagobject->router                  = 'TjlmscourseHelperRoute::getCourseRoute';

		$content_history_options_arr = array(
		'formFile' => "administrator\/components\/com_tjlms\/models\/forms\/course.xml",
		'hideFields' => '["asset_id","checked_out","checked_out_time"],"ignoreChanges":["checked_out", "checked_out_time"]',
		'convertToInt' => '["ordering"]',
		'displayLookup' => '[{"sourceColumn":"cat_id",
		"targetTable":"#__categories","targetColumn":"id","displayColumn":"title"},
		{"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"}]'
		);

		$tagobject->content_history_options = json_encode($content_history_options_arr);

		if (!$type_id)
		{
			if (!$db->insertObject('#__content_types', $tagobject, 'type_id'))
			{
				echo $db->stderr();

				return false;
			}
		}
		else
		{
			$tagobject->type_id = $type_id;

			if (!$db->updateObject('#__content_types', $tagobject, 'type_id'))
			{
				echo $db->stderr();

				return false;
			}
		}

		/** @var JTableContentType $table */
		$table	= Table::getInstance('contenttype');

		if ($table)
		{
			$table->load(array('type_alias' => 'com_tjlms.category'));

			if (!$table->type_id)
			{
				$data	= array(
					'type_title'		=> 'Shika Category',
					'type_alias'		=> 'com_tjlms.category',
					'table'				=> '{"special":{"dbtable":"#__categories","key":"id","type":"Category","prefix":"Table","config":"array()"},'
					. '"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"Table","config":"array()"}}',
					'rules'				=> '',
					'field_mappings'	=> '
					{"common":{
					"core_content_item_id":"id",
					"core_title":"title",
					"core_state":"published",
					"core_alias":"alias",
					"core_created_time":"created_time",
					"core_modified_time":"modified_time",
					"core_body":"description",
					"core_hits":"hits",
					"core_publish_up":"null",
					"core_publish_down":"null",
					"core_access":"access",
					"core_params":"params", "core_featured":"null",
					"core_metadata":"metadata", "core_language":"language",
					"core_images":"null", "core_urls":"null", "core_version":"version", "core_ordering":"null", "core_metakey":"metakey",
					"core_metadesc":"metadesc", "core_catid":"parent_id",
					"core_xreference":"null", "asset_id":"asset_id"},
					"special": {
					"parent_id":"parent_id",
					"lft":"lft",
					"rgt":"rgt",
					"level":"level",
					"path":"path",
					"extension":"extension",
					"note":"note"
					}
					}',
					'content_history_options' => '{"formFile":"administrator\/components\/com_categories\/models\/forms\/category.xml",
					"hideFields":["asset_id","checked_out","checked_out_time","version","lft","rgt","level","path","extension"],

					"ignoreChanges":["modified_user_id", "modified_time", "checked_out", "checked_out_time", "version", "hits", "path"],

					"convertToInt":["publish_up", "publish_down"],
	"displayLookup":[{"sourceColumn":"created_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},
					{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},
					{"sourceColumn":"modified_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},
					{"sourceColumn":"parent_id","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}]}',
				);

				$table->bind($data);

				if ($table->check())
				{
					$table->store();
				}
			}

			// Create deny and allow files
			$this->createHtaccessFiles();
		}

		// Create default category on installation if not exists
		$sql = $db->getQuery(true)->select($db->quoteName('id'))
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('extension') . ' = ' . $db->quote('com_tjlms'));

		$db->setQuery($sql);
		$cat_id = $db->loadResult();

		if (empty($cat_id))
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models/', 'CategoriesModel');
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables/');

			// Create "course" category.
			$categoryModel = BaseDatabaseModel::getInstance('Category', 'CategoriesModel');

			$categoryArray = array(
				'title'           => 'Uncategorised',
				'parent_id'       => 1,
				'id'              => 0,
				'published'       => 1,
				'access'          => 1,
				'created_user_id' => $user->id,
				'extension'       => 'com_tjlms',
				'level'           => 1,
				'alias'           => 'uncategorised',
				'associations'    => array(),
				'description'     => '<p>This is a default Shika lesson category</p>',
				'language'        => '*',
				'params'          => '',
			);

			if (!$categoryModel->save($categoryArray))
			{
				throw new Exception($categoryModel->getError());
			}
		}

		// Create default category on installation if not exists
		$sql = $db->getQuery(true)->select($db->quoteName('id'))
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('extension') . ' = ' . $db->quote('com_tjlms.lessons'));

		$db->setQuery($sql);
		$cat_id = $db->loadResult();

		if (empty($cat_id))
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models/', 'CategoriesModel');
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables/');

			// Create "blog" category.
			$categoryModel = BaseDatabaseModel::getInstance('Category', 'CategoriesModel');

			$categoryArray = array(
				'title'           => 'Uncategorised',
				'parent_id'       => 1,
				'id'              => 0,
				'published'       => 1,
				'access'          => 1,
				'created_user_id' => $user->id,
				'extension'       => 'com_tjlms.lessons',
				'level'           => 1,
				'alias'           => 'uncategorised',
				'associations'    => array(),
				'description'     => '<p>This is a default Shika lesson category</p>',
				'language'        => '*',
				'params'          => '',
			);

			if (!$categoryModel->save($categoryArray))
			{
				throw new Exception($categoryModel->getError());
			}

			$category = Table::getInstance('Category', 'JTable', array('dbo', $db));
			$category->load(array('extension' => 'com_tjlms.lessons'));

			// Fields to update.
			$fields = array(
				$db->quoteName('catid') . ' = ' . (int) $category->id
			);

			$sql = $db->getQuery(true)->update($db->quoteName('#__tjlms_lessons'))
			->set($fields)
			->where($db->quoteName('catid') . " = ''");

			$db->setQuery($sql);
			$result = $db->execute();
		}
	}

	/**
	 * installSqlFiles
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  void
	 */
	public function installSqlFiles($parent)
	{
		$db = Factory::getDBO();

		// Obviously you may have to change the path and name if your installation SQL file ;)
		if (method_exists($parent, 'extension_root'))
		{
			$sqlfile = $parent->getPath('extension_root') . DS . 'admin' . DS . 'sql' . DS . 'install.mysql.utf8.sql';
		}
		else
		{
			$sqlfile = $parent->getParent()->getPath('extension_root') . DS . 'sql' . DS . 'install.mysql.utf8.sql';
		}

		// Don't modify below this line
		$buffer = file_get_contents($sqlfile);

		if ($buffer !== false)
		{
			$queries = JDatabaseDriver::splitSql($buffer);

			if (count($queries) != 0)
			{
				foreach ($queries as $query)
				{
					$query = trim($query);

					if ($query != '' && $query['0'] != '#')
					{
						$db->setQuery($query);

						if (!$db->execute())
						{
							$app->enqueueMessage(Text::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)), 'error');
							return false;
						}
					}
				}
			}
		}
	}

	/**
	 * Uninstalls subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  JObject The subextension uninstallation status
	 */
	private function _uninstallSubextensions($parent)
	{
		jimport('joomla.installer.installer');

		$db = Factory::getDBO();

		$status          = new CMSObject;
		$status->modules = array();
		$status->plugins = array();

		$src = $parent->getParent()->getPath('source');

		// Modules uninstallation
		if (count($this->uninstall_queue['modules']))
		{
			foreach ($this->uninstall_queue['modules'] as $folder => $modules)
			{
				if (count($modules))
				{
					foreach ($modules as $module => $modulePreferences)
					{
						// Find the module ID
						$sql = $db->getQuery(true)->select($db->qn('extension_id'))
							->from($db->qn('#__extensions'))
							->where($db->qn('element') . ' = ' . $db->q('mod_' . $module))
							->where($db->qn('type') . ' = ' . $db->q('module'));
						$db->setQuery($sql);
						$id = $db->loadResult();

						// Uninstall the module
						if ($id)
						{
							$installer         = new Installer;
							$result            = $installer->uninstall('module', $id, 1);
							$status->modules[] = array(
								'name' => 'mod_' . $module,
								'client' => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		// Plugins uninstallation
		if (count($this->uninstall_queue['plugins']))
		{
			foreach ($this->uninstall_queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$sql = $db->getQuery(true)
								->select($db->qn('extension_id'))
								->from($db->qn('#__extensions'))
								->where($db->qn('type') . ' = ' . $db->q('plugin'))
								->where($db->qn('element') . ' = ' . $db->q($plugin))
								->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($sql);

						$id = $db->loadResult();

						if ($id)
						{
							$installer         = new Installer;
							$result            = $installer->uninstall('plugin', $id);
							$status->plugins[] = array(
								'name' => 'plg_' . $plugin,
								'group' => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		return $status;
	}

	/**
	 * _renderPostUninstallation
	 *
	 * @param   STRING  $status  status of installed extensions
	 * @param   ARRAY   $parent  parent item
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	private function _renderPostUninstallation($status, $parent)
	{
?>
	   <?php
		$rows = 0;
?>
	   <h2><?php
		echo Text::_('TjLMS Uninstallation Status');
?></h2>
		<table class="adminlist">
			<thead>
				<tr>
					<th class="title" colspan="2"><?php
		echo Text::_('Extension');
?></th>
					<th width="30%"><?php
		echo Text::_('Status');
?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="3"></td>
				</tr>
			</tfoot>
			<tbody>
				<tr class="row0">
					<td class="key" colspan="2"><?php
		echo 'TjLMS ' . Text::_('Component');
?></td>
					<td><strong style="color: green"><?php
		echo Text::_('Removed');
?></strong></td>
				</tr>
				<?php
		if (count($status->modules))
		{
?>
			   <tr>
					<th><?php
			echo Text::_('Module');
?></th>
					<th><?php
			echo Text::_('Client');
?></th>
					<th></th>
				</tr>
				<?php
			foreach ($status->modules as $module)
			{
				++$rows;
?>
			   <tr class="row<?php echo $rows % 2;?>">
					<td class="key"><?php echo $module['name'];?></td>
					<td class="key"><?php echo ucfirst($module['client']);?></td>
					<td>
						<strong style="color: <?php echo $module['result'] ? "green" : "red";?>">
							<?php echo $module['result'] ? Text::_('Removed') : Text::_('Not removed');?>
						</strong>
					</td>
				</tr>
				<?php
			}
?>
			   <?php
		}
?>
			   <?php
		if (count($status->plugins))
		{
?>
			   <tr>
					<th><?php
			echo Text::_('Plugin');
?></th>
					<th><?php
			echo Text::_('Group');
?></th>
					<th></th>
				</tr>
				<?php
			foreach ($status->plugins as $plugin)
			{
				++$rows;
				?>

			   <tr class="row<?php echo $rows % 2;?>">
					<td class="key"><?php echo ucfirst($plugin['name']);?></td>
					<td class="key"><?php echo ucfirst($plugin['group']);?></td>
					<td>
						<strong style="color: <?php echo $plugin['result'] ? "green" : "red";?>">
							<?php echo $plugin['result'] ? Text::_('Removed') : Text::_('Not removed');?>
						</strong>
					</td>
				</tr>
	<?php
			}
		}
	?>
		   </tbody>
		</table>
		<?php
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param   JInstaller  $parent  Parent
	 *
	 * @return void
	 *
	 * @since   1.0.0
	 */
	public function uninstall($parent)
	{
		// Uninstall subextensions
		$status = $this->_uninstallSubextensions($parent);

		// Show the post-uninstallation page
		$this->_renderPostUninstallation($status, $parent);
	}

	/**
	 * method to update the component
	 *
	 * @param   JInstaller  $parent  Parent
	 *
	 * @return void
	 */
	public function update($parent)
	{
		$this->installSqlFiles($parent);

		$this->fix_db_on_update();

		$this->setshikadefaultBavior();

		$this->deleteUnexistingFiles();
	}

	/**
	 * Delete files that should not exist
	 *
	 * @return  void
	 */
	public function deleteUnexistingFiles()
	{
		include JPATH_ADMINISTRATOR . '/components/com_tjlms/deletelist.php';

		jimport('joomla.filesystem.file');

		foreach ($files as $file)
		{
			if (File::exists(JPATH_ROOT . $file) && !File::delete(JPATH_ROOT . $file))
			{
				$app->enqueueMessage(Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file));
			}
		}

		jimport('joomla.filesystem.folder');

		foreach ($folders as $folder)
		{
			if (Folder::exists(JPATH_ROOT . $folder) && !Folder::delete(JPATH_ROOT . $folder))
			{
				$app->enqueueMessage(Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $folder));
			}
		}
	}

	/**
	 * Get updated table modified
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function fix_db_on_update()
	{
		// Entry for dashboard plugins
		$this->insertDashboardPlugin();
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$dataBaseModel = BaseDatabaseModel::getInstance('Database', 'TjlmsModel');

		try
		{
			$dataBaseModel->fixIgnorekeyIndexes();
			$dataBaseModel->fixColumnChange();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * function to insert dashboard plugin entry
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function insertDashboardPlugin()
	{
		/* Check dashboard entries */

		$db = Factory::getDbo();

		$query = $db->getQuery(true);

		// Get tjLms_dashboard
		$query->select($db->quoteName('id'));
		$query->select($db->quoteName('plugin_name'));
		$query->from($db->quoteName('#__tjlms_dashboard'));
		$db->setQuery($query);
		$plugins = $db->loadAssocList('plugin_name');
		$plugresult = array_keys($plugins);

		// Create an array for dashboard plugins entry
		$dashPlgArray = array
		(
			array('plg_name' => 'enrolledcoursescount', 'plg_size' => 'span6', 'plg_order' => '1'),
			array('plg_name' => 'pendindenrolledcount', 'plg_size' => 'span6', 'plg_order' => '2'),
			array('plg_name' => 'inprogresscoursescount', 'plg_size' => 'span6', 'plg_order' => '3'),
			array('plg_name' => 'completedcoursescount', 'plg_size' => 'span6', 'plg_order' => '4'),
			array('plg_name' => 'totaltimespent', 'plg_size' => 'span6', 'plg_order' => '5'),
			array('plg_name' => 'totalidealtime', 'plg_size' => 'span6', 'plg_order' => '6'),
			array('plg_name' => 'enrolledcourses', 'plg_size' => 'span12', 'plg_order' => '7'),
			array('plg_name' => 'likedcourses', 'plg_size' => 'span6', 'plg_order' => '8'),
			array('plg_name' => 'recommendedcourses', 'plg_size' => 'span6', 'plg_order' => '9'),
			array('plg_name' => 'likedlesson', 'plg_size' => 'span6', 'plg_order' => '10'),
			array('plg_name' => 'mygroups', 'plg_size' => 'span6', 'plg_order' => '11'),
			array('plg_name' => 'groupdiscussion', 'plg_size' => 'span6', 'plg_order' => '12'),
			array('plg_name' => 'mydiscussions', 'plg_size' => 'span6', 'plg_order' => '13'),
			array('plg_name' => 'activitylist', 'plg_size' => 'span6', 'plg_order' => '14'),
			array('plg_name' => 'activitygraph', 'plg_size' => 'span12', 'plg_order' => '15')
		);

		foreach ($dashPlgArray as $key => $value)
		{
			$dashboardPlgData = new stdClass;
			$dashboardPlgData->plugin_name = $value['plg_name'];
			$dashboardPlgData->size = $value['plg_size'];
			$dashboardPlgData->ordering = $value['plg_order'];

			if (!in_array($value['plg_name'], $plugresult))
			{
				// Create and populate an object.
				$dashboardPlgData->id = '';
				$dashboardPlgData->user_id = 0;
				$dashboardPlgData->params = '';

				// Insert the object into the user profile table.
				$result = $db->insertObject('#__tjlms_dashboard', $dashboardPlgData, 'id');
			}
			else
			{
				$plg_name = $value['plg_name'];
				$dashboardPlgData->id = $plugins[$plg_name]['id'];
				$result = $db->updateObject('#__tjlms_dashboard', $dashboardPlgData, 'id');
			}
		}
	}

	/**
	 * Tag line, version etc
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function taglinMsg()
	{
		/*:TODO*/
	}

	/**
	 * functions to Create deny and allow
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function createHtaccessFiles()
	{
		$app = Factory::getApplication();
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		$lessons_dir	=	JPATH_SITE . '/media/com_tjlms/lessons';

		if (!Folder::exists($lessons_dir))
		{
			$result = Folder::create($lessons_dir);

			if (!$result)
			{
				$app->enqueueMessage('Could not create folder - ' . $lessons_dir, 'warning');
			}
		}

		$folders = Folder::folders($lessons_dir);

		// Create htaccess file in lessons root to deny direct files access of the folder
		$htaccessFile	=	$lessons_dir . '/.htaccess';

		if (File::exists($htaccessFile))
		{
			File::delete($htaccessFile);
		}

		if (!File::exists($htaccessFile))
		{
			$content = "deny from all
<Files *.flv>
    Order Allow,Deny
    Allow from all
</Files>
<Files *.mp4>
    Order Allow,Deny
    Allow from all
</Files>
<Files *.webm>
    Order Allow,Deny
    Allow from all
</Files>
<Files *.aac>
    Order Allow,Deny
    Allow from all
</Files>
<Files *.m4a>
    Order Allow,Deny
    Allow from all
</Files>
<Files *.f4a>
    Order Allow,Deny
    Allow from all
</Files>
<Files *.mp3>
    Order Allow,Deny
    Allow from all
</Files>
<Files *.ogg>
    Order Allow,Deny
    Allow from all
</Files>
<Files *.oga>
    Order Allow,Deny
    Allow from all
</Files>
<Files *.pdf>
    Order Allow,Deny
    Allow from all
</Files>
";
			$result = File::write($htaccessFile, $content);

			if (!$result)
			{
				$app->enqueueMessage('Could not create file - ' . $htaccessFile, 'warning');
			}
		}

		if (!empty($folders))
		{
			foreach ($folders as $lesson_folder)
			{
				// Create htaccess file in each lessons folder to allow direct files access of the folder
				$htaccessFile	=	$lessons_dir . '/' . $lesson_folder . '/.htaccess';

				if (!File::exists($htaccessFile))
				{
					$content = "allow from all";
					$result = File::write($htaccessFile, $content);

					if (!$result)
					{
						$app->enqueueMessage('Could not create file - ' . $htaccessFile, 'warning');
					}
				}
			}
		}
	}

	/**
	 * function to add course params.
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function addESGroupToCourseparams()
	{
		$db  = Factory::getDbo();
		$sql = $db->getQuery(true)->select('id, group_id, params')
			->from($db->qn('#__tjlms_courses'));
		$db->setQuery($sql);
		$courses = $db->loadAssocList();
		$params = array();

		foreach ($courses as $course)
		{
			if (!$course['group_id'])
			{
				continue;
			}

			$onAfterEnrollEsGroups = array();
			$esgArray = array();
			$onAfterEnrollEsGroups['onAfterEnrollEsGroups'] = (array) $course['group_id'];
			$esgArray['esgroup'] = $onAfterEnrollEsGroups;

			$obj		= new stdclass;
			$obj->id	= $course['id'];
			$obj->group_id = '';

			if (!empty($course['params']))
			{
				$cparams = (array) json_decode($course['params']);
				$cparams['esgroup'] = $onAfterEnrollEsGroups;
				$obj->params = json_encode($cparams);
			}
			else
			{
				$obj->params = json_encode($esgArray);
			}

			$result = $db->updateObject('#__tjlms_courses', $obj, 'id');
		}
	}
}

