<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelKbarticle extends JModelAdmin
{
	public function getTable($type = 'Kbcontent', $prefix = 'RsticketsproTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.kbarticle', 'kbarticle', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}
		
		$form->setFieldAttribute('tags', 'layout', 'joomla.form.field.list-fancy-select');

		// Modify the form based on access controls.
		if (!$this->canEditState((object) $data))
		{
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('published', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is a record you can edit.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		return $form;
	}
	
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_rsticketspro.edit.kbarticle.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}
	
	public function save($data)
	{
		$files 		= JFactory::getApplication()->input->files->get('jform', null, 'raw');
		$doUpload 	= false;
		
		// before attempting to process any further, let's verify if the upload worked
		if (isset($files['thumb']))
		{
			if ($files['thumb']['tmp_name'] && $files['thumb']['error'] == UPLOAD_ERR_OK)
			{
				// uploaded successfully
				// let's see if the extension is allowed...
				$thumb_ext	= strtolower(JFile::getExt($files['thumb']['name']));
				$allowed	= array('jpg', 'jpeg', 'gif', 'png');
				
				if (!in_array($thumb_ext, $allowed))
				{
					$this->setError(JText::sprintf('RST_IMAGE_UPLOAD_EXTENSION_ERROR', implode(', ', $allowed)));
					return false;
				}
				
				$doUpload = true;
			}
			elseif ($files['thumb']['error'] != UPLOAD_ERR_NO_FILE)
			{
				// error during upload!
				switch ($files['thumb']['error'])
				{
					case UPLOAD_ERR_INI_SIZE:
						$this->setError('The uploaded file exceeds the upload_max_filesize directive in php.ini.');
					break;
					
					case UPLOAD_ERR_FORM_SIZE:
						$this->setError('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
					break;
					
					case UPLOAD_ERR_PARTIAL:
						$this->setError('The uploaded file was only partially uploaded.');
					break;
					
					case UPLOAD_ERR_NO_TMP_DIR:
						$this->setError('Missing a temporary folder.');
					break;
					
					case UPLOAD_ERR_CANT_WRITE:
						$this->setError('Failed to write file to disk.');
					break;
					
					case UPLOAD_ERR_EXTENSION:
						$this->setError('A PHP extension stopped the file upload.');
					break;
				}
				
				return false;
			}
		}
		
		// get the current thumb's name & path
		if (!empty($data['id']))
		{
			$table = $this->getTable();
			$table->load($data['id']);
		}
		
		// remove the current thumb?
		if (!empty($data['delete_thumb']))
		{
			$data['thumb'] = '';
			
			if (!empty($data['id']))
			{
				$table->deleteThumb();
			}
		}
		
		$saved = parent::save($data);
		
		if ($saved)
		{
			$db 		= $this->getDbo();
			$query 		= $db->getQuery(true);
			$article_id	= $this->getState($this->getName().'.id');
			
			// upload the thumb here
			if ($doUpload)
			{
				$thumb_name = md5(uniqid($files['thumb']['name']));
				
				if (!JFile::upload($files['thumb']['tmp_name'], RST_ARTICLE_THUMB_FOLDER.'/'.$thumb_name.'.'.$thumb_ext, false, true))
				{
					$this->setError(JText::sprintf('RST_IMAGE_UPLOAD_ERROR_FOLDER', RST_ARTICLE_THUMB_FOLDER));
					return false;
				}
				
				// remove the old thumbnail before saving a new one
				if (!empty($data['id']))
				{
					$table->deleteThumb();
				}
				
				// update the database entry
				$query	->update('#__rsticketspro_kb_content')
						->set($db->qn('thumb') . '=' . $db->q($thumb_name . '.' . $thumb_ext))
						->where($db->qn('id') . '=' . $db->q($article_id));
				$db->setQuery($query)->execute();
			}
			
			// check if any tags were removed
			$query	->clear();
			$query	->delete($db->qn('#__rsticketspro_kb_content_tags'))
					->where($db->qn('article_id') . '=' . $db->q($article_id) . ' AND' . $db->qn('tag') . ' NOT IN (' . $db->q(implode(',', $data['tags'])) . ')');
			
			$db->setQuery($query);
			$db->execute();
			
			// add tags to tags table
			foreach ($data['tags'] as $tag) {
				$tag = preg_replace('/^#new#/', '', $tag);
				
				// add only the new tags
				$query	->clear();
				$query	->select('*')
						->from($db->qn('#__rsticketspro_kb_content_tags'))
						->where($db->qn('article_id') . ' = ' . $db->q($article_id) . ' AND ' . $db->qn('tag') . ' = ' . $db->q($tag));
				$db->setQuery($query);
				
				if ($db->loadResult() === null) {
					$query	->clear();
					$query	->insert($db->qn('#__rsticketspro_kb_content_tags'))
							->columns(array($db->qn('article_id'), $db->qn('tag')))
							->values(implode(', ', array($db->q($article_id), $db->q($tag))));
					$db->setQuery($query)->execute();
				}
			}
			
		}
		
		return $saved;
	}
	
	public function getTicket()
	{
		$item = $this->getItem();
		if ($item->from_ticket_id)
		{
			$table = JTable::getInstance('Tickets', 'RsticketsproTable');
			if ($table->load($item->from_ticket_id))
			{
				return $table;
			}
			else
			{
				return false;
			}
		}

		return false;
	}
	
	protected function getReorderConditions($table)
	{
		return array(
			'category_id = '.(int) $table->category_id
		);
	}

	protected function canDelete($record)
	{
		return JFactory::getUser()->authorise('kbarticle.delete', 'com_rsticketspro');
	}

	protected function canEditState($record)
	{
		return JFactory::getUser()->authorise('kbarticle.edit.state', 'com_rsticketspro');
	}
}