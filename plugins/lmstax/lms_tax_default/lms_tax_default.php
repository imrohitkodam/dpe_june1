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

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
jimport('joomla.plugin.plugin');
$lang = Factory::getLanguage();
$lang->load('plug_lms_tax_default', JPATH_ADMINISTRATOR);

/**
 * PlgLmstaxlms_Tax_Default
 *
 * @since  1.0.0
 */
class PlgLmstaxlms_Tax_Default extends CMSPlugin
{
	/**
	 * function Add Tax
	 *
	 * @param   INT  $amt  amount
	 *
	 * @return  mixed
	 */
	public function onAddTax($amt)
	{
		$tax_per = $this->params->get('tax_per');
		$tax_value = ($tax_per * $amt) / 100;

		$return = new Stdclass;
		$return->percent = $tax_per . "%";
		$return->taxvalue = $tax_value;

		return $return;
	}
}
