<?php
/**
 * @version    SVN: <svn_id>
 * @package    TMT
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2012-2013 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

jimport('joomla.database.database');
jimport('joomla.application.component.helper');

/**
 * Frontend helper Class. Provides some commonly used functions.
 *
 * @since  1.0
 */
Class TmtFrontendHelper
{
	/**
	 * Get ItemId function
	 *
	 * @param   string   $link          URL to find itemid for
	 *
	 * @param   integer  $skipIfNoMenu  return 0 if no menu is found
	 *
	 * @return  integer  $itemid
	 */
	public function getItemId($link, $skipIfNoMenu = 0)
	{
		$itemid = 0;
		$mainframe = Factory::getApplication();

		if ($mainframe->isClient('site'))
		{
			$menu = $mainframe->getMenu();
			$items = $menu->getItems('link', $link);

			if (isset($items[0]))
			{
				$itemid = $items[0]->id;
			}
		}

		if (!$itemid)
		{
			$db = Factory::getDBO();

			if (JVERSION >= 3.0)
			{
				$query = "SELECT id FROM #__menu
				WHERE link LIKE '%" . $link . "%'
				AND published =1
				LIMIT 1";
			}
			else
			{
				$query = "SELECT id FROM " . $db->nameQuote('#__menu') . "
				WHERE link LIKE '%" . $link . "%'
				AND published =1
				ORDER BY ordering
				LIMIT 1";
			}

			$db->setQuery($query);
			$itemid = $db->loadResult();
		}

		if (!$itemid)
		{
			if ($skipIfNoMenu)
			{
				$itemid = 0;
			}
			else
			{
				$jinput = $mainframe->input;
				$itemid = $jinput->get('Itemid', 0, 'INTEGER');
			}
		}

		return $itemid;
	}

	/**
	 * This function return array of js files which is loaded from tjassesloader plugin.
	 *
	 * @param   array  &$jsFilesArray                  Js file's array.
	 * @param   int    &$firstThingsScriptDeclaration  load script 1st
	 *
	 * @return   ARRAY  $jsFilesArray All JS files to be load
	 *
	 * @since  1.0.0
	 */
	public function getTmtJsFiles(&$jsFilesArray, &$firstThingsScriptDeclaration)
	{
		$app    = Factory::getApplication();
		$input  = Factory::getApplication()->input;
		$option = $input->get('option', '');
		$view   = $input->get('view', '');
		$layout = $input->get('layout', '');

		$config = Factory::getConfig();
		$debug = $config->get('debug');

		$loadminifiedJs = '';

		if ($debug == 0)
		{
			$loadminifiedJs = '.min';
		}

		// Backend Js files
		if ($app->isClient('administrator'))
		{
			if ($option == "com_tmt")
			{
				$jsFilesArray[] = 'administrator/components/com_tmt/assets/js/tmt.js';
				$jsFilesArray[] = 'media/com_tjlms/js/common.js';

				// Load the view specific js
				switch ($view)
				{
					// Admin test view
					case "test":
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjlmsvalidator.js';
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/jquery.form.js';
					break;

					case "question":
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjlmsvalidator.js';
					break;
				}
			}
		}
		else
		{
			if ($option == 'com_tmt')
			{
				$jsFilesArray[] = 'media/com_tjlms/js/common.js';

				switch ($view)
				{
					case "test":
							// $jsFilesArray[] = 'components/com_tmt/assets/js/jquery.countdown' . $loadminifiedJs . '.js';
					break;

					case "answersheet":
						$jsFilesArray[] = 'components/com_tjlms/assets/js/tjlms.js';
						$jsFilesArray[] = 'components/com_tjlms/assets/js/tjService.js';
				}
			}
		}

		$reqURI = Uri::root();

		// If host have wwww, but Config doesn't.
		if (isset($_SERVER['HTTP_HOST']))
		{
			if ((substr_count($_SERVER['HTTP_HOST'], "www.") != 0) && (substr_count($reqURI, "www.") == 0))
			{
				$reqURI = str_replace("://", "://www.", $reqURI);
			}
			elseif ((substr_count($_SERVER['HTTP_HOST'], "www.") == 0) && (substr_count($reqURI, "www.") != 0))
			{
				// Host do not have 'www' but Config does
				$reqURI = str_replace("www.", "", $reqURI);
			}
		}

		// Defind first thing script declaration.
		$loadFirstDeclarations          = "var root_url = '" . $reqURI . "';";
		$firstThingsScriptDeclaration[] = $loadFirstDeclarations;

		return $jsFilesArray;
	}

	/**
	 * This function return array of css files which is loaded from tjassesloader plugin.
	 *
	 * @param   array  &$cssFilesArray  Css file's array.
	 *
	 * @return   ARRAY  $cssFilesArray All Css files to be load
	 *
	 * @since  1.0.0
	 */
	public function getTmtCssFiles(&$cssFilesArray)
	{
		$app    = Factory::getApplication();
		$input  = Factory::getApplication()->input;
		$option = $input->get('option', '');
		$view   = $input->get('view', '');
		$layout = $input->get('layout', '');
		$extension = $input->get('extension', '');

		$config = Factory::getConfig();
		$debug = $config->get('debug');

		$loadminifiedCss = '';

		if ($debug == 0)
		{
			$loadminifiedCss = '.min';
		}

		// Backend Css files
		if ($app->isClient('administrator'))
		{
			if ($option == "com_tmt")
			{
				$cssFilesArray[] = 'media/com_tjlms/css/common.css';
				$cssFilesArray[] = 'media/com_tjlms/font-awesome/css/font-awesome.min.css';
				$cssFilesArray[] = 'media/com_tjlms/css/tjlms_backend.css';
				$cssFilesArray[] = 'media/com_tmt/css/tmt_backend.css';
				$cssFilesArray[] = 'media/com_tjlms/vendors/artificiers/artficier.css';

				if ($view == 'question' || $view == 'test')
				{
					$cssFilesArray[] = 'media/com_tmt/css/tmt.css';
				}
			}
		}
		else
		{
			if ($option == "com_tmt")
			{
				$cssFilesArray[] = 'media/com_tjlms/css/common.css';
				$cssFilesArray[] = 'media/com_tjlms/font-awesome/css/font-awesome.min.css';
				$cssFilesArray[] = 'components/com_tmt/assets/css/tmt' . $loadminifiedCss . '.css';

				if ($view == 'test')
				{
					$cssFilesArray[] = 'components/com_tmt/assets/css/jquery.countdown' . $loadminifiedCss . '.css';
				}
			}
		}

		return $cssFilesArray;
	}
}
