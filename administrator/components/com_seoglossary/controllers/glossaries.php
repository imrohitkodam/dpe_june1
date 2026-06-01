<?php
/**    
 * SEO Glossary Glossaries controller
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Glossaries list controller class.
 */
class SeoglossaryControllerGlossaries extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function &getModel($name = 'glossary', $prefix = 'SeoglossaryModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
	
	public function saveorder()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Get the arrays from the Request
		$order = $this->input->post->get('order', null, 'array');
		$originalOrder = explode(',', $this->input->getString('original_order_values'));

		// Make sure something has changed
		if (!($order === $originalOrder))
		{
			parent::saveorder();
		}
		else
		{
			// Nothing to reorder
			$this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list, false));
			return true;
		}
	}
	
	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function saveOrderAjax()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Get the arrays from the Request
		$pks   = $this->input->post->get('cid', null, 'array');
		$order = $this->input->post->get('order', null, 'array');
		$originalOrder = explode(',', $this->input->getString('original_order_values'));

		// Make sure something has changed
		if (!($order === $originalOrder)) {
			// Get the model
			$model = $this->getModel();
			// Save the ordering
			$return = $model->saveorder($pks, $order);
			if ($return)
			{
				echo "1";
			}
		}
		// Close the application
		JFactory::getApplication()->close();
	}
}