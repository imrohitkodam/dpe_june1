<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2023 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Table\Table;

/**
 * Country form controller class.
 *
 * @package     Tjfields
 * @subpackage  com_tjfields
 * @since       2.2
 */
class TjfieldsControllerCondition extends FormController
{
	/**
	 * The extension for which the countries apply.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $client;

	/**
	 * Constructor.
	 *
	 * @param  array   $config  An optional associative array of configuration settings.
	 *
	 * @since  1.6
	 * @see    JController
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->view_list = 'conditions';

		$this->input = Factory::getApplication()->input;

		if (empty($this->client))
		{
			$this->client = $this->input->get('client', '');
		}
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.6
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$append = parent::getRedirectToItemAppend($recordId);
		$append .= '&client=' . $this->client;

		return $append;
	}

	/**
	 * Gets the URL arguments to append to a list redirect.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.6
	 */
	protected function getRedirectToListAppend()
	{
		$append = parent::getRedirectToListAppend();
		$append .= '&client=' . $this->client;

		return $append;
	}

	/**
	 * Gets the URL arguments to append to a list redirect.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.6
	 */
	public function getFieldsOptions()
	{
		$app = Factory::getApplication();
		$fieldId = $app->input->get('fieldId', 0, 'INT');
		
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
		$fieldTable = Table::getInstance('field', 'TjfieldsTable');
		$fieldTable->load((int) $fieldId);
		$fieldParams = json_decode($fieldTable->params);

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('t.id AS value, t.options AS text');
		$query->from('`#__tjfields_options` AS t');
		$query->where('t.field_id = ' . (int) $fieldId);
		$query->order($db->escape('t.ordering ASC'));
		$db->setQuery($query);

		// Get all countries.
		$fieldOptions = $db->loadObjectList();
		
		if ($fieldParams->other)
		{
			$object = new stdClass();
			$object->value = 'tjlistothervalue';
			$object->text = 'Other';

			array_push($fieldOptions, $object);
		}
		
		if ($fieldTable->type == 'checkbox')
		{
			$object = new stdClass();
			$object->value = 'yes';
			$object->text = 'Yes';

			array_push($fieldOptions, $object);
		}

		echo new JsonResponse($fieldOptions);
		$app->close();
	}
}
