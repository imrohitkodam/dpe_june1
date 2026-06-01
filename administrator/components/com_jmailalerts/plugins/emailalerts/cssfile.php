<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

$css_style = "<style type=\"text/css\">
	body {
		/*@editable*/
		background-color: #666666;
	}

	/*@tab Header
	@section header bar
	@tip Choose a set of colors that look good with the colors of your logo image or text header.
	@theme header*/
	#header { /*@editable*/
		background-color: #cccccc; /*@editable*/
		padding: 0px; /*@editable*/
		color: #333333; /*@editable*/
		font-size: 30px; /*@editable*/
		font-family: Georgia; /*@editable*/
		font-weight: normal;
	}

	/*@tab Body
	@section default text
	@tip This is the default text style for the body of your email.
	@theme main*/
	#content { /*@editable*/
		font-size: 13px; /*@editable*/
		color: #333333; /*@editable*/
		font-style: normal; /*@editable*/
		font-weight: normal; /*@editable*/
		font-family: Helvetica; /*@editable*/
		line-height: 1.25em;
		padding: 10px 30px; /*@editable*/
		text-align: center;
	}

	/*@tab Body
	@section title style
	@tip Titles and headlines in your message body. Make them big and easy to read.
	@theme title*/
	.primary-heading { /*@editable*/
		font-size: 28px; /*@editable*/
		font-weight: bold; /*@editable*/
		color: #336699; /*@editable*/
		font-family: Georgia;
		margin: 25px 0 20px 0; /*@editable*/
		text-align: center;
	}

	/*@tab Body
	@section subtitle style
	@tip This is the byline text that appears immediately underneath your titles/headlines.
	@theme subtitle*/
	.secondary-heading { /*@editable*/
		font-size: 20px; /*@editable*/
		font-weight: bold; /*@editable*/
		color: #000000; /*@editable*/
		font-style: normal; /*@editable*/
		font-family: Georgia;
		margin: 25px 0 5px 0;
	}

	/*@tab Body
	@section product grid type
	@tip Adjust type colors for the product grid*/
	#tj-table td strong { /*@editable*/
		color: #336699;
	}

	/*@tab Footer
	@section footer
	@tip You might give your footer a light background color and separate it with a top border
	@theme footer*/
	#footer {
		background-color: #ffffff;
		padding: 20px; /*@editable*/
		font-size: 10px; /*@editable*/
		color: #666666; /*@editable*/
		line-height: 100%; /*@editable*/
		font-family: Verdana;
		text-align: center;
	}

	/*@tab Footer
	@section link style
	@tip Specify a color for your footer hyperlinks.*/
	#footer a { /*@editable*/
		color: #cc6600; /*@editable*/
		text-decoration: underline; /*@editable*/
		font-weight: normal;
	}

	/*@tab Page
	@section link style
	@tip Specify a color for all the hyperlinks in your email.
	@theme link*/
	a, a:link, a:visited { /*@editable*/
		color: #cc6600; /*@editable*/
		text-decoration: underline; /*@editable*/
		font-weight: normal;
	}

	body {
		text-align: center;
	}

	#layout {
		margin: 10px auto;
		text-align: left;
		border-collapse: collapse;
		background-color: #ffffff;
	}

	.rounded {
		margin: 0;
		padding: 0;
		background-color: #666666;
		line-height: 8px;
	}

	#tj-table {
		width: 100%;
		margin: 10px auto;
		padding: 10px 0;
		border-top: 1px solid #ddd;
		border-bottom: 1px solid #ddd;
	}

	#tj-table td {
		text-align: center;
		padding: 8px;
		font-size: 12px;
	}

	#tj-table td strong, #tj-table td a {
		display: block;
		margin-top: 5px;
	}

	#tj-table th {
		text-align: center;
		padding: 8px;
		font-size: 12px;
	}

	#tj-table th strong, #tj-table th a {
		display: block;
		margin-top: 5px;
	}

	#tj-table td img {
		margin-bottom: 5px;
		padding: 4px;
		border: 1px solid #ddd;
		-moz-box-shadow: 2px 2px 2px #ccc;
		box-shadow: 2px 2px 2px #ccc;
		-webkit-box-shadow: 2px 2px 2px #ccc;
	}

	#social, #share {
		text-align: center;
		margin: 8px 0;
		color: #ccc;
	}

	#social strong {
		padding: 0 10px;
	}

	#share {
		margin-top: 15px;
	}

	#can-spam {
		width: 70%;
		margin: auto;
	}

	#can-spam td {
		padding: 10px;
		text-align: left;
		font-size: 12px;
		color: #666;
	}

	#copyright {
		font-size: 10px;
		font-style: italic;
	}
</style>";
