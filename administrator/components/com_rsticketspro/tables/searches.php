<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableSearches extends JTable
{
	public $id = null;
	
	public $user_id = null;
	public $name = null;
	public $params = null;
	public $default = null;
	
	public $published = 1;
	public $ordering = null;
	
	public function check()
	{
		if (!$this->id)
		{
			$db = JFactory::getDbo();
			$this->ordering = $this->getNextOrder($db->qn('user_id') . '=' . $db->q(JFactory::getUser()->id));
		}
		
		return true;
	}
	
	public function bind($src, $ignore = array())
	{
		if (isset($src['params']) && is_array($src['params']))
		{
			$src['params'] = base64_encode(serialize($src['params']));
		}
		return parent::bind($src, $ignore);
	}
	
	public function __construct(& $db)
	{
		parent::__construct('#__rsticketspro_searches', 'id', $db);
	}
	
	public function load($keys = null, $reset = true)
	{
		$loaded = parent::load($keys, $reset);
		
		if ($loaded)
		{
			// base64 encoded & serialized
			if (is_string($this->params))
			{
				$params = @unserialize(base64_decode($this->params));
				if (!$params)
				{
					$params = array();
				}
			}
			else
			{
				$params = array();
			}
			$this->params = $params;
		}
		
		return $loaded;
	}
	
	public function store($updateNulls = false)
	{
		$result = parent::store($updateNulls);

		if ($result)
		{
			if ($this->default)
			{
				$db 	= $this->getDbo();
				$query  = $db->getQuery(true);
				
				// can't have more than 1 default search
				$query->update('#__rsticketspro_searches')
					  ->set($db->qn('default').'='.$db->q(0))
					  ->where($db->qn('user_id').'='.$db->q($this->user_id))
					  ->where($db->qn('id').'!='.$db->q($this->id));
				$db->setQuery($query)->execute();
			}
		}
		
		return $result;
	}
	
	public function reorder($where = '')
	{
		return parent::reorder($this->_db->qn('user_id') . '=' . $this->_db->q(JFactory::getUser()->id));
	}
}