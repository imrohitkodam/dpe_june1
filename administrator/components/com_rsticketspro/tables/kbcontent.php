<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableKbcontent extends JTable
{
	public $id = null;
	public $name = '';
	public $alias = '';
	public $text = '';
	public $category_id = 0; // 0 - uncategorised
	public $thumb = '';
	public $meta_description = '';
	public $meta_keywords = '';
	public $private = 0;
	public $from_ticket_id = 0;
	public $hits = 0;
	public $created = null;
	public $modified = null;
	public $published = null;
	public $ordering = null;
	
	public function __construct(& $db)
	{
		parent::__construct('#__rsticketspro_kb_content', 'id', $db);
	}

	public function check()
	{
		$db = $this->getDbo();

		if (!$this->id && !$this->ordering)
		{
			$this->ordering = $this->getNextOrder($db->qn('category_id') . ' = ' . $db->q($this->category_id));
		}

		if (!$this->id)
		{
			$this->created = JFactory::getDate()->toSql();
			$this->modified = $db->getNullDate();
		}
		else
		{
			$this->modified = JFactory::getDate()->toSql();
		}
		
		$this->alias = trim($this->alias);

		if (empty($this->alias)) {
			$this->alias = $this->name;
		}

		$this->alias = JFilterOutput::stringURLSafe($this->alias);

		if (trim(str_replace('-', '', $this->alias)) == '') {
			$this->alias = JFactory::getDate()->format('Y-m-d-H-i-s');
		}

		return true;
	}
	
	public function deleteThumb()
	{
		if ($this->id && $this->thumb)
		{
			if (file_exists(RST_ARTICLE_THUMB_FOLDER.'/'.$this->thumb))
			{
				JFile::delete(RST_ARTICLE_THUMB_FOLDER.'/'.$this->thumb);
			}
			
			return true;
		}
		
		return false;
	}
	
	public function deleteTags()
	{
		if ($this->id)
		{
			$db		= JFactory::getDbo();
			$query	= $db->getQuery(true);
			
			$query	->delete($db->qn('#__rsticketspro_kb_content_tags'))
					->where($db->qn('article_id') . '=' . $db->q($this->id));
					
			$db->setQuery($query);
			$db->execute();
			
			return true;
		}
		
		return false;
	}
	
	public function delete($pk = null)
	{
		$deleted = parent::delete($pk);

		if ($deleted)
		{
			$this->deleteThumb();
			$this->deleteTags();
			
			JPluginHelper::importPlugin('finder');
			JFactory::getApplication()->triggerEvent('onFinderAfterDelete', array('com_rsticketspro.article', $this));
		}
		
		return $deleted;
	}
	
	public function store($updateNulls = true)
	{
		// Verify that the alias is unique
        $table = JTable::getInstance('Kbcontent', 'RsticketsproTable');

        if ($table->load(array('alias' => $this->alias, 'category_id' => (int) $this->category_id)) && ($table->id != $this->id || $this->id == 0)) {
            // Is the existing category trashed?
            $this->setError(JText::_('RST_KB_ARTICLE_UNIQUE_ALIAS'));
			
            return false;
        }
		
		JPluginHelper::importPlugin('finder');
		JFactory::getApplication()->triggerEvent('onFinderAfterSave', array('com_rsticketspro.article', $this, true));
		
		return parent::store($updateNulls);
	}
	
	public function publish($pks = null, $value = 1, $userid = 0) 
	{
		JPluginHelper::importPlugin('finder');
		JFactory::getApplication()->triggerEvent('onFinderChangeState', array('com_rsticketspro.article', $pks, $value));
		
		return parent::publish($pks, $value, $userid);
	}
}