<?php
/**
 * @version     site/views/default/view.json.php 2020-05-28 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Application\CmsApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

defined('_JEXEC') or die;
defined('WATCHFULLI_PATH') or die;

class WatchfulliViewDefault extends HtmlView
{
	public function display($tpl = null)
	{
		$send = new WatchfulliSend();
		if (defined('WATCHFUL_REMOTE_DEBUG'))
		{
			print_r($send->getData());
		}
		else
		{
			echo '{wcode}' . json_encode($send->getData()) . '{/wcode}';
		}

		/** @var CmsApplication $app */
		$app = Factory::getApplication();
		$app->close();
	}
}
