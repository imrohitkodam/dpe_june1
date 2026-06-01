<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\Field\ListField;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of courses
 *
 * @since       1.3.14
 * @deprecated  1.3.32 Use TJCertificate certificatetemplates fields instead
 */
class JFormFieldCertificatetemplate extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.3.22
	 */
	protected $type = 'Certificatetemplate';

	/**
	 * Method to get a list of certificateTempplate for a list input.
	 *
	 * @return	array		An array of HTMLHelper options.
	 *
	 * @since	1.3.14
	 */
	protected function getInput()
	{
		$input = Factory::getApplication()->input;
		$certificateId = $input->getInt('id', 0);

		JLoader::import('components.com_tjlms.models.certificates', JPATH_ADMINISTRATOR);
		$certificatesModel = BaseDatabaseModel::getInstance('Certificates', 'TjlmsModel', array('ignore_request' => true));

		$certificatesModel->setState('filter.state', 1);
		$results = $certificatesModel->getItems();

		$options = array();
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FORM_SELECT_CERTIFICATETEMPLATE_TEMPLATE'));

		if (!empty($results))
		{
			foreach ($results as $result)
			{
				$options[] = HTMLHelper::_('select.option', $result->id, $result->title);
			}
		}

		if ($certificateId)
		{
			$this->value = $certificateId;
		}

		return HTMLHelper::_('select.genericlist', $options, $this->name, '', 'value', 'text', $this->value);
	}
}
