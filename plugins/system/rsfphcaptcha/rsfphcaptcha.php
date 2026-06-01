<?php
/**
* @package RSform!Pro
* @copyright (C) 2014 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die;

define('RSFORM_FIELD_HCAPTCHA', 2422);

class plgSystemRsfphcaptcha extends CMSPlugin
{
	protected $autoloadLanguage = true;

	public function __construct($subject, array $config = array())
	{
		parent::__construct($subject, $config);

		if (!class_exists('RSFormProHelper'))
        {
            if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php'))
            {
                return;
            }

	        require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php';
        }

		RSFormProHelper::$captchaFields[] = RSFORM_FIELD_HCAPTCHA;
	}

	public function onRsformBackendAfterCreateFieldGroups(&$fieldGroups, $self)
	{
		$formId = JFactory::getApplication()->input->getInt('formId');
		$exists = RSFormProHelper::componentExists($formId, RSFORM_FIELD_HCAPTCHA);

		$fieldGroups['captcha']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_HCAPTCHA,
			'name' 	=> JText::_('PLG_SYSTEM_RSFPHCAPTCHA_LABEL'),
			'icon'  => 'rsficon rsficon-spinner9',
			'exists' => $exists ? $exists[0] : false
		);
	}

	// Show the Configuration tab
	public function onRsformBackendAfterShowConfigurationTabs($tabs)
	{
		$tabs->addTitle(JText::_('PLG_SYSTEM_RSFPHCAPTCHA_LABEL'), 'page-hcaptcha');
		$tabs->addContent($this->showConfigurationScreen());
	}

	private function loadFormData()
	{
		$data 	= array();
		$db 	= JFactory::getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__rsform_config'))
			->where($db->qn('SettingName') . ' LIKE ' . $db->q('hcaptcha.%', false));
		if ($results = $db->setQuery($query)->loadObjectList())
		{
			foreach ($results as $result)
			{
				$data[$result->SettingName] = $result->SettingValue;
			}
		}

		return $data;
	}

	protected function showConfigurationScreen()
	{
		ob_start();

		JForm::addFormPath(__DIR__ . '/forms');

		$form = JForm::getInstance( 'plg_system_rsfphcaptcha.configuration', 'configuration', array('control' => 'rsformConfig'), false, false );
		$form->bind($this->loadFormData());

		?>
        <div id="page-hcaptcha" class="form-horizontal">
            <p><?php echo JText::_('PLG_SYSTEM_RSFPHCAPTCHA_DONT_HAVE_HCAPTCHA_ACCOUNT'); ?>
                <a href="https://www.hcaptcha.com" target="_blank"><?php echo JText::_('PLG_SYSTEM_RSFPHCAPTCHA_CLICK_HERE_TO_GET_STARTED'); ?></a></p>
			<?php
			foreach ($form->getFieldsets() as $fieldset)
			{
				if ($fields = $form->getFieldset($fieldset->name))
				{
					foreach ($fields as $field)
					{
						// This is a workaround because our fields are named "hcaptcha." and Joomla! uses the dot as a separator and transforms the JSON into [hcaptcha][language] instead of [hcaptcha.language].
						echo str_replace('"rsformConfig[hcaptcha][', '"rsformConfig[hcaptcha.', $form->renderField($field->fieldname));
					}
				}
			}
			?>
        </div>
		<?php

		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

	public function onRsformFrontendAJAXScriptCreate($args)
	{
		$script =& $args['script'];
		$formId = $args['formId'];

		if ($componentId = RSFormProHelper::componentExists($formId, RSFORM_FIELD_HCAPTCHA))
		{
			$form = RSFormProHelper::getForm($formId);

			$logged	= $form->RemoveCaptchaLogged ? JFactory::getUser()->id : false;

			$data = RSFormProHelper::getComponentProperties($componentId[0]);

			if (!empty($data['SIZE']) && $data['SIZE'] == 'INVISIBLE' && !$logged)
			{
				$script .= 'ajaxValidationhCaptcha(task, formId, data, '.$componentId[0].');'."\n";
			}
		}
	}

	public function onRsformFrontendAfterFormProcess($args)
	{
		$formId = $args['formId'];

		if (RSFormProHelper::componentExists($formId, RSFORM_FIELD_HCAPTCHA))
		{
			JFactory::getSession()->clear('com_rsform.hCaptchaToken'.$formId);
		}
	}

	public function onRsformFrontendInitFormDisplay($args)
	{
		if ($componentIds = RSFormProHelper::componentExists($args['formId'], RSFORM_FIELD_HCAPTCHA))
		{
			$all_data = RSFormProHelper::getComponentProperties($componentIds);

			if ($all_data)
			{
				foreach ($all_data as $componentId => $data)
				{
					$args['formLayout'] = preg_replace('/<label (.*?) for="' . preg_quote($data['NAME'], '/') .'"/', '<label $1', $args['formLayout']);
				}
			}
		}
	}
}