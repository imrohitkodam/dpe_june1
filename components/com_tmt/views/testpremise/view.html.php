<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * View to edit
 *
 * @since  1.0.0
 */
class TmtViewTestpremise extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

	protected $params;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since  1.0.0
	 */
	public function display($tpl = null)
	{
		$app               = Factory::getApplication();
		$user              = Factory::getUser();
		$tmtFrontendHelper = new tmtFrontendHelper;

		// Check if user is logged in
		if (!$user->id)
		{
			$msg = Text::_('COM_TMT_MESSAGE_LOGIN_FIRST');

			// Get curent url
			$current = Uri::getInstance()->toString();
			$url     = base64_encode($current);
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false), $msg);
		}

		$this->item = $this->get('Data');

		// Check if user has access to quiz/test
		if (!isset($this->item->invite_id))
		{
			$app->enqueueMessage(Text::_('COM_TMT_MESSAGE_NO_ACL_PERMISSION'), 'warning');
            $app->setHeader('status', 503, true);
			return false;
		}

		$this->form = $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function _prepareDocument()
	{
	}
}
