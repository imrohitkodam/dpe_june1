<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,Easydiscuss
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Filesystem\File;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of courses
 *
 * @since  1.3.10
 */
class JFormFieldDiscussion extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.3.10
	 */
	protected $type = 'discussion';

	/**
	 * Fiedd to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	2.2
	 */
	protected $loadExternally = 0;

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   1.3.10
	 */
	protected function getOptions()
	{
		$mainFile = JPATH_ROOT . '/administrator/components/com_easydiscuss/includes/easydiscuss.php';

		if (!File::exists($mainFile))
		{
			return;
		}

		// Engine is required anywhere EasySocial is used.
		require_once $mainFile;

		$config = array();
		$discussCatModel = ED::model('categories');
		$config['published'] = 1;
		$discussCategories = $discussCatModel->getAllCategories($config);

		$options = array();
		$options[] = HTMLHelper::_('select.option', "", Text::_('COM_TJLMS_EASYDISCUSS_CATEGORIES'));

		foreach ($discussCategories as $c)
		{
			$options[] = HTMLHelper::_('select.option', $c->id, $c->title);
		}

		if (!$this->loadExternally)
		{
			// Merge any additional options in the XML definition.
			$options = array_merge(parent::getOptions(), $options);
		}

		return $options;
	}

	/**
	 * Method to get a list of options for a list input externally and not from xml.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   1.3.10
	 */
	public function getOptionsExternally()
	{
		$this->loadExternally = 1;

		return $this->getOptions();
	}
}
