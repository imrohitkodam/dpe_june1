<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;

class plgContentEbCategory extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	public function onContentPrepare($context, &$article, &$params, $limitstart = 0)
	{
		if (!str_contains($article->text, '{ebcategory'))
		{
			return true;
		}

		$regex         = "#{ebcategory (\d+)}#s";
		$article->text = preg_replace_callback($regex, [&$this, 'displayEvents'], $article->text);

		return true;
	}

	/**
	 * Display events from a category
	 *
	 * @param $matches
	 *
	 * @return string
	 * @throws Exception
	 */
	private function displayEvents($matches)
	{
		// Require library + register autoloader
		require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/bootstrap.php';

		EventbookingHelper::loadLanguage();
		$categoryId = (int) $matches[1];
		$request    = [
			'option'    => 'com_eventbooking',
			'view'      => 'category',
			'id'        => $categoryId,
			'hmvc_call' => 1,
			'Itemid'    => $this->params->get('menu_item_id', EventbookingHelper::getItemid()),
		];

		$appInput   = $this->app->getInput();
		$start      = $appInput->get->getInt('start', 0);
		$limitStart = $appInput->get->getInt('limitstart', 0);
		if ($start && !$limitStart)
		{
			$limitStart = $start;
		}
		$request['limitstart'] = $limitStart;

		$input  = new RADInput($request);
		$config = require JPATH_ADMINISTRATOR . '/components/com_eventbooking/config.php';
		ob_start();

		//Initialize the controller, execute the task
		RADController::getInstance('com_eventbooking', $input, $config)
			->execute();

		return '<div class="clearfix"></div>' . ob_get_clean();
	}

	/**
	 * Override registerListeners method to only register listeners if needed
	 *
	 * @return void
	 */
	public function registerListeners()
	{
		if (!ComponentHelper::isEnabled('com_eventbooking'))
		{
			return;
		}

		if (!$this->app->isClient('site'))
		{
			return;
		}

		parent::registerListeners();
	}
}
