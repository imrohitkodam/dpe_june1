<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;

/**
 * The skill controller
 *
 * @since  1.0.0
 */
class TjCompetencyControllerSkill extends FormController
{
	/**
	 * The framework for which the skills are being created.
	 *
	 * @var    int
	 * @since  1.0
	 */
	protected $framework;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since  1.6
	 * @see    JControllerLegacy
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$app    = Factory::getApplication();
		$jinput = $app->input;

		if (empty($this->framework))
		{
			$this->framework = $jinput->getInt('framework_id');
		}
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.6
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$append = parent::getRedirectToItemAppend($recordId);

		if (!empty ($this->framework))
		{
			$append .= '&framework_id=' . $this->framework;
		}

		return $append;
	}

	/**
	 * Gets the URL arguments to append to a list redirect.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.6
	 */
	protected function getRedirectToListAppend()
	{
		$append = parent::getRedirectToListAppend();

		if (!empty ($this->framework))
		{
			$append .= '&framework_id=' . $this->framework;
		}

		return $append;
	}
}
