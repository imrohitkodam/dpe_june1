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
 * The skillcontentmap controller
 *
 * @since  1.0.0
 */
class TjCompetencyControllerSkillContentMap extends FormController
{
	/**
	 * client i.e. the content type
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $client;

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

		if (empty($this->client))
		{
			$this->client = $jinput->get('client', '', 'string');
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

		if (!empty ($this->client))
		{
			$append .= '&client=' . $this->client;
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

		if (!empty ($this->client))
		{
			$append .= '&client=' . $this->client;
		}

		return $append;
	}
}
