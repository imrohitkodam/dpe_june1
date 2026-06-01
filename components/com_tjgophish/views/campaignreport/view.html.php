<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;

/**
 * TJGOPHISH - Campaignreport View
 *
 * @since  1.0.0
 */
class TjGoPhishViewCampaignReport extends HtmlView
{
	protected $item;

	/**
	 * Display the Campaign report view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		// Access check: is this user registered?
		if (empty(Factory::getUser()->id))
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
		}

		$jInput = Factory::getApplication()->input;
		$id = $jInput->get('id', 0, 'INT');

		// Get the Data
		$model = $this->getModel();
		$this->item = $model->getItem($id);
		$this->state = $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		// Set the document
		$this->setDocuments();

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocuments()
	{
		$document = Factory::getDocument();
		$document->setTitle(Text::_('COM_TJGOPHISH_CAMPAIGN_REPORT'));
	}
}
