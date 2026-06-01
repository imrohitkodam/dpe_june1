<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Filesystem\File;

JLoader::import('components.com_tjlms.includes.tjlms', JPATH_ADMINISTRATOR);

/**
 * Course plugin
 *
 * @since  1.0
 */
class PlgTjCompetencyContentTypeCourse extends CMSPlugin
{
	/**
	 * The Application object
	 *
	 * @var    JApplicationSite
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * The supported form contexts
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $supportedContext = array(
		'com_tjcompetency.skillcontentmap',
		'com_tjcompetency.skillcontentmaps',
		'com_tjcompetency.skillcontentusermap',
		'com_tjcompetency.skillcontentusermaps',
		'com_tjreports.reports',
	);

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *
	 * @since   3.7.0
	 */
	public function __construct(&$subject, $config)
	{
		// Don't even register this plugin if TjLms isn't loaded and available
        if (class_exists('TjLms'))
        {
			parent::__construct($subject, $config);
        }
	}

	/**
	 * Get Content Name
	 *
	 * @param   int  $id  Content Id
	 *
	 * @return  string|void
	 *
	 * @since   1.0.0
	 */
	public function courseGetContentName($id)
	{
		return TJLms::Course($id)->title;
	}

	/**
	 * Get Content Link
	 *
	 * @param   int  $id  Content Id
	 *
	 * @return  string|void
	 *
	 * @since   1.0.0
	 */
	public function courseGetContentLink($id)
	{
		$comtjlmsHelper = new comtjlmsHelper;

		return $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $id, false);
	}

	/**
	 * Get Supported Context
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	public function courseGetSupportedContext()
	{
		return $this->supportedContext;
	}

	/**
	 * Add additional fields to the supported forms
	 *
	 * @param   JForm  $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   3.9.0
	 */
	public function onContentPrepareForm(JForm $form, $data)
	{
		$client = $data->client;

		if (empty($client))
		{
			$client = $this->app->input->get('client', '', 'string');
		}

		if ($this->app->isClient('site') || !in_array($form->getName(), $this->supportedContext) || $client != 'course')
		{
			return true;
		}

		$path = JPATH_PLUGINS . "/{$this->_type}/{$this->_name}/forms/field.xml";

		$form->loadFile($path);

		return true;
	}
}
