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
 * The scale controller
 *
 * @since  1.0.0
 */
class TjCompetencyControllerScale extends FormController
{
	/**
	 * The scaleset for which the scales are being created.
	 *
	 * @var    int
	 * @since  1.0
	 */
	protected $scaleset;

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

		if (empty($this->scaleset))
		{
			$this->scaleset = $jinput->getInt('scale_set_id');
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

		if (!empty ($this->scaleset))
		{
			$append .= '&scale_set_id=' . $this->scaleset;
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

		if (!empty ($this->scaleset))
		{
			$append .= '&scale_set_id=' . $this->scaleset;
		}

		return $append;
	}
}
