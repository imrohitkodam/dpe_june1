<?php
/**
 * @package     Joomla.Plugins
 * @subpackage  PlgFieldsTjFile
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// @namespace Techjoomla\Plugin\Fields\Tjfile;

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('jquery.framework');
// Add styles
$style = '.loader{
	width: 100px;
	height: 100px;
	background: url("' . Uri::root(true) . '/plugins/fields/tjfile/assets/loader.gif") 50% 50% no-repeat rgb(249,249,249);
}';

$document = Factory::getDocument();
$document->addStyleDeclaration($style);

/**
 * Fields File Plugin
 *
 * @since  1.0.0
 */
class JFormFieldTjfile extends FormField
{
	protected $type = 'Tjfile';

	/**
	 * Getting values from file field.
	 *
	 * @return  file name
	 *
	 * @since   1.0.0
	 */
	protected function getInput()
	{
		// Set session for authentication
		$session = Factory::getSession();

		$user = Factory::getUser();

		$settings = array(
			'filesize'        => $this->getAttribute('filesize') ,
			'destination'     => $this->getAttribute('destination') ,
			'acceptedformats' => $this->getAttribute('acceptedformats') ,
			'filename_format' => $this->getAttribute('filename_format')
		);

		$session->set('fileupload', $settings);

		$configData = $session->get('fileupload');

		
		HTMLHelper::script(Uri::root() . '/plugins/fields/tjfile/assets/file.js');

		$html = '
		<div class="control-label">
			<label>File Upload</label>
		</div>

		<div class="controls">
			<input type="file" name="getFileUpload' . $this->name . '" id="getFileUpload' . $this->id . '" onchange="getFileChange(this.id);" />

			<input type="hidden" name="' . $this->name . '" class="setfileval' . $this->id . '" "  id="' . $this->id . '" value="' . $this->value . '" />

			<div id="loader' . $this->id . '" class="loader d-none"></div>
		</div>';

		$filepath = JPATH_SITE . '/' . $settings['destination'] . '/' . $this->value;

			if(!empty($user->id))
			{
				$filepreviewpath = JUri::root() . $settings['destination'] . '/' . $this->value;
			}

		// Delete button display if file uploaded
		if((!empty($this->value)) && File::exists($filepath))
		{
				$html .= "
			<div id='uploaded-file_" . $this->id . "'><a href=".$filepreviewpath." download>" . $this->value . "</a>
			<button type='button' class='remove-attr' id='remove_" . $this->id . "' value='". $this->value . "' onclick='removeFile(this.id);'>" . "<i class='fa fa-trash'></i>" . "</button>
			</div>";
		}

		$html .= '<br>';

		return $html;
	}
}