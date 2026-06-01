<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_TMT
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.application.component.controller');

/**
 * TMT Installer
 *
 * @since  1.0.0
 */
class Com_TmtInstallerScript
{
	/** @var array The list of extra modules and plugins to install */
	private $oldversion = "";

	// Used to identify new install or update
	private $componentStatus = "install";

	private $installation_queue = array(
		// DEFINED AS : modules => { (folder) => { (module) => { (position), (published) } }* }*
		'modules' => array(
			'admin' => array(

						),
			'site' => array(
						)
		),
		// DEFINED AS : plugins => { (folder) => { (element) => (published) }* }*
		'plugins' => array(

		)
	);

	private $uninstall_queue = array(
		// DEFINED AS : modules => { (folder) => { (module) => { (position), (published) } }* }*
		'modules' => array(
			'admin' => array(

						),
			'site' => array(
						)
		),

		// DEFINED AS : plugins => { (folder) => { (element) => (published) }* }*
		'plugins' => array(

		)
	);

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @param   JInstaller  $type    type
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  void
	 */
	public function preflight($type, $parent)
	{
	}

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   string      $type    install, update or discover_update
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  void
	 */
	public function postflight( $type, $parent )
	{
		$msgBox = array();

		if (version_compare(JVERSION, '3.0', 'lt'))
		{
			$document = Factory::getDocument();
			$document->addStyleSheet(JURI::root() . '/media/techjoomla_strapper/css/bootstrap.min.css');
		}

		/*JUGAD FOR HIDE ADMIN menu */
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query = "DELETE FROM `#__menu` WHERE `alias` LIKE 'com-tmt' AND `client_id`=11";
		$db->setQuery($query);
		$db->execute();
		$query = $db->getQuery(true);
		$query = "UPDATE `#__menu` SET `client_id`=11 WHERE `alias` LIKE 'com-tmt'";
		$db->setQuery($query);
		$result = $db->execute();
		/*JUGAD FOR HIDE ADMIN menu */

		// Do all releated Tag line/ logo etc
		$this->taglinMsg();

		/*POST 1.3 add section in each test and add entries in the media if not added and update the lesson for that media id*/
		$this->migrateSections();
		$this->migrateQuizMedia();

		/** @var JTableContentType $table */
		$table	= Table::getInstance('contenttype');

		if ($table)
		{
			$table->load(array('type_alias' => 'com_tmt.questions.category'));

			if (!$table->type_id)
			{
				$field_mappings_arr = array(
				'common' => array(
					"core_content_item_id" => "id",
					"core_title" => "title",
					"core_state" => "published",
					"core_alias" => "alias",
					"core_created_time" => "created_time",
					"core_modified_time" => "modified_time",
					"core_body" => "description",
					"core_hits" => "hits",
					"core_publish_up" => "null",
					"core_publish_down" => "null",
					"core_access" => "access",
					"core_params" => "params",
					"core_featured" => "null",
					"core_metadata" => "metadata",
					"core_language" => "language",
					"core_images" => "null",
					"core_urls" => "null",
					"core_version" => "version",
					"core_ordering" => "null",
					"core_metakey" => "metakey",
					"core_metadesc" => "metadesc",
					"core_catid" => "parent_id",
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

				$content_history_optionsArr = array();

				$data	= array(
					'type_title'		=> 'Questions Category',
					'type_alias'		=> 'com_tmt.questions.category',
					'table'				=> '{"special":{"dbtable":"#__categories","key":"id","type":"Category","prefix":"Table","config":"array()"},' .
					'"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"Table","config":"array()"}}',
					'rules'				=> '',
					'field_mappings'	=> json_encode($field_mappings_arr),
					'content_history_options' => '{"formFile":"administrator\/components\/com_categories\/models\/forms\/category.xml",'
					. '"hideFields":["asset_id","checked_out","checked_out_time","version","lft","rgt","level","path","extension"],'
					. '"ignoreChanges":["modified_user_id", "modified_time", "checked_out", "checked_out_time", "version", "hits", "path"],'
					. '"convertToInt":["publish_up", "publish_down"], '
					. '"displayLookup":[{"sourceColumn":"created_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},'
					. '{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},'
					. ' {"sourceColumn":"modified_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},'
					. '{"sourceColumn":"parent_id","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}]}',
				);

				$table->bind($data);

				if ($table->check())
				{
					$table->store();
				}
			}
		}

		$this->deleteUnexistingFiles();
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
		$db = Factory::getDbo();
		$user = Factory::getUser();

		// Create default category on installation if not exists
		$sql = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('extension') . ' = ' . $db->quote('com_tmt.questions'));

		$db->setQuery($sql);
		$cat_id = $db->loadResult();

		if (empty($cat_id))
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models/', 'CategoriesModel');
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables/');

			// Create "questions" category.
			$categoryModel = BaseDatabaseModel::getInstance('Category', 'CategoriesModel');

			$categoryArray = array(
				'title'           => 'Uncategorised',
				'parent_id'       => 1,
				'id'              => 0,
				'published'       => 1,
				'access'          => 1,
				'created_user_id' => $user->id,
				'extension'       => 'com_tmt.questions',
				'level'           => 1,
				'alias'           => 'uncategorised',
				'associations'    => array(),
				'description'     => '<p>This is a default Question category</p>',
				'language'        => '*',
				'params'          => '',
			);

			if (!$categoryModel->save($categoryArray))
			{
				throw new Exception($categoryModel->getError());
			}
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
		$app  = Factory::getApplication();

		// Obviously you may have to change the path and name if your installation SQL file ;)
		if (method_exists($parent, 'extension_root'))
		{
			$sqlfile = $parent->getPath('extension_root') . '/admin/sql/install.mysql.utf8.sql';
		}
		else
		{
			$sqlfile = $parent->getParent()->getPath('extension_root') . '/sql/install.mysql.utf8.sql';
		}

		// Don't modify below this line
		$buffer = file_get_contents($sqlfile);

		if ($buffer !== false)
		{
			$queries = \JDatabaseDriver::splitSql($buffer);

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
		$this->componentStatus = "update";
		$this->installSqlFiles($parent);
		$this->fix_db_on_update();
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
		$tmt_tests_columns = array(
			'start_date' => "datetime  NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'parent_id' => "int(11) NOT NULL AFTER `id`" ,
			'type' => "varchar(100) DEFAULT 'plain' NOT NULL  AFTER `parent_id`" ,
			'end_date' => "datetime  NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'alias' => "VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER  `title`",
			'questions_shuffle' => "INT(11)",
			'answers_shuffle' => "INT(11)"
		);
		$this->alterTables("#__tmt_tests", $tmt_tests_columns);

		$tmt_answers_columns = array(
			'id' => 'int(11) unsigned NOT NULL AUTO_INCREMENT',
			'question_id' => 'int(11) NOT NULL',
			'answer' => 'text NOT NULL',
			'marks' => 'int(3) NOT NULL',
			'is_correct' => 'tinyint(1) NOT NULL',
			'order' => 'int(3) NOT NULL',
			'comments' => "text  NOT NULL "
		);
		$this->alterTables("#__tmt_answers", $tmt_answers_columns);

		$tmt_questions_columns = array(
			'params' => "text NOT NULL"
		);

		$this->alterTables("#__tmt_questions", $tmt_questions_columns);

		$tmt_tests_answers = array(
			'anss_order' => 'VARCHAR( 255 ) NOT NULL '
		);
		$this->alterTables("#__tmt_tests_answers", $tmt_tests_answers);
	}

	/**
	 * alterTables
	 *
	 * @param   STRING  $table   Table name
	 * @param   ARRAY   $colums  colums name
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function alterTables($table, $colums)
	{
		$db    = Factory::getDBO();
		$query = "SHOW COLUMNS FROM {$table}";
		$db->setQuery($query);

		$res = $db->loadColumn();

		foreach ($colums as $c => $t)
		{
			if (!in_array($c, $res))
			{
				$query = "ALTER TABLE {$table} add column $c $t;";
				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	/**
	 * Delete files that should not exist
	 *
	 * @return  void
	 */
	public function deleteUnexistingFiles()
	{
		include JPATH_ADMINISTRATOR . '/components/com_tmt/deletelist.php';

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
	 * method to add a section for each test post 1.3
	 *
	 * @return  void
	 *
	 * @since 1.3
	 */
	public function migrateSections()
	{
		$db = Factory::getDbo();
		$subquery = $db->getQuery(true);
		$subquery->select($db->quoteName('ts.test_id'));
		$subquery->from($db->quoteName('#__tmt_tests_sections', 'ts'));

		$query = $db->getQuery(true);
		$query->select($db->quoteName('t.id'));
		$query->from($db->quoteName('#__tmt_tests', 't'));
		$query->where($db->quoteName('t.id') . " NOT IN (" . $subquery . ")");
		$db->setQuery($query);
		$testIdArray = $db->loadColumn();

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tmt/models');

		foreach ($testIdArray as $testId)
		{
			// Create the dafault section for each test
			$sectionModel = BaseDatabaseModel::getInstance('Section', 'TmtModel');
			$sectionData = array();
			$sectionData['title'] = "Section 1";
			$sectionData['test_id'] = $testId;
			$sectionData['state'] = 1;
			$sectionData['ordering'] = 1;
			$sectionModel->save($sectionData);

			$sectionId = $sectionModel->getState($sectionModel->getName() . '.id');

			// Add the section id in tmt_tests_questions
			$query = $db->getQuery(true);
			$fields = array(
				$db->quoteName('section_id') . ' = ' . (int) $sectionId,
			);
			$conditions = array(
				$db->quoteName('test_id') . ' = ' . $testId
			);
			$query->update($db->quoteName('#__tmt_tests_questions'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$result = $db->execute();

			// If dynamic test, add section id in rules
			$query = $db->getQuery(true);
			$fields = array(
				$db->quoteName('section_id') . ' = ' . (int) $sectionId,
			);
			$conditions = array(
				$db->quoteName('quiz_id') . ' = ' . $testId
			);
			$query->update($db->quoteName('#__tmt_quiz_rules'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$result = $db->execute();
		}
	}

	/**
	 * method to add a media against each Quiz which is used in a lesson
	 *
	 * @return  void
	 *
	 * @since 1.3
	 */
	public function migrateQuizMedia()
	{
		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('tt.id', 'tt.lesson_id', 'tt.test_id','t.resume','t.total_marks','t.passing_marks')));
			$query->from($db->quoteName('#__tjlms_tmtquiz', 'tt'));
			$query->join("INNER", $db->quoteName('#__tmt_tests', 't') . " ON t.id=tt.test_id");
			$query->join("INNER", $db->quoteName('#__tjlms_lessons', 'l') . " ON l.id=tt.lesson_id");
			$db->setQuery($query);
			$testLessonArray = $db->loadObjectList();

			require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/lesson.php';
			require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/media.php';

			foreach ($testLessonArray as $tl)
			{
				$lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');
				$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');

				$data = $mediaData = array();

				$mediaData['format'] = "quiz";
				$mediaData['sub_format'] = "quiz.test";
				$mediaData['source'] = $tl->test_id;

				$data['media_id'] = $mediaModel->getMediaIdByData($mediaData);

				if (!$data['media_id'])
				{
					$mediaModel->save($mediaData);
					$data['media_id'] = $mediaModel->getState($mediaModel->getName() . '.id');
				}

				$data['id'] = $tl->lesson_id;
				$data['format'] = "quiz";
				$data['total_marks'] = $tl->total_marks;
				$data['resume'] = $tl->resume;
				$data['passing_marks'] = $tl->passing_marks;

				if ($lessonModel->save($data))
				{
					$dquery = $db->getQuery(true);

					$conditions = array(
						$db->quoteName('id') . ' = ' . $tl->id
					);

					$dquery->delete($db->quoteName('#__tjlms_tmtquiz'));
					$dquery->where($conditions);
					$db->setQuery($dquery);
					$db->execute();
				}
			}
		}
		catch (Exception $e)
		{
			return false;
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
}
