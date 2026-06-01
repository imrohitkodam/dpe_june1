<?php
/**
 * @package     TJLMS
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\View\HtmlView as View;

/**
 * HTML View class for the tags Component
 *
 * @since  _DEPLOY_VERSION__
 */
class TjlmsViewTags extends View
{
	protected $app;

	protected $tagDetails;

	/**
	 * Display Tags data
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 * 
	 * @since  _DEPLOY_VERSION__
	 */
	public function display ($tpl = null)
	{
		$this->app   = Factory::getApplication();
		$input = $this->app->input;

		// Get the tags array from url
		$tags                 = $input->get('tagid');

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tags/models');

		$this->tagDetails = array();

		foreach ($tags as $tagId)
		{
			$tagModel = BaseDatabaseModel::getInstance('Tag', 'TagsModel', array('table_path' => JPATH_ADMINISTRATOR . '/components/com_tags/tables'));
			$this->tagDetails[] = $tagModel->getItem($tagId)[0];
		}

		// Display the view
		parent::display($tpl);
	}
}
