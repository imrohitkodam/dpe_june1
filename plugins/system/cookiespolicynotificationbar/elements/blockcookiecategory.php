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
		
jimport('joomla.form.formfield');
jimport( 'joomla.form.form' );

class JFormFieldblockcookiecategory extends JFormField {
	
	protected $type = 'blockcookiecategory';

	protected function getInput() 
	{	
		return $this->fetchElement($this->name, $this->value, $this->element, $this->options['control']);
	}

	function fetchElement($name, $value, &$node, $control_name)
    {
		// get th params from db
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('params'));
		$query->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('element').' = '.$db->quote('cookiespolicynotificationbar'));
		$query->where($db->quoteName('folder').' = '.$db->quote('system'));
		$db->setQuery($query);
		$extension_params = $db->loadResult();

		if (!empty($extension_params))
		{
			$extension_params_json = json_decode($extension_params);
			$cookie_categories_group = isset($extension_params_json->cookie_categories_group) ? $extension_params_json->cookie_categories_group : '';

			if(!empty($cookie_categories_group) && is_object($cookie_categories_group))
			{
				$options = array();
				foreach ($cookie_categories_group as $k=>$v)
				{
					$options[] = JHtml::_('select.option', $v->cookie_category_id, $v->cookie_category_name);
				}

				$value = $this->value;

				// If the value is a string -> Only one result
				if (is_string($value))
				{
					$value = array($value);
				}
				elseif (is_object($value))
				{
					// If the value is an object, let's get its properties.
					$value = get_object_vars($value);
				}
			}
			else
			{
				// first installation
				$options = array();
				$options[] = JHtml::_('select.option', 'required-cookies', 'Required Cookies');			
				$options[] = JHtml::_('select.option', 'analytical-cookies', 'Analytical Cookies');			
				$options[] = JHtml::_('select.option', 'social-media-cookies', 'Social Media');			
				$options[] = JHtml::_('select.option', 'targeted-advertising-cookies', 'Targeted Advertising Cookies');
			}
		}
		else
		{
			$options = array();
			$options[] = JHtml::_('select.option', 'required-cookies', 'Required Cookies');			
			$options[] = JHtml::_('select.option', 'analytical-cookies', 'Analytical Cookies');			
			$options[] = JHtml::_('select.option', 'social-media-cookies', 'Social Media');			
			$options[] = JHtml::_('select.option', 'targeted-advertising-cookies', 'Targeted Advertising Cookies');			
		}

		//array_unshift($options, JHtml::_('select.option', '', ''));
		return JHtml::_('select.genericlist', $options, $this->name, '', 'value', 'text', $value, $this->id);
    }
}