<?php
/* ======================================================
# Cookies Policy Notification Bar for Joomla! - v4.0.0 (Pro version)
# -------------------------------------------------------
# For Joomla! CMS
# Author: Web357 (Yiannis Christodoulou)
# Copyright (©) 2009-2020 Web357. All rights reserved.
# License: GNU/GPLv3, http://www.gnu.org/licenses/gpl-3.0.html
# Website: https:/www.web357.com/
# Demo: https://demo.web357.com/joomla/cookiespolicynotificationbar
# Support: support@web357.com
# Last modified: 26 Nov 2020, 01:41:46
========================================================= */

defined('_JEXEC') or die;

jimport('joomla.form.formfield');

class JFormFieldviewacceptancelogs extends JFormField {
	
	protected $type = 'viewacceptancelogs';

	protected function getInput()
	{
		$html = '';

		if (version_compare(JVERSION, '3.0', 'lt'))
		{
			// j25
			$html .= '<div style="display: block;border: 2px solid red;clear: both;padding: 4px;">This "View Acceptance Logs" feature is not supported anymore in Joomla! 2.5</div>';

		}
		elseif (version_compare(JVERSION, '4.0', 'lt'))
		{
			// j3
			// load js
			JFactory::getDocument()->addScript(JURI::root(true).'/plugins/system/cookiespolicynotificationbar/assets/js/admin.min.js');

			// load the modal behavior to display the logs
			JHtml::_('behavior.modal');

			// View logs
			$modalParams = array(
				'title'       => ''.JText::_('PLG_SYSTEM_CPNB_ACCEPTANCE_LOGS_HEADING_IN_MODAL').'',
				'closeButton' => true,
				'height'      => '400px',
				'width'       => '800px',
				'bodyHeight'  => '70',
				'modalWidth'  => '80',
				'backdrop'    => true,
				'keyboard'    => true,
				'footer'      => '<a role="button" class="btn btn-secondary" data-dismiss="modal" aria-hidden="true">' . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</a>'
			);
			$modalBody = '<div class="container-fluid"><div class="row-fluid"><div class="cpnb-acceptance-logs-table" style="height:1200px">...</div></div></div>';
			$html .= JHtml::_('bootstrap.renderModal', 'modal-view-acceptance-logs', $modalParams, $modalBody);

			// Buttons: View Logs | Delete Logs
			$html .= '<div class="cpnb-acceptance-logs">';
			$html .= '<h3>'.JText::_('PLG_SYSTEM_CPNB_VIEW_OR_DELETE_LOGS').'</h3>';
			$html .= '<p>';
			$html .= '<a href="#modal-view-acceptance-logs" role="button" class="btn btn-success cpnb-view-acceptance-logs-btn" data-toggle="modal"><strong>'.JText::_('PLG_SYSTEM_CPNB_VIEW_LOGS').'</strong></a>';
			$html .= '&nbsp;&nbsp; | &nbsp;&nbsp;';
			$html .= '<a class="btn btn-danger cpnb-delete-acceptance-logs-btn" data-cpnb-delete-confirmation-msg="'.JText::_('PLG_SYSTEM_CPNB_SURE_FOR_DELETE').'"><strong>'.JText::_('PLG_SYSTEM_CPNB_DELETE_LOGS').'</strong></a>';
			$html .= '</p>';
			$html .= '<div class="cpnb-acceptance-logs-deleted"></div>';
			$html .= '</div>';
			
		}
		elseif (version_compare(JVERSION, '4.0', 'gt'))
		{
			// j4
			$html .= '<div style="display: block;border: 2px solid red;clear: both;padding: 4px;">This "View Acceptance Logs" feature is not supported yet in Joomla! 4.x</div>';
		}
		
		return $html;		
	}
}