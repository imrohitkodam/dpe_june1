<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
jimport('joomla.html.parameter');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

/**
 * tjlmsmediaHelper
 *
 * @since  1.0.0
 */
class TjlmsmediaHelper
{
	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 */
	public function __construct()
	{
		$params = ComponentHelper::getParams('com_tjlms');
		$this->tjlms_config['image_size'] = $params->get('max_size');

		if (!$this->tjlms_config['image_size'])
		{
			$this->tjlms_config['image_size'] = 9024;
		}
	}

	/*function geturl($video_provider,$url)
	{
		switch($video_provider)
		{
			case 'youtube':
				require_once(JPATH_SITE.DS."components".DS."com_jgive".DS."helpers".DS."video".DS."youtube.php");
				$helperVideoYoutube=new helperVideoYoutube();
				return $result=$helperVideoYoutube->getlink($url);
			break;

			case 'vimeo':
				require_once(JPATH_SITE.DS."components".DS."com_jgive".DS."helpers".DS."video".DS."vimeo.php");
				$helperVideoVimeo=new helperVideoVimeo();
				return $result=$helperVideoVimeo->getlink($url);
			break;
		}
	}*/

	/**
	 * check for max media size allowed for upload
	 * image upload
	 *
	 * @param   STRING  $image_for  image_for
	 * @param   INT     $lesson_id  lesson_id
	 *
	 * @return  mixed  String on success, false on failure.
	 *
	 * @since    1.0.0
	 */
	public function imageupload( $image_for='', $lesson_id='' )
	{
		$upload_orig = '1';
		$img_dimensions_config = array();
		$img_dimensions_config[] = 'small';
		$img_dimensions_config[] = 'medium';
		$img_dimensions_config[] = 'large';
		$app = Factory::getApplication();

		// Get uploaded media details
		$params = ComponentHelper::getParams('com_tjlms');

		// Orginal file name
		$file_name  = $_FILES['jform']['name']['image'];

		// Convert name to lowercase
		$file_name  = strtolower($_FILES['jform']['name']['image']);

		// Replace "spaces" with "_" in filename
		$file_name  = preg_replace('/\s/', '_', $file_name);

		// Removes special chars.
		$file_name = preg_replace('/[^A-Za-z0-9\_\.]/', '', $file_name);

		// Replaces multiple "_" with single one.
		$file_name = preg_replace('/_+/', '_', $file_name);

		// Replace "spaces" with "_" in filename
		$file_name  = preg_replace('/\s/', '_', $file_name);
		$file_type  = $_FILES['jform']['type']['image'];
		$file_tmp_name = $_FILES['jform']['tmp_name']['image'];
		$file_size    = $_FILES['jform']['size']['image'];
		$file_error   = $_FILES['jform']['error']['image'];

		// Set error flag, if any error occurs set this to 1
		$error_flag = 0;

		// Check for max media size allowed for upload
		$max_size_exceed = $this->check_max_size($file_size);

		if ($max_size_exceed)
		{
			$max_size = $params->get('max_size');

			if (!$max_size)
			{
				// KB
				$max_size = 1024;
			}

			$errorList[] = Text::_('FILE_BIG') . " " . $max_size . "KB<br>";
			$app->enqueueMessage(Text::_('COM_TJLMS_MAX_FILE_SIZE') . ' ' . $max_size . 'KB<br>', 'error');
			$error_flag = 1;
		}

		if (!$error_flag)
		{
			/*detect file type & detect media group type image/video/flash*/
			$media_type_group = $this->check_media_type_group($file_type);

			if (!$media_type_group['allowed'])
			{
				$errorList[] = Text::_('FILE_ALLOW');

				$error_flag = 1;
			}

			if (!$error_flag)
			{
				$media_extension = $this->get_media_extension($file_name);

				// Upload original img

				// $file_name_without_extension=$this->get_media_file_name_without_extension($file_name);

				$timestamp = time();

				$original_file_name = $original_file_name_with_extension = $timestamp . '_' . $file_name;

				// $original_file_name=$original_file_name_without_extension.'.'.$media_extension;

				// Always use constants when making file paths, to avoid the possibilty of remote file inclusion
				if ($image_for == 'course')
				{
					$store_path	= $params->get('course_image_upload_path');
				}
				elseif ($image_for == 'lesson')
				{
					$store_path	= $params->get('lesson_image_upload_path');
				}
				elseif ($image_for == 'module')
				{
					$store_path	= $params->get('module_image_upload_path', 'media/com_tjlms/images/modules/');
				}

				$fullPath = JPATH_SITE . '/' . $store_path;
				$relPath = $store_path;

				// If folder is not present create it
				if (!Folder::exists($fullPath))
				{
					@mkdir($fullPath);
				}

				// Determine if resizing is needed for images
				foreach ($img_dimensions_config as $config)
				{
					$media_dimnesions = new stdClass;

					// If component optins saved the get the image dimentions
					if ($params->get($config . '_width'))
					{
						$media_dimnesions->img_width = $params->get($config . '_width');
					}
					else
					{
						// If there is no value exist then get default value
						switch ($config . '_width')
						{
							case 'small_width':
							$media_dimnesions->img_width = 64;
							break;

							case 'medium_width':
							$media_dimnesions->img_width = 120;
							break;

							case 'large_width':
							$media_dimnesions->img_width = 400;
							break;

							default:
								$media_dimnesions->img_width = 400;
							break;
						}
					}

					if ($params->get($config . '_height'))
					{
						$media_dimnesions->img_height = $params->get($config . '_height');
					}
					else
					{
						switch ($config . '_height')
						{
							case 'small_height':
								$media_dimnesions->img_height = 64;
							break;

							case 'medium_height':
								$media_dimnesions->img_height = 120;
							break;

							case 'large_height':
								$media_dimnesions->img_height = 400;
							break;

							default:
								$media_dimnesions->img_height = 400;
							break;
						}
					}

					// $media_dimnesions->img_height=$params->get( $config.'_height' );
					$max_zone_width  = $media_dimnesions->img_width;
					$max_zone_height = $media_dimnesions->img_height;

					switch ($config)
					{
						case 'small':
						$file_name_with_extension_size = "S_" . $original_file_name_with_extension;
						break;
						case 'medium':
						$file_name_with_extension_size = "M_" . $original_file_name_with_extension;
						break;
						case 'large':
						$file_name_with_extension_size = "L_" . $original_file_name_with_extension;
						break;
						default:
						$file_name_with_extension_size = $original_file_name_with_extension;
						break;
					}

					/*if($media_type_group['media_type_group']!="video" )// skip resizing for video*/
					if ($media_type_group['media_type_group'] == "image" )
					{
						// Get uploaded image dimensions
						$media_size_info = $this->check_media_resizing_needed($media_dimnesions, $file_tmp_name);

						$resizing = 0;

						if ($media_size_info['resize'])
						{
							$resizing = 1;
						}

						switch ($resizing)
						{
							case 0:
									$new_media_width = $media_size_info['width_img'];
									$new_media_height = $media_size_info['height_img'];

									// @TODO not sure abt this
									$top_offset = 0;

									// @TODO not sure abt this
									$blank_height = $new_media_height;
								break;
							case 1:
									$new_dimensions = $this->get_new_dimensions($max_zone_width, $max_zone_height, 'auto');
									$new_media_width = $new_dimensions['new_calculated_width'];
									$new_media_height = $new_dimensions['new_calculated_height'];
									$top_offset = $new_dimensions['top_offset'];
									$blank_height = $new_dimensions['blank_height'];
								break;
						}
					}
					else
					{
						// As we skipped resizing for video , we will use zone dimensions
						$new_media_width = $media_dimnesions->img_width;
						$new_media_height = $media_dimnesions->img_height;

						// @TODO not sure abt this
						$top_offset = 0;
						$blank_height = $new_media_height;
					}

					$colorR = 255;
					$colorG = 255;
					$colorB = 255;

					$upload_image = $this->uploadImage(
										$max_zone_width, $max_zone_height, $fullPath,
										$relPath, $colorR, $colorG, $colorB, $new_media_width,
										$new_media_height, $blank_height, $top_offset,
										$media_extension, $file_name_with_extension_size
										);
				}

				if ($upload_orig == '1')
				{
					$upload_path = $fullPath . $original_file_name;

					if (!File::upload($file_tmp_name, $upload_path))
					{
						$app->enqueueMessage(Text::_('COM_TJLMS_ERROR_MOVING_FILE'), 'error');
						echo Text::_('COM_TJLMS_ERROR_MOVING_FILE');

						return false;
					}
				}

				return $original_file_name;
			}
		}

		return false;
	}

	/**
	 * check for max media size allowed for upload
	 * detect media group type image/video/flash
	 *
	 * @param   ARRAY  $file_size  File size
	 *
	 * @return  course ID
	 *
	 * @since    1.0.0
	 */
	public function check_max_size($file_size)
	{
		// @TODO needed?
		$this->media_size = $file_size;
		$max_media_size = $this->tjlms_config['image_size'] * 1024;

		if ($file_size > $max_media_size)
		{
			return 1;
		}

		return 0;
	}

	/**
	 * detect file type
	 * detect media group type image/video/flash
	 *
	 * @param   ARRAY  $file_type  File name
	 *
	 * @return  course ID
	 *
	 * @since    1.0.0
	 */
	public function check_media_type_group($file_type)
	{
		$allowed_media_types = array(
			'image' => array
				(
				// Images
				'image/png',
				'image/jpeg',
				'image/pjpeg',
				'image/jpeg',
				'image/pjpeg',
				'image/jpeg',
				'image/pjpeg'
				)
		);

		$media_type_group = '';
		$flag = 0;

		foreach ($allowed_media_types as $key => $value)
		{
			if (in_array($file_type, $value))
			{
				$media_type_group = $key;
				$flag = 1;
				break;
			}
		}

		$this->media_type = $file_type;
		$this->media_type_group = $media_type_group;

		$return['media_type'] = $file_type;
		$return['media_type_group'] = $media_type_group;

		if (!$flag)
		{
			// File type not allowed
			$return['allowed'] = 0;

			return $return;
		}

		$return['allowed'] = 1;

		// Allowed file type
		return $return;
	}

	/**
	 * get_media_extension
	 *
	 * @param   ARRAY  $file_name  File name
	 *
	 * @return  course ID
	 *
	 * @since    1.0.0
	 */
	public function get_media_extension($file_name)
	{
		$media_extension = pathinfo($file_name);
		$this->media_extension = $media_extension['extension'];

		return $media_extension['extension'];
	}

	/**
	 * get_media_file_name_without_extension
	 *
	 * @param   ARRAY  $file_name  File name
	 *
	 * @return  course ID
	 *
	 * @since    1.0.0
	 */
	public function get_media_file_name_without_extension($file_name)
	{
		$media_extension = pathinfo($file_name);

		return $media_extension['filename'];
	}

	/**
	 * Method to  check_media_resizing_needed
	 *
	 * @param   ARRAY  $media_dimnesions  course data
	 * @param   ARRAY  $file_tmp_name     course data
	 *
	 * @return  course ID
	 *
	 * @since    1.0.0
	 */
	public function check_media_resizing_needed($media_dimnesions,$file_tmp_name)
	{
		// Get uploaded image height and width

		// This will work for all images + swf files

		list($width_img,$height_img) = getimagesize($file_tmp_name);
		$return['width_img'] = $width_img;
		$return['height_img'] = $height_img;
		$this->width = $width_img;
		$this->height = $height_img;

		if ($width_img == $media_dimnesions->img_width && $height_img == $media_dimnesions->img_height)
		{
			$return['resize'] = 0;

			// No resizing needed
			return $return;
		}

		// Resizing needed
		$return['resize'] = 1;

		return $return;
	}

	/**
	 * Method to  get_new_dimensions
	 *
	 * @param   ARRAY  $max_zone_width   course data
	 * @param   ARRAY  $max_zone_height  course data
	 * @param   ARRAY  $option           course data
	 *
	 * @return  course ID
	 *
	 * @since    1.0.0
	 */
	public function get_new_dimensions($max_zone_width, $max_zone_height, $option)
	{
		switch ($option)
		{
			case 'exact':
				$new_calculated_width = $max_zone_width;
				$new_calculated_height = $max_zone_height;
				break;
			case 'auto':
				$new_dimensions = $this->get_optimal_dimensions($max_zone_width, $max_zone_height);
				$new_calculated_width = $new_dimensions['new_calculated_width'];
				$new_calculated_height = $new_dimensions['new_calculated_height'];
				break;
		}

		$new_dimensions['new_calculated_width'] = $new_calculated_width;
		$new_dimensions['new_calculated_height'] = $new_calculated_height;

		return $new_dimensions;
	}

	/*function uploadImage('jform', $maxSize, $max_zone_width, $fullPath, $relPath, $colorR, $colorG, $colorB, $max_zone_height = null){*/

	/**
	 * Method to  uploadImage
	 *
	 * @param   ARRAY  $max_zone_width                 course data
	 * @param   ARRAY  $max_zone_height                course data
	 * @param   ARRAY  $fullPath                       course data
	 * @param   ARRAY  $relPath                        course data
	 * @param   ARRAY  $colorR                         course data
	 * @param   ARRAY  $colorG                         course data
	 * @param   ARRAY  $colorB                         course data
	 * @param   ARRAY  $new_media_width                course data
	 * @param   ARRAY  $new_media_height               course data
	 * @param   ARRAY  $blank_height                   course data
	 * @param   ARRAY  $top_offset                     course data
	 * @param   ARRAY  $media_extension                course data
	 * @param   ARRAY  $file_name_with_extension_size  course data
	 *
	 * @return  course ID
	 *
	 * @since    1.0.0
	 */
	public function uploadImage(
		$max_zone_width,$max_zone_height,
		$fullPath, $relPath, $colorR, $colorG, $colorB,$new_media_width,
		$new_media_height,$blank_height,$top_offset,$media_extension,
		$file_name_with_extension_size)
	{
		switch ($this->media_type_group)
		{
			case "flash":
				jimport('joomla.filesystem.file');

				// Retrieve file details from uploaded file, sent from upload form
				$file = $_FILES['jform'];

				// Clean up filename to get rid of strange characters like spaces etc
				$filename = File::makeSafe($file['name']['image']);

				// Set up the source and destination of the file
				$src = $file['tmp_name']['image'];

				$filename = strtolower($filename);
				$filename = preg_replace('/\s/', '_', $filename);
				$timestamp = time();

				// $file_name_without_extension=$this->get_media_file_name_without_extension($filename);
				$filename = $file_name_with_extension_size;
				$dest = $fullPath . "swf" . DS . $filename;

				// First check if the file has the right extension, we need swf only
				if (File::upload($src, $dest))
				{
					$dest = $fullPath . "swf" . DS . $filename;

					return $dest;
				}

			break;

			case "video":
				jimport('joomla.filesystem.file');

				// Retrieve file details from uploaded file, sent from upload form
				$file = $_FILES['jform'];

				// Clean up filename to get rid of strange characters like spaces etc
				$filename = File::makeSafe($file['name']['image']);

				// Set up the source and destination of the file
				$src = $file['tmp_name']['image'];

				$filename = strtolower($filename);
				$filename = preg_replace('/\s/', '_', $filename);
				$timestamp = time();

				// $file_name_without_extension=$this->get_media_file_name_without_extension($filename);
				$filename = $timestamp . "_" . $file_name_with_extension_size;

				$dest = $fullPath . "vids" . DS . $filename;

				if (File::upload($src, $dest))
				{
					$dest = $fullPath . "vids" . DS . $filename;

					return $dest;
				}

			break;
		}

		$errorList = array();

		// $folder = $relPath;
		// ADDED BY @VIDYASAGAR
		$folder = $fullPath;
		$match = "";
		$filesize = $_FILES['jform']['size']['image'];

		if ($filesize > 0)
		{
			$filename = strtolower($_FILES['jform']['name']['image']);
			$filename = preg_replace('/\s/', '_', $filename);

			if ($filesize < 1)
			{
				$errorList[] = Text::_('FILE_EMPTY');
			}

			if (count($errorList) < 1)
			{
				// File is allowed
				$match = "1";
				$NUM = time();

				// $front_name = $file_name_with_extension_size;
				// $newfilename = $front_name.".".$media_extension;

				$newfilename = $file_name_with_extension_size;
				$save = $folder . $newfilename;

				if (!file_exists($save))
				{
					list($this->width, $this->height) = getimagesize($_FILES['jform']['tmp_name']['image']);
					$image_p = imagecreatetruecolor($new_media_width, $blank_height);
					$white = imagecolorallocate($image_p, $colorR, $colorG, $colorB);

					// START added to preserve transparency

					imagealphablending($image_p, false);
					imagesavealpha($image_p, true);
					$transparent = imagecolorallocatealpha($image_p, 255, 255, 255, 127);
					imagefill($image_p, 0, 0, $transparent);

					// END added to preserve transparency

					switch ($media_extension)
					{
						/*case "gif":
							$gr = new qtc_gifresizer;//New Instance Of GIFResizer
							 echo
							$gr->temp_dir = $folder.'frames'; //Used for extracting GIF Animation Frames
							if folder is not present create it
							if(!Folder::exists($gr->temp_dir)){
								@mkdir($gr->temp_dir);
							}
							$gr->resize("gifs/1.gif","resized/1_resized.gif",50,50); //Resizing the animation into a new file.
							$gr->resize($_FILES['jform']['tmp_name']['img'],$save,$new_media_width,$new_media_height); Resizing the animation into a new file.
						break;*/

						case "jpg":
							$image = @imagecreatefromjpeg($_FILES['jform']['tmp_name']['image']);

							if (!@imagecopyresampled($image_p, $image, 0, $top_offset, 0, 0, $new_media_width, $new_media_height, $this->width, $this->height))
							{
								$errorList[] = Text::_('FILE_JPG');
							}
						break;

						case "jpeg":
							$image = @imagecreatefromjpeg($_FILES['jform']['tmp_name']['image']);

							if (!@imagecopyresampled($image_p, $image, 0, $top_offset, 0, 0, $new_media_width, $new_media_height, $this->width, $this->height))
							{
								$errorList[] = Text::_('FILE_JPEG');
							}
						break;

						case "png":
							$image = @imagecreatefrompng($_FILES['jform']['tmp_name']['image']);

							if (!@imagecopyresampled($image_p, $image, 0, $top_offset, 0, 0, $new_media_width, $new_media_height, $this->width, $this->height))
							{
								$errorList[] = Text::_('FILE_PNG');
							}
						break;
					}

					switch ($media_extension)
					{
						/*
						case "gif":
							if(!@imagegif($image_p, $save)){
								$errorList[]= Text::_('FILE_GIF');
							}

						break;
						*/
						case "jpg":
							if (!@imagejpeg($image_p, $save, 100))
							{
								$errorList[] = Text::_('FILE_JPG');
							}
						break;

						case "jpeg":

							if (!@imagejpeg($image_p, $save, 100))
							{
								$errorList[] = Text::_('FILE_JPEG');
							}

						break;
						case "png":
							if (!@imagepng($image_p, $save, 0))
							{
								$errorList[] = Text::_('FILE_PNG');
							}

						break;
					}

					@imagedestroy($image_p);
				}
				else
				{
					$errorList[] = Text::_('FILE_EXIST');
				}
			}
		}
		else
		{
			$errorList[] = Text::_('FILE_NO');
		}

		if (!$match)
		{
			$errorList[] = Text::_('FILE_ALLOW') . ":" . $filename;
		}

		if (sizeof($errorList) == 0)
		{
			return $fullPath . $newfilename;
		}
		else
		{
			$eMessage = array();

			for ($x = 0; $x < sizeof($errorList); $x++)
			{
				$eMessage[] = $errorList[$x];
			}

			return $eMessage;
		}
	}

	/**
	 * Method to  get_optimal_dimensions
	 *
	 * @param   ARRAY  $max_zone_width   course data
	 * @param   ARRAY  $max_zone_height  course data
	 *
	 * @return  course ID
	 *
	 * @since    1.0.0
	 */
	public function get_optimal_dimensions($max_zone_width, $max_zone_height)
	{
		// @TODO not sure abt this
		$top_offset = 0;

		if ($max_zone_height == null)
		{
			if ($this->width < $max_zone_width)
			{
				$new_calculated_width = $this->width;
			}
			else
			{
				$new_calculated_width = $max_zone_width;
			}

			$ratio_orig = $this->width / $this->height;
			$new_calculated_height = $new_calculated_width / $ratio_orig;

			$blank_height = $new_calculated_height;
			$top_offset = 0;
		}
		else
		{
			if ($this->width <= $max_zone_width && $this->height <= $max_zone_height)
			{
				$new_calculated_height = $this->height;
				$new_calculated_width = $this->width;
			}
			else
			{
				if ($this->width > $max_zone_width)
				{
					$ratio = ($this->width / $max_zone_width);
					$new_calculated_width = $max_zone_width;
					$new_calculated_height = ($this->height / $ratio);

					if ($new_calculated_height > $max_zone_height)
					{
						$ratio = ($new_calculated_height / $max_zone_height);
						$new_calculated_height = $max_zone_height;
						$new_calculated_width = ($new_calculated_width / $ratio);
					}
				}

				if ($this->height > $max_zone_height)
				{
					$ratio = ($this->height / $max_zone_height);
					$new_calculated_height = $max_zone_height;
					$new_calculated_width = ($this->width / $ratio);

					if ($new_calculated_width > $max_zone_width)
					{
						$ratio = ($new_calculated_width / $max_zone_width);
						$new_calculated_width = $max_zone_width;
						$new_calculated_height = ($new_calculated_height / $ratio);
					}
				}
			}

			if ($new_calculated_height == 0 || $new_calculated_width == 0 || $this->height == 0 || $this->width == 0)
			{
				/*die(JText::_('FILE_VALID'));*/
			}

			if ($new_calculated_height < 45)
			{
				$blank_height = 45;
				$top_offset = round(($blank_height - $new_calculated_height) / 2);
			}
			else
			{
				$blank_height = $new_calculated_height;
			}
		}

		$new_dimensions['new_calculated_width'] = $new_calculated_width;
		$new_dimensions['new_calculated_height'] = $new_calculated_height;
		$new_dimensions['top_offset'] = $top_offset;
		$new_dimensions['blank_height'] = $blank_height;

		return $new_dimensions;
	}

	/**
	 * Method to  getMediaParams and media source
	 *
	 * @param   ARRAY  $mediaId  mediaId
	 *
	 * @return  course ID
	 *
	 * @since    1.0.0
	 */
	public function getMediaParams($mediaId)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('source,params');
		$query->from($db->quoteName('#__tjlms_media'));
		$query->where($db->quoteName('id') . ' = ' . $mediaId);
		$db->setQuery($query);

		return $db->loadobject();
	}
}
