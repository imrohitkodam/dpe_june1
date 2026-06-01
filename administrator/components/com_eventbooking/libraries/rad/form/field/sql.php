<?php
/**
 * Supports an custom SQL select list
 *
 * @package     Joomla.RAD
 * @subpackage  Form
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

class RADFormFieldSQL extends RADFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 */
	protected $type = 'SQL';

	/**
	 * The query.
	 *
	 * @var    string
	 */
	protected $query;

	/**
	 * Method to instantiate the form field object.
	 *
	 * @param   \Joomla\CMS\Table\Table  $row    the table object store form field definitions
	 * @param   mixed                    $value  the initial value of the form field
	 */
	public function __construct($row, $value)
	{
		parent::__construct($row, $value);

		$this->query = $row->default_values;
	}

	/**
	 * Method to get the custom field options.
	 * Use the query attribute to supply a query to generate the list.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		try
		{
			/* @var \Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('db');

			foreach ($this->replaceData as $key => $value)
			{
				$this->query = str_replace('[' . strtoupper($key) . ']', $value, $this->query);
			}

			$this->query = str_replace('[EVENT_ID]', (int) $this->eventId, $this->query);

			// A bit hacky to support [USER_ID] tag for SQL field
			$app  = Factory::getApplication();
			$view = $app->getInput()->getCmd('view');
			$id   = $app->getInput()->getInt('id', 0);

			if ($view === 'register')
			{
				$userId = $app->getIdentity()->id;
			}
			elseif ($view === 'registrant' && $id > 0)
			{
				$query = $db->getQuery(true)
					->select('user_id')
					->from('#__eb_registrants')
					->where('id = ' . $id);
				$db->setQuery($query);
				$userId = (int) $db->loadResult();
			}
			else
			{
				$userId = 0;
			}

			$this->query = str_replace('[USER_ID]', $userId, $this->query);
			$db->setQuery($this->query);

			$options   = [];
			$options[] = HTMLHelper::_('select.option', '', $this->row->prompt_text ?: Text::_('EB_SELECT'));
			$options   = array_merge($options, $db->loadObjectList());
		}
		catch (Exception $e)
		{
			$options = [];
		}

		return $options;
	}

	/**
	 * Override getDisplayValue to display the text instead of the value returned by SQL command
	 *
	 * @return ?string
	 */
	public function getDisplayValue()
	{
		$options = $this->getOptions();

		// If there is no data returned from the SQL command, just return the field value
		if (!count($options))
		{
			return $this->value;
		}

		// Find the option with value matchs the value of the field and return it
		foreach ($options as $option)
		{
			if ($option->value == $this->value)
			{
				return $option->text;
			}
		}

		// Default, return field value
		return $this->value;
	}
}
