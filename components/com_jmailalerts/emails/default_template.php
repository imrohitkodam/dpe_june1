<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$emails_config = array(
'message_subject' => "Updates from the community at [SITENAME]",
'message_body'    => "
<table
	id=\"layout\"
	style=\"width: 620px; margin: 10px auto; text-align: left;
	border-collapse: collapse; background: #fff;\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"
	>
	<tbody>
		<tr>
			<td class=\"rounded\" style=\"margin: 0; padding: 0; line-height: 8px;\">
				<img src=\"components/com_jmailalerts/assets/images/header_top.gif\" border=\"0\" width=\"620\" height=\"35\" />
			</td>
		</tr>

		<tr>
			<td
				id=\"header\"
				style=\"background-color: #323232; padding: 20px 30px 25px 30px; color: #ffffff; font-family: Helvetica; font-weight: normal; text-align: left;\"
				>
				<h1 class=\"primary-heading\" style=\"font-size: 28px; font-weight: bold; color: #ffffff; font-family: Helvetica; margin: 0;\">[SITENAME]</h1>
			</td>
		</tr>

		<tr>
			<td
				class=\"rounded\"
				style=\"margin: 0; padding: 0; line-height: 8px;\">
				<img src=\"components/com_jmailalerts/assets/images/header_bottom.gif\" border=\"0\" width=\"620\" height=\"19\" />
			</td>
		</tr>

		<tr>
			<td
				id=\"content\"
				style=\"margin: 0; font-size: 12px; color: #555555;
				font-style: normal; font-weight: normal; font-family: Helvetica; line-height: 1.6em; padding: 10px 30px 0;\">
				<table class=\"content-grid\" border=\"0\">
					<tbody>
						<tr>
							<td style=\"vertical-align: top; padding-right: 30px;\" width=\"500\" valign=\"top\">
								<p style=\"margin: 0px; line-height: 1.6em; font-size: 14px;\">
									<strong>Free Plugins</strong>
								</p>

								<div class=\"jma_div1\">[jma_latestusers]</div>
								<div class=\"jma_div1\">[jma_latestnews_js]</div>
								<div class=\"jma_div1\">[jma_latest_content_k2]</div>
								<div class=\"jma_div1\">[jma_latestitems_flexi]</div>
								<div class=\"jma_div1\">[jma_latestdownload]</div>
								<div class=\"jma_div1\">[jma_latestevents_jem]</div>
								<div class=\"jma_div1\">[jma_latest_posts_kunena]</div>
								<div class=\"jma_div1\">[jma_latestsobipro]</div>
								<div class=\"jma_div1\">[jma_latestphoto_pg]</div>

								<p style=\"margin: 0px; line-height: 1.6em; font-size: 14px;\">
									<strong>Paid Plugins For EasySocial</strong>
								</p>

								<div class=\"jma_div1\">[jma_pending_connections_es]</div>
								<div class=\"jma_div1\">[jma_latestmsg_es]</div>
								<div class=\"jma_div1\">[jma_latestphoto_es]</div>

								<p style=\"margin: 0px; line-height: 1.6em; font-size: 14px;\">
									<strong>Paid Plugins For JomSocial</strong>
								</p>

								<div class=\"jma_div1\">[jma_latestmsg_js]</div>
								<div class=\"jma_div1\">[jma_pending_connections_js]</div>
								<div class=\"jma_div1\">[jma_network_suggest_js]</div>
								<div class=\"jma_div1\">[jma_people_you_may_know_js]</div>
								<div class=\"jma_div1\">[jma_latestevents_js]</div>
								<div class=\"jma_div1\">[jma_latestphoto_js]</div>
								<div class=\"jma_div1\">[jma_latestvideo_js]</div>
								<div class=\"jma_div1\">[jma_groups_js]</div>
								<div class=\"jma_div1\">[jma_group_activity_js]</div>

								<p style=\"margin: 0px; line-height: 1.6em; font-size: 14px;\">
									<strong>Paid Plugins For Community Builder</strong>
								</p>

								<div class=\"jma_div1\">[jma_pending_connections_cb]</div>
								<div class=\"jma_div1\">[jma_network_suggest_cb]</div>
								<div class=\"jma_div1\">[jma_people_you_may_know_cb]</div>

								<p style=\"margin: 0px; line-height: 1.6em; font-size: 14px;\">
									<strong>Other Paid Plugins</strong>
								</p>

								<div class=\"jma_div1\">[jma_easyblog]</div>
								<div class=\"jma_div1\">[jma_latest_docs_docman]</div>
								<div class=\"jma_div1\">[jma_jreviews_listings]</div>
								<div class=\"jma_div1\">[jma_jomestate_listings]</div>
								<div class=\"jma_div1\">[jma_latestitems_zoo]</div>
								<div class=\"jma_div1\">[jma_mosets]</div>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>

		<tr>
			<td
				class=\"rounded\"
				style=\"margin: 0; padding: 0; line-height: 8px;\">
				<img src=\"components/com_jmailalerts/assets/images/afooter.gif\" border=\"0\" width=\"620\" height=\"35\" />
			</td>
		</tr>

		<tr>
			<td
				id=\"footer\"
				style=\"background-color: #626262; padding: 20px; font-size: 13px; color: #ccc; line-height: 150%; font-family: Verdana; text-align: center;\">
					<p>This message was intended for <span> [mailuser] </span> .
					If you do not wish to receive this type of email from [SITENAME] in the future, please set your preferences.
					To update your Mail alert preferences, [PREFRENCES]
					</p>
			</td>
		</tr>
	</tbody>
</table>"
);
