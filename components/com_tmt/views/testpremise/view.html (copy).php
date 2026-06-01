<?php

/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View to edit
 */
class TmtViewTestpremise extends JViewLegacy {

	protected $state;
	protected $item;
	protected $form;
	protected $params;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		$app=JFactory::getApplication();
		$user=JFactory::getUser();
		$tmtFrontendHelper=new tmtFrontendHelper();

		//check if user is logged in
		if (! $user->id)
		{
			$msg=JText::_('COM_TMT_MESSAGE_LOGIN_FIRST');

			// Get curent url
			$current=JUri::getInstance()->toString();
			$url=base64_encode($current);
			$app->redirect(JRoute::_('index.php?option=com_users&view=login&return='.$url,false),$msg);
		}

		$this->item = $this->get('Data');

		//print_r($this->item); die;

		// Check if user has access to quiz/test
		if (! isset($this->item->invite_id) )
		{
			JError::raise(E_WARNING, $code=503, JText::_('COM_TMT_MESSAGE_NO_ACL_PERMISSION'), $info='');
			return false;
		}

/*
		// if logged in user does not match invited user id
		if ( $user->id != $this->item->invite_data->candidate_id )
		{
			JError::raise(E_WARNING, $code=503, JText::_('COM_TMT_MESSAGE_NO_ACL_PERMISSION'), $info='');
			return false;
		}

		// Do not allow taking test for jobs not open (Approved)
		if ( $this->item->job_data->status != 'Approved')
		{
			$tmtFrontendHelper=new tmtFrontendHelper();
			$itemid = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=mytestinv');
			$redirect_link = JRoute::_('index.php?option=com_tmt&view=mytestinv&Itemid='.$itemid, false);
			$msg = JText::_('COM_TMT_MESSAGE_JOB_NOT_OPEN');
			$app->redirect($redirect_link, $msg);
		}
*/
		$this->form=$this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors));
		}

		// Get itemid
//		$this->company_details_itemid = $tmtFrontendHelper->getItemId('index.php?option=com_jajobboard&view=japrofiles&layout=jaview&cid');
//s		$this->job_details_itemid = $tmtFrontendHelper->getItemId('index.php?option=com_jajobboard&view=jajobs&layout=jaview&cid');

		$this->_prepareDocument();
		parent::display($tpl);
	}


	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
	}

}
