<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;

defined('JPATH_BASE') or die();
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
jimport('joomla.html.parameter.element');
jimport('joomla.html.html');
jimport('joomla.form.formfield');

/**
 * Display the view
 *
 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
 *
 * @return  mixed  A string if successful, otherwise a Error object.
 *
 * @since  1.0.0
 */
class JFormFieldHeader extends JFormField
{
	public $type = 'Header';
/**
 * Display the view
 *
 * @return  mixed  A string if successful, otherwise a Error object.
 *
 * @since  1.0.0
 */
	public function getInput()
	{
		$return = '
		<div class="tjlms_div_outer">
			<div class="tjlms_div_inner">
				' . Text::_($this->value) . '
			</div>
		</div>';

		return $return;
	}
}
