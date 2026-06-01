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

defined('JPATH_BASE') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * JFormFieldSubuserfilter helper.
 *
 * @since  1.1.8
 */
class JFormFieldPaymentstatus extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'paymentstatus';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		JLoader::register('TjlmsModelOrders', JPATH_ADMINISTRATOR . '/components/com_tjlms/models/orders.php');
		$ordersModel = new TjlmsModelOrders;
		$paymentStatus = $ordersModel->getPaymentStatusFilter();

		$options = array();
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATUS'));

		foreach ($paymentStatus as $status)
		{
			$options[] = JHTML::_('select.option', $status->value, $status->text);
		}

		return $options;
	}
}
