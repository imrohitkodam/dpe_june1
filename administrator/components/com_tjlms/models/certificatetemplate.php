<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

jimport('joomla.application.component.modeladmin');

/**
 * Certificates model.
 *
 * @since       1.6
 * @deprecated  1.3.32 Use TJCertificate template model instead
 */
class TjlmsModelCertificatetemplate extends AdminModel
{
	/**
	 * @var      string    The prefix to use with controller messages.
	 * @since    1.6
	 */
	protected $text_prefix = 'COM_TJLMS';

	/**
	 * @var   	string  	Alias to manage history control
	 * @since   3.2
	 */
	public $typeAlias = 'com_tjlms.certificatetemplate';

	/**
	 * @var null  Item data
	 * @since  1.6
	 */
	protected $item = null;

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return    JTable    A database object
	 *
	 * @since    1.6
	 */
	public function getTable($type = 'Certificatetemplate', $prefix = 'TjlmsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm(
			'com_tjlms.certificatetemplate', 'certificatetemplate',
			array('control' => 'jform',
				'load_data' => $loadData
			)
		);

		if (empty($form))
		{
			return false;
		}

		$templateId = $form->getValue('id');

		if (empty($templateId))
		{
			$templatePath = JPATH_SITE . '/administrator/components/com_tjlms/certificate_default.php';

			if (file_exists($templatePath))
			{
				include_once $templatePath;
				$form->setValue('body', null, $certificate['message_body']);
			}
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return   mixed  The data for the form.
	 *
	 * @since    1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_tjlms.edit.certificatetemplate.data', array());

		if (empty($data))
		{
			if ($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since    1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Do any procesing on fields here if needed
		}

		return $item;
	}

	/**
	 * Method to duplicate an Certificatetemplate
	 *
	 * @param   array  &$pks  An array of primary key IDs.
	 *
	 * @return  boolean  True if successful.
	 *
	 * @throws  Exception
	 */
	public function duplicate(&$pks)
	{
		$user = Factory::getUser();

		// Access checks.
		if (!$user->authorise('core.create', 'com_tjlms'))
		{
			throw new Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
		}

		$context    = $this->option . '.' . $this->name;

		// Include the plugins for the save events.
		PluginHelper::importPlugin($this->events_map['save']);

		$table = $this->getTable();

		foreach ($pks as $pk)
		{
			if ($table->load($pk, true))
			{
				// Reset the id to create a new record.
				$table->id = 0;

				if (!$table->check())
				{
					throw new Exception($table->getError());
				}

				// Trigger the before save event.
				$result = Factory::getApplication()->triggerEvent($this->event_before_save, array($context, &$table, true));

				if (in_array(false, $result, true) || !$table->store())
				{
					throw new Exception($table->getError());
				}

				// Trigger the after save event.
				Factory::getApplication()->triggerEvent($this->event_after_save, array($context, &$table, true));
			}
			else
			{
				throw new Exception($table->getError());
			}
		}

		// Clean cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   JTable  $table  Table Object
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '')
			{
				$query = $this->_db->getQuery(true);
				$query->select('MAX(ordering)');
				$query->from($this->_db->qn('#__tjlms_certificate_template'));
				$this->_db->setQuery($query);
				$max             = $this->_db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * get count of courses use this certificate.
	 *
	 * @param   INT  $certID      certID
	 *
	 * @param   INT  $created_by  created_by
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public function checkCertificateIsUsed($certID, $created_by)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select('COUNT(c.id)');
			$query->from($this->_db->qn('#__tjlms_certificate_template', 'ct'));
			$query->join('LEFT', $this->_db->qn('#__tjlms_courses', 'c') . ' ON(' . $this->_db->qn('ct.id') . '=' . $this->_db->qn('c.certificate_id') . ')');
			$query->where($this->_db->qn('c.certificate_id') . '=' . (int) $certID);
			$query->where($this->_db->qn('c.created_by') . '!=' . (int) $created_by);
			$this->_db->setQuery($query);
			$result = $this->_db->loadResult();

			return $result;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Method to validate the Certificate data
	 *
	 * @param   array  $form  The jform data.
	 * 
	 * @param   array  $data  The data to validate.
	 *
	 * @return  boolean true if valid, false otherwise.
	 *
	 * @since   1.3.15
	 */
	public function validate($form,$data)
	{
		if (strlen(trim($data['title'])) == 0)
		{
			$this->setError(Text::_("COM_TJLMS_FORM_LBL_CERTIFICATETEMPLATE_TITLE_BLANK"));

			return false;
		}

		return parent::validate($form, $data);
	}
}
