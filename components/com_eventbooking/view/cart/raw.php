<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Registry\Registry;

class EventbookingViewCartRaw extends RADViewHtml
{
	/**
	 * The events added to cart
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * The component config data
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * The last accessed Category ID
	 *
	 * @var int
	 */
	protected $categoryId;

	/**
	 * The javascript string
	 *
	 * @var string
	 */
	protected $jsString;

	/**
	 * The bootstrap helper class
	 *
	 * @var EventbookingHelperBootstrap
	 */
	protected $bootstrapHelper;

	/**
	 * Events data which are being displayed in module
	 *
	 * @var array
	 */
	protected $rows;

	/**
	 * Set data and display the view
	 *
	 * @return void
	 */
	public function display()
	{
		$this->displayModule();
	}

	/**
	 * Display content of cart module, using for ajax request
	 */
	protected function displayModule()
	{
		$module = ModuleHelper::getModule('mod_eb_cart');
		$params = new Registry($module->params);

		if ($params->get('item_id'))
		{
			$Itemid = $params->get('item_id');
		}
		else
		{
			$Itemid = $this->input->getInt('Itemid');
		}

		$cart         = new EventbookingHelperCart();
		$rows         = $cart->getEvents();
		$this->rows   = $rows;
		$this->config = EventbookingHelper::getConfig();
		$this->Itemid = $Itemid;

		parent::display();
	}
}
