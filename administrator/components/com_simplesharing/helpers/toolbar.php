<?php

/**
 * @version     1.0.2
 * @package     com_simplesharing
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @author      NYC HelpDesk.co LLC <support@nychelpdesk.co> - nychelpdesk.co
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Simplesharing Toolbar helper.
 */
class SimpleSharingToolbarHelper extends JToolbarHelper {

    public static function modal($targetModalId, $icon, $alt)
	{
		JHtml::_('behavior.modal');
		$title = JText::_($alt);
                $message = JText::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
		$message = addslashes($message);
                $cmd = "if (document.adminForm.boxchecked.value==0){alert(\"$message\"); event.stopPropagation();}";

		$dhtml = "<button data-toggle='modal' data-target='#" . $targetModalId . "' onClick='".$cmd."' class='btn btn-small'>
			<i class='" . $icon . "' title='" . $title . "'></i> " . $title . "</button>";

		$bar = JToolbar::getInstance('toolbar');
		$bar->appendButton('Custom', $dhtml, $alt);
	}
}
