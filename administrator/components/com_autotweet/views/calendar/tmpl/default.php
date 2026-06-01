<?php

/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     http://https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\ScriptHelper;

$siteUrl = \Joomla\CMS\Uri\Uri::root();

ScriptHelper::addStyleSheet($siteUrl.'/media/com_autotweet/fullcalendar/fullcalendar.min.css');
ScriptHelper::addStyleSheet($siteUrl.'/media/com_autotweet/fullcalendar/fullcalendar.print.min.css', 'text/css', 'print');

JHtml::_('jquery.framework');

ScriptHelper::addScript($siteUrl.'/media/com_autotweet/fullcalendar/lib/moment.min.js');
ScriptHelper::addScript($siteUrl.'/media/com_autotweet/fullcalendar/fullcalendar.min.js');

$now = \Joomla\CMS\Factory::getDate();
$formatted_date = $now->format('Y-m-d');

$config = \Joomla\CMS\Factory::getConfig();

$jlang = \Joomla\CMS\Factory::getLanguage();
$current_lang = $jlang->getTag();
[$lang, $var] = explode('-', $current_lang);

if (('en' === $lang) || ('es' === $lang)) {
    ScriptHelper::addScript($siteUrl.'/media/com_autotweet/fullcalendar/lang/'.$lang.'.js');
} else {
    ScriptHelper::addScript($siteUrl.'/media/com_autotweet/fullcalendar/lang/en.js');
}

$offset = EParameter::getTimezone();
$tmz = $offset->getName();

ScriptHelper::addScriptDeclaration(
    "
		jQuery(document).ready(function() {

			jQuery('#xt-calendar').fullCalendar({
				header: {
					left: 'prev,next today',
					center: 'title',
					right: 'month,agendaWeek,agendaDay'
				},
				defaultDate: '{$formatted_date}',
				lang: '{$lang}',
				timezone: '{$tmz}',
				timeFormat: 'HH:mm',
				startParam: 'xtstart',
				endParam: 'xtend',

				events: {
					url: 'index.php?option=com_autotweet&view=requests&layout=calendar&format=json',
					error: function() {
						jQuery('#script-warning').show();
					}
				},
				loading: function(bool) {
					if (bool) {
						jQuery('.loaderspinner72').addClass('loading72');
					} else {
						jQuery('.loaderspinner72').removeClass('loading72');
					}
				}
			});

		});
"
);

?>
<div class="container-fluid container-main">
	<div class="extly extly-calendar">
		<div class="xt-body">
			<div class="xt-grid xt-cal-header">
				<div class="xt-col-span-8">
					<h1><?php echo $config->get('sitename'); ?></h1>
					<hr/>
				</div>
				<div class="xt-col-span-4">
					<h3 class="xt-cal-brand xt-text-right">
						<i class="xticon far fa-calendar"></i>
						<?php
                            echo JText::_('COM_AUTOTWEET');
                        ?> -
						<?php
                            echo JText::_('COM_AUTOTWEET_VIEW_CALENDAR_TITLE');
                        ?>
					</h3>
					<p class="xt-cal-brand">
						<?php
                            echo AutoTweetDefaultView::showWorldClockLink();
                        ?>
					</p>
					<hr/>
				</div>
			</div>

			<div class="xt-grid">
				<div class="xt-col-span-12">

					<div id='script-warning' style="display:none;">
						<div class="xt-alert xt-alert-error">
							<p><?php echo JText::_('COM_AUTOTWEET_UNABLE_LOAD_ERROR'); ?></p>
						</div>
					</div>

					<span class="loaderspinner72"><?php echo JText::_('COM_AUTOTWEET_LOADING'); ?></span>

					<div id="xt-calendar"></div>
				</div>
			</div>
		</div>
	</div>
</div>
