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

defined('JPATH_BASE') or die;

require_once(JPATH_SITE."/plugins/system/web357framework/elements/elements_helper.php");

jimport('joomla.form.formfield');

class JFormFieldLangheader extends JFormField {
	
	function getInput()
	{
		return "";
	}

	function getLabel()
	{
		// Retrieving request data using JInput
		$jinput = JFactory::getApplication()->input;

		if (method_exists($this, 'fetchTooltip')):
			$label = $this->fetchTooltip($this->element['label'], $this->description, $this->element, $this->options['control'], $this->element['name'] = '');
		else:
			$label = parent::getLabel();
		endif;
		
		// Get Joomla! version
		$jversion = new JVersion;
		$short_version = explode('.', $jversion->getShortVersion()); // 3.8.10
		$mini_version = $short_version[0].'.'.$short_version[1]; // 3.8
		if (version_compare( $mini_version, "2.5", "<=")) :
			// v2.5
			$jversion_class = 'vj25x';
		elseif (version_compare( $mini_version, "3.0", "<=")) :
			// v3.0.x
			$jversion_class = 'vj30x';
		elseif (version_compare( $mini_version, "3.1", "<=")) :
			// v3.1.x
			$jversion_class = 'vj31x';
		elseif (version_compare( $mini_version, "3.2", "<=")) :
			// v3.2.x
			$jversion_class = 'vj32x';
		elseif (version_compare( $mini_version, "3.3", "<=")) :
			// v3.3.x
			$jversion_class = 'vj33x';
		elseif (version_compare( $mini_version, "3.4", "<=")) :
			// v3.4.x
			$jversion_class = 'vj34x';
		else:
			// other
			$jversion_class = 'j00x';
		endif;
		
		// There are two types of class, the w357_large_header, w357_small_header, w357_xsmall_header.
		$id = (!empty($this->element['id'])) ? $this->element['id'] : '';
		$class = (!empty($this->element['class'])) ? $this->element['class'] : '';
		$lang_code = (!empty($this->element['lang_code'])) ? $this->element['lang_code'] : '';
		$language_name = (!empty($this->element['language_name'])) ? $this->element['language_name'] : '';

		// flag
		$juri_base = str_replace('/administrator', '', JURI::base());
		
		if (!version_compare( $mini_version, "2.5", "<=")) :
			// joomla 3.x
			$lang_code_img = strtolower($lang_code);
		else:
			// joomla 2.5
			$lang_code_img = strtolower(substr($lang_code, 0, 2));
		endif;
		
		$lang_img = "<img src='".$juri_base."media/mod_languages/images/".$lang_code_img.".gif' alt='".$language_name."' title='".$language_name."' style='margin-right: 5px;' />";
		
		// get default site language name by tag.
		$frontend_language_tag = JComponentHelper::getParams('com_languages')->get('site');
		$default_site_language_name = $this->getLanguageNameByTag($frontend_language_tag);

		if (!empty($default_site_language_name) && $default_site_language_name == $language_name):
			$display_language_name = $language_name." (Default Site language)";
		else:
			$display_language_name = $language_name;
		endif;

		// build label
		$custom_label  = '';
		$custom_label .= '<label>';
		$custom_label .= $lang_img.' '.JText::_('J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_TEXTS_FOR').' '.$display_language_name;
		$custom_label .= '</label>';

		// Output
		return '<div class="w357frm_param_header '.$class.' '.$jversion_class.' '.$jinput->get('option').'">'.$custom_label.'</div>';
	}

	public function getLanguageNameByTag($tag)
	{
		$db = JFactory::getDBO();
		$query = "SELECT title_native "
		."FROM #__languages "
		."WHERE lang_code = '".$tag."' AND published = 1"
		;
		$db->setQuery($query);
		$db->execute();
		$result = $db->loadResult();
		
		if ($result !== null)
		{
			// If there are more than one language
			return $result;
		}
		else
		{
			// If there is only one language
			return $this->getDefaultLanguageName();
		}
	}

	public function getDefaultLanguageName()
	{
		$db = JFactory::getDBO();
		$query = "SELECT title_native "
		."FROM #__languages "
		."WHERE published = 1"
		;
		$db->setQuery($query);
		$db->execute();

		return $db->loadResult();
	}

}