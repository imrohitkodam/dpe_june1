<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_JLike
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View to edit
 *
 * @since  1.6
 */
class JlikeViewPath extends JViewLegacy
{
	protected $state;

	protected $item;

	protected $form;

	protected $params;

	protected $app;

	protected $user;

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
		$this->app  = JFactory::getApplication();
		$this->user = JFactory::getUser();

		$this->state  = $this->get('State');
		$this->item   = $this->get('Item');
		$this->params = $this->app->getParams('com_jlike');

		if (!empty($this->item))
		{
			$this->form = $this->get('Form');
		}

		if (!$this->user->id)
		{
			$current = JUri::getInstance()->toString();
			$url     = base64_encode($current);
			$this->app->redirect(JRoute::_('index.php?option=com_users&view=login&return=' . $url, false));
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		if ($this->_layout == 'edit')
		{
			if ($this->user->authorise('core.create', 'com_jlike') !== true)
			{
				throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
			}
		}

		parent::display($tpl);
	}
}
