<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelKbcategory extends JModelAdmin
{
	public function getTable($type = 'Kbcategories', $prefix = 'RsticketsproTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.kbcategory', 'kbcategory', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

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
		$data = JFactory::getApplication()->getUserState('com_rsticketspro.edit.kbcategory.data', array());
		
		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}
	
	protected function getReorderConditions($table)
	{
		return array(
			'parent_id = '.(int) $table->parent_id
		);
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
				$ext = strtolower(JFile::getExt($files['thumb']['name']));
				$allowed = array('jpg', 'jpeg', 'gif', 'png');
				if (!in_array($ext, $allowed))
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
		
		// upload the thumb here
		if ($saved && $doUpload)
		{
			$name = md5(uniqid($files['thumb']['name']));
			$id	  = $this->getState($this->getName().'.id');
			
			if (!JFile::upload($files['thumb']['tmp_name'], RST_CATEGORY_THUMB_FOLDER.'/'.$name.'.'.$ext, false, true))
			{
				$this->setError(JText::sprintf('RST_IMAGE_UPLOAD_ERROR_FOLDER', RST_CATEGORY_THUMB_FOLDER));
				return false;
			}
			
			// remove the old thumbnail before saving a new one
			if (!empty($data['id']))
			{
				$table->deleteThumb();
			}
			
			// build thumbnail
			if (function_exists('imagecreatefromstring') && function_exists('imagescale'))
			{
				$file		= RST_CATEGORY_THUMB_FOLDER . '/' . $name . '.' . $ext;
				$newWidth  	= 64;
				$quality   	= 90;
				$original  	= @imagecreatefromstring(file_get_contents($file));

				if ($original)
				{
					// If we're downsizing, IMG_BICUBIC produces better results
					if ($newWidth < imagesx($original))
					{
						$image = imagescale($original, $newWidth, -1, IMG_BICUBIC);
					}
					else
					{
						$image = imagescale($original, $newWidth);
					}

					if ($image)
					{
						$thumbPath = RST_CATEGORY_THUMB_FOLDER . '/small/' . $name . '.' . $ext;

						switch ($ext)
						{
							case 'png':
								$x = imagesx($image);
								$y = imagesy($image);
								$width = imagesx($original);
								$height = imagesy($original);
								$transparentImage = imagecreatetruecolor($x, $y);
								imagealphablending($transparentImage, false);
								imagesavealpha($transparentImage, true);
								$transparent = imagecolorallocatealpha($transparentImage, 255, 255, 255, 127);
								imagefilledrectangle($transparentImage, 0, 0, $width, $height, $transparent);
								imagecopyresampled($transparentImage, $original, 0, 0, 0, 0, $x, $y, $width, $height);
								imagepng($transparentImage, $thumbPath);
								break;

							case 'jpeg':
							case 'jpg':
								imagejpeg($image, $thumbPath, $quality);
								break;

							case 'gif':
								$transparency = imagecolortransparent($original);
								if ($transparency > -1)
								{
									$x = imagesx($image);
									$y = imagesy($image);
									$width = imagesx($original);
									$height = imagesy($original);
									$transparentImage = imagecreatetruecolor($x, $y);
									$transparentColor = imagecolorsforindex($original, 127);
									$transparency = imagecolorallocate($transparentImage, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
									imagefill($transparentImage, 0, 0, $transparency);
									imagecolortransparent($transparentImage, $transparency);
									imagecopyresampled($transparentImage, $original, 0, 0, 0, 0, $x, $y, $width, $height);
									imagegif($transparentImage, $thumbPath);
								}
								else
								{
									imagegif($image, $thumbPath);
								}
								break;
						}

						// update the database entry
						$db 	= $this->getDbo();
						$query 	= $db->getQuery(true);
						$query->update('#__rsticketspro_kb_categories')
							->set($db->qn('thumb') . '=' . $db->q($name . '.' . $ext))
							->where($db->qn('id') . '=' . $db->q($id));
						$db->setQuery($query)->execute();
					}

					unset($image, $original);
				}
			}
		}
		
		return $saved;
	}

	protected function canDelete($record)
	{
		return JFactory::getUser()->authorise('kbcategory.delete', 'com_rsticketspro');
	}

	protected function canEditState($record)
	{
		return JFactory::getUser()->authorise('kbcategory.edit.state', 'com_rsticketspro');
	}
}