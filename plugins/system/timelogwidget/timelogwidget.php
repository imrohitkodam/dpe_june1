<?php
/**
 * @package     Timelog
 * @subpackage  Plg_TimelogWidget
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

/**
 * Timelog widget Plugin work with the com_timelog component.
 *
 * @since  1.0.0
 */
class PlgSystemTimelogwidget extends CMSPlugin
{
	/**
	 * Add the timelog widget to body
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterRoute()
	{
		$app			= Factory::getApplication();
		$input = $app->input;
		$user = Factory::getUser();

		if ($app->isClient('administrator') || $input->getString('tmpl') || !$user->id || !$this->isAuthorise())
		{
			return;
		}
		$uri = \Joomla\CMS\Uri\Uri::getInstance();
		$uri = $uri->toString();
		if (strpos($uri,'view=test') == false) {
			 // HTMLHelper::_('behavior.modal');
		}
 
		
		$document	= Factory::getDocument();
		$class		= 'timelogwidget';

// Build Custom CSS
$css	= <<<CSS
#timelogwidget {
	cursor: pointer;
	font-size: 0.9em;
	position: fixed;
	text-align: center;
	z-index: 9999;
	-webkit-transition: background-color 0.2s ease-in-out;
	-moz-transition: background-color 0.2s ease-in-out;
	-ms-transition: background-color 0.2s ease-in-out;
	-o-transition: background-color 0.2s ease-in-out;
	transition: background-color 0.2s ease-in-out;

	background: #34b8f0;
	color: #fff;
	border-radius: 25px;
	padding-left: 12px;
	padding-right: 12px;
	padding-top: 12px;
	padding-bottom: 12px;
	right: 20px;
	bottom: 20px;
}

#timelogwidget:hover {
	color: #fff;
}

#timelogwidget > img {
	display: block;
	margin: 0 auto;
}
CSS;

$document->addStyleDeclaration($css);

$document->addScript(Uri::root() . 'media/plg_system_timelogwidget/js/timelogwidget.js');

			$js			= <<<SCRIPTHERE
	jQuery('document').ready(function() {
				var lessionId = jQuery('#lession_id').val();
				var hidetool = jQuery('#hidetool').val();
				var courseId = jQuery('input:hidden[name="test[course_id]"]').val();		
				if(((hidetool && lessionId)  || (!hidetool && !lessionId)) && (!courseId)){
				jQuery(document.body).timelogwidget({
				'className':	'$class',
			});
			}
			
	});
SCRIPTHERE;

		$document->addScriptDeclaration($js);
	}

	/**
	 * Authorise the user to view timelog icon
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	private function isAuthorise()
	{
		static $authorised = null;

		if (is_null($authorised))
		{
			$user       = Factory::getUser();
			$authorised = true;
			$canTimelog = true;

			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				$canTimelog  = RBACL::check($user->id, 'com_cluster', 'core.timelog', 'com_timelog');
			}

			if (!$canTimelog)
			{
				$authorised = false;
			}
		}

		return $authorised;
	}
}
