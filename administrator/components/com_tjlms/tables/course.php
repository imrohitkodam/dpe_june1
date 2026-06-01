<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Table\Observer\Tags;
use Joomla\CMS\Table\Observer\AbstractObserver;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Access\Rules;
use Joomla\String\StringHelper;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Versioning\VersionableTableInterface;

/**
 * course Table class
 *
 * @since  1.0.0
 */
class TjlmsTablecourse extends Table  implements VersionableTableInterface, TaggableTableInterface
{
	use TaggableTableTrait;
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		$this->setColumnAlias('published', 'state');
		$this->typeAlias = 'com_tjlms.course';

		// Create or set a Dispatcher
		$dispatcher = Factory::getApplication()->getDispatcher();

		$this->setDispatcher($dispatcher);

		$event = Joomla\CMS\Event\AbstractEvent::create(
			'onTableObjectCreate',
			[
				'subject'	=> $this,
			]
		);
		$this->getDispatcher()->dispatch('onTableObjectCreate', $event);

		parent::__construct('#__tjlms_courses', 'id', $db);
	}

	/**
	 * Overloaded bind function to pre-process the params.
	 *
	 * @param   array   $array   Named array
	 * @param   string  $ignore  string
	 *
	 * @return   null|string    null is operation was satisfactory, otherwise returns an error
	 *
	 * @since  1.0.0
	 */
	public function bind($array, $ignore = '')
	{
		$input  = Factory::getApplication()->input;
		$task   = $input->getString('task', '');
		$user   = Factory::getUser();
		$userId = $user->get('id');

		if ($task == 'save' || $task == 'apply' || $task == 'save2copy')
		{
			$canCreate = $user->authorise('core.create', 'com_tjlms');
			$canChange = true;

			if (empty($array['id']))
			{
				$canChange = $canCreate;
			}
			elseif (!empty($array['created_by']))
			{
				$manageOwn	= $canCreate && $userId == $array['created_by'];
				$canChange 	= $user->authorise('core.edit.state', 'com_tjlms.course.' . $array['id']) || $manageOwn;
			}

			if (!$canChange)
			{
				unset($array['state']);
			}
		}

		$files = $input->files->get('jform');

		if (isset($array['params']) && is_array($array['params']))
		{
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

		if (isset($array['metadata']) && is_array($array['metadata']))
		{
			$registry = new Registry;
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string) $registry;
		}

		if (isset($array['rules']) && is_array($array['rules']))
		{
			$rules = new Rules($array['rules']);
			$this->setRules($rules);
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * This function convert an array of JAccessRule objects into an rules array.
	 *
	 * @param   type  $jaccessrules  an arrao of JAccessRule objects.
	 *
	 * @return  rule
	 *
	 * @since  1.0.0
	 */
	private function RulestoArray($jaccessrules)
	{
		$rules = array();

		foreach ($jaccessrules as $action => $jaccess)
		{
			$actions = array();

			foreach ($jaccess->getData() as $group => $allow)
			{
				$actions[$group] = ((bool) $allow);
			}

			$rules[$action] = $actions;
		}

		return $rules;
	}

	/**
	 * Overloaded check function
	 *
	 * @return  true|false
	 *
	 * @since  1.0.0
	 */
	public function check()
	{
		$db = Factory::getDbo();

		// If there is an ordering column and this is a new row then get the next ordering value
		if (property_exists($this, 'ordering') && $this->id == 0)
		{
			$this->ordering = self::getNextOrder();
		}

		$this->alias = trim($this->alias);

		if (empty($this->alias))
		{
			$this->alias = $this->title;
		}

		if ($this->alias)
		{
			if (Factory::getConfig()->get('unicodeslugs') == 1)
			{
				$this->alias = OutputFilter::stringURLUnicodeSlug($this->alias);
			}
			else
			{
				$this->alias = OutputFilter::stringURLSafe($this->alias);
			}
		}

		// Check if category with same alias is present
		$category = Table::getInstance('Category', 'JTable', array('dbo', $db));

		if ($category->load(array('alias' => $this->alias)))
		{
			$msg = Text::_('COM_TJLMS_SAVE_COURSE_WARNING_DUPLICATE_CATALIS');

			while ($category->load(array('alias' => $this->alias)))
			{
				$this->alias = StringHelper::increment($this->alias, 'dash');
			}

			Factory::getApplication()->enqueueMessage($msg, 'warning');
		}

		// Check if course with same alias is present
		$table = Table::getInstance('Course', 'TjlmsTable', array('dbo', $db));

		if ($table->load(array('alias' => $this->alias, 'catid' => $this->catid)) && ($table->id != $this->id || $this->id == 0))
		{
			$msg = Text::_('COM_TJLMS_SAVE_ALIAS_WARNING');

			while ($table->load(array('alias' => $this->alias)))
			{
				$this->alias = StringHelper::increment($this->alias, 'dash');
			}

			Factory::getApplication()->enqueueMessage($msg, 'warning');
		}

		$tjlms_views = array(
					'activities','buy','category','certificate',
					'reports','student_course_report','teacher_report',
					'course','courses','dashboard','enrolment',
					'lesson','orders'
					);

		if (in_array($this->alias, $tjlms_views))
		{
			$this->setError(Text::_('COM_TJLMS_VIEW_WITH_SAME_ALIAS'));

			return false;
		}

		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = Factory::getDate()->format("Y-m-d-H-i-s");
		}

		// Server side validation Disabled the 'expiry' field.
		if ($this->id)
		{
			JLoader::import('components.com_tjcertificate.models.certificates', JPATH_ADMINISTRATOR);
			$tjCertificateModel = BaseDatabaseModel::getInstance('Certificates', 'TjCertificateModel', array('ignore_request' => true));
			$tjCertificateModel->setState('filter.client', 'com_tjlms.course');
			$tjCertificateModel->setState('filter.client_id', $this->id);
			$tjCertificateData = $tjCertificateModel->getTotal();

			if ($tjCertificateData > 0)
			{
				unset($this->expiry);
			}
		}

		return parent::check();
	}

	/**
	 * Method to set the publishing state for a row or list of rows in the database
	 * table.  The method respects checked out rows by other users and will attempt
	 * to checkin rows that it can after adjustments are made.
	 *
	 * @param   mixed    $pks     An optional array of primary key values to update.
	 * @param   integer  $state   The publishing state. eg. [0 = unpublished, 1 = published]
	 * @param   integer  $userId  The user id of the user performing the operation.
	 *
	 * @return    boolean    True on success.
	 *
	 * @since    1.0.4
	 */
	public function publish($pks = null, $state = 1, $userId = 0)
	{
		// Initialise variables.
		$k = $this->_tbl_key;

		// Sanitize input.
		ArrayHelper::toInteger($pks);
		$userId = (int) $userId;
		$state  = (int) $state;

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks))
		{
			if ($this->$k)
			{
				$pks = array(
					$this->$k
				);
			}
			// Nothing to set publishing state on, return false.
			else
			{
				$this->setError(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));

				return false;
			}
		}

		// Build the WHERE clause for the primary keys.
		$where = $k . '=' . implode(' OR ' . $k . '=', $pks);

		// Determine if there is checkin support for the table.
		if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time'))
		{
			$checkin = '';
		}
		else
		{
			$checkin = ' AND (checked_out = 0 OR checked_out = ' . (int) $userId . ')';
		}

		// Update the publishing state for rows with the given primary keys.
		$this->_db->setQuery('UPDATE `' . $this->_tbl . '`' . ' SET `state` = ' . (int) $state . ' WHERE (' . $where . ')' . $checkin);
		$this->_db->execute();

		try
		{
			$this->_db->execute();
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		// If checkin is supported and all rows were adjusted, check them in.
		if ($checkin && (count($pks) == $this->_db->getAffectedRows()))
		{
			// Checkin each row.
			foreach ($pks as $pk)
			{
				$this->checkin($pk);
			}
		}

		// If the JTable instance value is in the list of primary keys that were set, set the instance.
		if (in_array($this->$k, $pks))
		{
			$this->state = $state;
		}

		$this->setError('');

		return true;
	}

	/**
	 * Define a namespaced asset name for inclusion in the #__assets table
	 *
	 * @see JTable::_getAssetName
	 *
	 * @return string The asset name
	 *
	 * @since  1.0.0
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;

		return 'com_tjlms.course.' . (int) $this->$k;
	}

	/**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
	 *
	 * @param   Object  $table  JTable
	 * @param   INT     $id     Id
	 *
	 * @return  The parent asset's id
	 *
	 * @since  1.0.0
	 */
	protected function _getAssetParentId(Table $table = null, $id = null)
	{
		// We will retrieve the parent-asset from the Asset-table
		$assetParent   = Table::getInstance('Asset');

		// Default: if no asset-parent can be found we take the global asset
		$assetParentId = $assetParent->getRootId();

		// The item has the component as asset-parent
		$assetParent->loadByName('com_tjlms');

		// Return the found asset-parent-id
		if ($assetParent->id)
		{
			$assetParentId = $assetParent->id;
		}

		return $assetParentId;
	}

	/**
	 * Function used to delete images
	 *
	 * @param   INT  $pk  Primary key
	 *
	 * @return  int
	 *
	 * @since  1.0.0
	 */
	public function delete($pk = null)
	{
		$this->load($pk);
		$result = parent::delete($pk);

		if ($result)
		{
			jimport('joomla.filesystem.file');
			$result = File::delete(JPATH_ADMINISTRATOR . '/components/com_tjlms/images/' . $this->image);
		}

		return $result;
	}

	/**
	 * Function to get course Image
	 *
	 * @param   STRING  $imageSize  Size of the image
	 *
	 * @return  int
	 *
	 * @since  1.0.0
	 */
	public function getCourseImage($imageSize = 'S_')
	{
		require_once JPATH_ROOT . '/components/com_tjlms/libraries/storage.php';

		$this->Tjstorage = new Tjstorage;

		// Get image to be shown for course
		$tjlmsparams      = ComponentHelper::getParams('com_tjlms');
		$courseImgPath    = $tjlmsparams->get('course_image_upload_path');
		$courseimgRelPath = Uri::root(true) . $tjlmsparams->get('course_image_upload_path');
		$courseDefaultImg = Uri::root(true) . '/media/com_tjlms/images/default/course.png';

		// For course images that are stored in a remote location, we should return the proper path.
		// If not it is stored locally.
		if ($this->image)
		{
			$storage   = $this->Tjstorage->getStorage($this->storage);
			$imageToUse = $storage->getURI($courseImgPath . $imageSize . $this->image);

			if ($this->storage == 'local')
			{
				if (!File::exists(JPATH_SITE . $courseImgPath . $imageSize . $this->image))
				{
					$imageToUse = $courseDefaultImg;
				}
			}
		}
		else
		{
			$imageToUse = $courseDefaultImg;
		}

		return $imageToUse;
	}

	/**
	 * Function to get course Url
	 *
	 * @return  int
	 *
	 * @since  1.0.0
	 */
	public function getCourseUrl()
	{
		$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . $this->id;

		$path = JPATH_COMPONENT . '/helpers/' . 'main.php';

		if (!class_exists('comtjlmsHelper'))
		{
			// Require_once $path;
			JLoader::register('comtjlmsHelper', $path);
			JLoader::load('comtjlmsHelper');
		}

		$comtjlmsHelper = new comtjlmsHelper;

		$courseRoutedUrl = $comtjlmsHelper->tjlmsRoute($courseUrl);

		return $courseRoutedUrl;
	}

	/**
	 * Get the type alias for UCM features
	 *
	 * @return  string  The alias as described above
	 *
	 * @since   4.0.2
	 */
	public function getTypeAlias()
	{
		return $this->typeAlias;
	}
}
