<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableKbrules extends JTable
{
	public $id = null;
	
	public $name = '';
	public $category_id = 0;
	public $conditions = '';
	public $publish_article = 1;
	public $private = 0;
	
	public $published = 1;
	
	public function __construct(& $db)
	{
		parent::__construct('#__rsticketspro_kb_rules', 'id', $db);
	}
	
	public function bind($src, $ignore = array())
	{
		if (!isset($src['conditions']))
		{
			$src['conditions'] = array();
			if (isset($src['select_type']))
			{
				$j = 0;
				foreach ($src['select_type'] as $i => $type)
				{
					$condition = (object) array(
						'type' => $type,
						'condition' => isset($src['select_condition'][$i]) ? $src['select_condition'][$i] : '',
						'custom_field' => $type == 'custom_field' && isset($src['select_custom_field_value'][$j]) ? $src['select_custom_field_value'][$j] : '',
						'value' => isset($src['select_value'][$i]) ? $src['select_value'][$i] : '',
						'connector' => isset($src['select_connector'][$i]) ? $src['select_connector'][$i] : ''
					);
					
					$src['conditions'][] = $condition;
					if ($type == 'custom_field')
					{
						$j++;
					}
				}
			}
			
			$src['conditions'] = serialize($src['conditions']);
		}
		
		return parent::bind($src, $ignore);
	}

	public function check()
	{
		try
		{
			if (is_string($this->conditions))
			{
				$conditions = unserialize($this->conditions);

				if (empty($conditions))
				{
					throw new Exception(JText::_('RST_KB_RULE_NO_CONDITION_ERROR'));
				}

				foreach ($conditions as $condition)
				{
					if (empty($condition->type))
					{
						throw new Exception(JText::_('RST_KB_RULE_SELECT_TYPE_ERROR'));
					}

					if (empty($condition->condition))
					{
						throw new Exception(JText::_('RST_KB_RULE_SELECT_CONDITION_ERROR'));
					}

					if (empty($condition->value))
					{
						throw new Exception(JText::_('RST_KB_RULE_SELECT_VALUE_ERROR'));
					}

					if ($condition->type === 'custom_field' && empty($condition->custom_field))
					{
						throw new Exception(JText::_('RST_KB_RULE_SELECT_CUSTOM_FIELD_ERROR'));
					}
				}
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
			return false;
		}

		return true;
	}
}