<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,Easydiscuss
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Filesystem\File;

/**
 * Joomla user group tjintegration Plugin
 *
 * @since  1.3.10
 */
class PlgTjlmsEasydiscuss extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.3.10
	 */
	protected $autoloadLanguage = true;

	/**
	 * The form event. Load additional parameters when available into the field form.
	 * Only when the type of the form is of interest.
	 *
	 * @return  array
	 *
	 * @since   1.3.10
	 */
	public function onPrepareIntegrationField()
	{
		Form::addFieldPath(JPATH_PLUGINS . '/tjlms/easydiscuss/fields');

		if (File::exists(JPATH_ROOT . '/administrator/components/com_easydiscuss/includes/easydiscuss.php'))
		{
			return array('path' => JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/easydiscuss.xml', 'name' => $this->_name);
		}
	}
}
