<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

jimport('joomla.application.component.view');
jimport('techjoomla.common');

require_once JPATH_SITE . '/administrator/components/com_tjlms/helpers/tjlms.php';
/**
 * View to edit
 *
 * @since  1.0.0
 */
class TjlmsViewCoupon extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$app  = Factory::getApplication();
		$canDo = TjlmsHelper::getActions();

		if (!$canDo->get('view.coupons'))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);
			
			return false;
		}

		$this->techjoomlacommon = new TechjoomlaCommon;
		$this->state = $this->get('State');
		$this->item = $this->get('Item');
		$this->form = $this->get('Form');

		// Import helper for declaring language constant
		JLoader::import('TjlmsHelper', Uri::root() . 'administrator/components/com_tjlms/helpers/tjlms.php');

		// Call helper function
		TjlmsHelper::getLanguageConstant();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		parent::display($tpl);
	}
}
