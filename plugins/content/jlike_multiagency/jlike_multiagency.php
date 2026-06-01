<?php
/**
 * @package    Jlike
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\Form;

use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Jilke Agency Plugin
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgContentJlike_Multiagency extends CMSPlugin
{
	protected $autoloadLanguage = true;

	/**
	 * The form event. Load additional parameters when available into the field form.
	 * Only when the type of the form is of interest.
	 *
	 * @param   Form     $form  The form
	 * @param   stdClass  $data  The data
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof Form))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		$name         = $form->getName();
		$allowedForms = array('com_jlike.recommendation');

		if (!in_array($name, $allowedForms))
		{
			return true;
		}

		if (is_object($data) && ($name == 'com_jlike.recommendation'))
		{
		}
	}
}
