<?php
/**
* @package RSSeo!
* @copyright (C) 2019 www.rsjoomla.com
* @license     GNU General Public License version 2 or later; see LICENSE
*/

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

JLoader::registerAlias('JFormFieldList', '\\Joomla\\CMS\\Form\\Field\\ListField');

class JFormFieldFont extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $type = 'Font';
	
	/**
	 * Method to get the field input markup for a combo box field.
	 *
	 * @return  string   The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getOptions() {
		$path = JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/dompdf/vendor/dompdf/dompdf/lib/fonts/';
		
		$options 	= array();
		$options[] = HTMLHelper::_('select.option', 'times', Text::_('COM_RSSEO_PDF_FONT_TIMES'));
		$options[] = HTMLHelper::_('select.option', 'helvetica', Text::_('COM_RSSEO_PDF_FONT_HELVETICA'));
		$options[] = HTMLHelper::_('select.option', 'courier', Text::_('COM_RSSEO_PDF_FONT_COURIER'));
		$options[] = HTMLHelper::_('select.option', 'dejavu sans', Text::_('COM_RSSEO_PDF_FONT_DEJAVU_SANS'), 'value', 'text', !file_exists($path.'DejaVuSans.ufm'));
		$options[] = HTMLHelper::_('select.option', 'fireflysung', Text::_('COM_RSSEO_PDF_FONT_FIREFLYSUNG'), 'value', 'text', !file_exists($path.'fireflysung.ufm'));
		
		return $options;
	}
}