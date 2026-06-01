<?php
/**
* @package RSform!Pro
* @copyright (C) www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

class TableRSForm_Rsticketspro extends JTable
{
	public $form_id = null;

	public $rstp_published = 0;
	public $rstp_debug = 0;
	public $rstp_mappings = '';
	public $rstp_trigger_on_payment = 0;

	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	public function __construct(& $db)
	{
		parent::__construct('#__rsform_rsticketspro', 'form_id', $db);
	}

	// Validate data before save
	public function check()
	{
		if (is_array($this->rstp_mappings))
		{
			$this->rstp_mappings = serialize($this->rstp_mappings);
		}

		return true;
	}

	public function load($keys = null, $reset = true)
	{
		$result = parent::load($keys, $reset);

		if ($result)
		{
			$this->rstp_mappings = @unserialize($this->rstp_mappings);

			if ($this->rstp_mappings === false)
			{
				$this->rstp_mappings = array();
			}
		}

		return $result;
	}

	public function hasPrimaryKey()
	{
		$db 	= $this->getDbo();
		$key 	= $this->getKeyName();
		$table	= $this->getTableName();

		$query = $db->getQuery(true)
			->select($db->qn($key))
			->from($db->qn($table))
			->where($db->qn($key) . ' = ' . $db->q($this->{$key}));

		return $db->setQuery($query)->loadResult() !== null;
	}
}