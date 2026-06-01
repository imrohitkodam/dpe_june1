<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.html.parameter');

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewbuy extends HtmlView
{
	protected $user;

	protected $defaultCountryMobileCode;

	protected $subsPlan;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$user    = Factory::getUser();
		$app     = Factory::getApplication();
		$input   = $app->input;
		$session = Factory::getSession();
		$layout  = $input->get('layout', '', 'STRING');

		// IF guest user return false.
		if (!$user->id)
		{
			echo $this->logoutmessage = Text::_("COM_TJLMS_MESSAGE_LOGIN_FIRST");

			return;
		}

		$model = $this->getModel();

		$this->course_id   = $input->get('course_id', '', 'INT');
		$this->course_info = $model->getcourseinfo($this->course_id);

		if (empty($this->course_id) || empty($this->course_info))
		{
			$app->enqueueMessage(Text::_('COM_TJLMS_COURSE_DOES_NOT_EXISTS'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		if ($this->course_info->type != 1)
		{
			$app->enqueueMessage(Text::_('COM_TJLMS_COURSE_DOES_NOT_PAID'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		$this->tjlmsFrontendHelper = new comtjlmsHelper;

		$this->tjlmsFrontendHelper->getLanguageConstant();

		$this->logoutmessage_orderid = Text::_("COM_TJLMS_SESSION_EXPIRED_ORDERID");

		PluginHelper::importPlugin('payment');

		$com_params     = ComponentHelper::getParams('com_tjlms');
		$gatewaysconfig = $com_params->get('gateways', '', 'ARRAY');
		$this->currency = $com_params->get('currency', '', 'STRING');
		$this->creator  = $com_params->get('show_user_or_username', 'name');

		// For default layout get all info of course to display on  1st page of check out
		if ($input->get('layout', '', 'STRING') == '')
		{
			$this->subsPlan = $this->get('Subsplan');
		}

		$this->itemid = $input->get('Itemid');

		// Get User date to prefill user info on billing tab.
		if ($user->id)
		{
			$this->userdata = $this->get('userdata');
		}

		$this->userbill = (isset($this->userdata['BT'])) ? $this->userdata['BT'] : '';

		$this->defaultCountryMobileCode = (!empty($this->userbill->country_mobile_code))
			? $this->userbill->country_mobile_code : $com_params->get('default_country_mobile_code');

		$gateways = array();

		if (!empty($gatewaysconfig))
		{
			$gateways = Factory::getApplication()->triggerEvent('onTP_GetInfo', array($gatewaysconfig));
		}

		$newgateways = array();

		foreach ($gateways as $gateway)
		{
			if (!empty($gateway->id))
			{
				if (empty($gateway->name))
				{
					$gateway->name = $gateway->id;
				}

				$newgateways[] = $gateway;
			}
		}

		// Get country dropdown
		$this->country           = $this->get('Country');
		$this->gateways          = $newgateways;
		$this->allow_taxation    = $com_params->get('allow_taxation', '', 'INT');
		$this->enable_bill_vat   = $com_params->get('enable_bill_vat', '', 'INT');
		$this->tnc               = $com_params->get('terms_condition', '', 'INT');

		if ($this->tnc)
		{
			$this->article = $com_params->get('tnc_article', '', 'INT');

			// Check if the article exists
			$this->doesArticleExists = $model->doesArticleExists($this->article);
		}

		$this->user = Factory::getUser();
		$plugin = PluginHelper::getPlugin('lmstax', 'lms_tax_default');

		if ($plugin)
		{
			$pluginParams = new Registry;
			$pluginParams->loadString($plugin->params);
			$this->tax_per = $pluginParams->get('tax_per', '0');
		}

		$courseLink = $this->tjlmsFrontendHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $this->course_id, false);

		JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
		$TjlmsModelcourse = new TjlmsModelcourse;
		$checkifuserenroled = $TjlmsModelcourse->checkifuserenroled($this->course_id, $user->id, $this->course_info->type);

		$course_user_order_info = $TjlmsModelcourse->course_user_order_info($this->course_id);

		if ((isset($checkifuserenroled) && ($checkifuserenroled == 1 || $checkifuserenroled == 0))
			&& (isset($course_user_order_info->status) && ($course_user_order_info->status == 'P' || $course_user_order_info->status == 'C'))
			&& $course_user_order_info->status != 'I')
		{
			$app->redirect($courseLink);
		}

		parent::display($tpl);
	}
}
