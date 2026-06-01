<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableKbcategories extends JTable
{
	public $id = null;
	
	public $parent_id = 0; // 0 - no parent
	public $thumb = '';
	public $name = null;
	public $alias = null;
	public $description = null;
	public $meta_description = null;
	public $meta_keywords = null;
	public $private = null;
	public $published = 1;
	public $ordering = null;
	
	public function __construct(& $db)
	{
		parent::__construct('#__rsticketspro_kb_categories', 'id', $db);
	}
	
	public function check()
	{
		try
		{
			$db = $this->getDbo();

			if ($this->id)
			{
				// let's see if the current parent is different
				if ($this->parent_id == $this->id)
				{
					throw new Exception(JText::_('RST_KB_CATEGORY_PARENT_SAME_ERROR'));
				}

				// let's see if we are trying to use a child as a parent
				if ($this->parent_id)
				{
					$query 	= $db->getQuery(true);
					$id 	= $this->parent_id;

					// get all the parents of the selected parent and see if they match our own id
					while ($id)
					{
						$query->select($db->qn('parent_id'))
							->from($db->qn('#__rsticketspro_kb_categories'))
							->where($db->qn('id') . '=' . $db->q($id));
						$db->setQuery($query);
						$id = $db->loadResult();
						$query->clear();

						if ($id == $this->id)
						{
							throw new Exception(JText::_('RST_KB_CATEGORY_PARENT_CHILD_ERROR'));
						}
					}
				}
			}
			
			$this->alias = trim($this->alias);

			if (empty($this->alias)) {
				$this->alias = $this->name;
			}

			$this->alias = JFilterOutput::stringURLSafe($this->alias);

			if (trim(str_replace('-', '', $this->alias)) == '') {
				$this->alias = JFactory::getDate()->format('Y-m-d-H-i-s');
			}

			if (!$this->id && !$this->ordering)
			{
				$this->ordering = $this->getNextOrder($db->qn('parent_id') . ' = ' . $db->q($this->parent_id));
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
			return false;
		}
		
		return true;
	}
	
	public function store($updateNulls = true)
	{
		// Verify that the alias is unique
        $table = JTable::getInstance('Kbcategories', 'RsticketsproTable');

        if ($table->load(array('alias' => $this->alias, 'parent_id' => (int) $this->parent_id)) && ($table->id != $this->id || $this->id == 0)) {
            // Is the existing category trashed?
            $this->setError(JText::_('RST_KB_CATEGORY_UNIQUE_ALIAS'));
			
            return false;
        }
		
		return parent::store($updateNulls);
	}
	
	public function deleteThumb()
	{
		if ($this->id && $this->thumb)
		{
			if (file_exists(RST_CATEGORY_THUMB_FOLDER.'/'.$this->thumb))
			{
				JFile::delete(RST_CATEGORY_THUMB_FOLDER.'/'.$this->thumb);
			}
			if (file_exists(RST_CATEGORY_THUMB_FOLDER.'/small/'.$this->thumb))
			{
				JFile::delete(RST_CATEGORY_THUMB_FOLDER.'/small/'.$this->thumb);
			}
			
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
			
			$db 	= $this->getDbo();
			$query 	= $db->getQuery(true);
			
			// all categories that have this category as parent will be moved to "No Parent (Top Category)"
			$query->update('#__rsticketspro_kb_categories')
				  ->set($db->qn('parent_id') . '=' . $db->q(0))
				  ->where($db->qn('parent_id') . '=' . $db->q($pk));
			$db->setQuery($query)->execute();
			
			// all articles that have this category as parent will be moved to "No Parent (Top Category)"
			$query->clear();
			$query->update('#__rsticketspro_kb_content')
				  ->set($db->qn('category_id') . '=' . $db->q(0))
				  ->where($db->qn('category_id') . '=' . $db->q($pk));
			$db->setQuery($query)->execute();
		}
		
		return $deleted;
	}
}