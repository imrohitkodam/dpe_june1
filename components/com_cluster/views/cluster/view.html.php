<?php
/**
 * @package    Com_Cluster
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;

jimport('joomla.application.component.view');

/**
 * View to edit
 *
 * @since  1.0.0
 */
class ClusterViewCluster extends HtmlView
{
	protected $item;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->item = $this->get('Item');
		$this->state = $this->get('State');
		$this->item->id = $this->state->get('cluster.id');

		// @Todo - Add permission based view accessing code
		parent::display($tpl);
	}
}
