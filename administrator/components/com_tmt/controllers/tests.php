<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Tests list controller class.
 */
class TmtControllerTests extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'test', $prefix = 'TmtModel', $config = Array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}

	/**
	 * Method to redirect to user dashboard
	 *
	 * @since   1.0
	 */
	function backToDashboard()
	{
		$tmtFrontendHelper=new tmtFrontendHelper();		//@TODO change the redirect of the view to LMS related views
		$itemid=$tmtFrontendHelper->getItemId('index.php?option=com_subusers&view=userview');
		$redirect=JRoute::_('index.php?option=com_subusers&view=userview&Itemid='.$itemid,false);
		$this->setRedirect($redirect,'');
	}

	/**
	 * Method to redirect to create question form
	 *
	 * @since   1.0
	 */
	function create()
	{
		$tmtFrontendHelper=new tmtFrontendHelper();
		$itemid=$tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=test');
		$redirect=JRoute::_('index.php?option=com_tmt&view=test&Itemid='.$itemid,false);
		$this->setRedirect($redirect,'');
	}

	/**
	 * Method to redirect to create question form
	 *
	 * @since   1.0
	 */
	function assign()
	{
		// Get all form data
		$data=JFactory::getApplication()->input->post;
		$passdata=array();
		$course_id=$data->get('course_id','','INT');
		$mod_id=$data->get('mod_id','','INT');
		$addquiz = JFactory::getApplication()->input->get('addquiz', '', 'INT');
		$quiz_id=$data->get('quiz_id','','INT');
		$passdata['addquiz']=$addquiz;
		$passdata['course_id']=$course_id;
		$passdata['mod_id']=$mod_id;
		$passdata['quiz_id']=$quiz_id;

		$testmodel=$this->getModel('test');
		$testmodel->assignTest2Lesson($passdata);
		echo '<script type="text/javascript">
		/*parent.SqueezeBox.close();*/
		parent.location.reload();
		</script>';
//		die;
//		$tmtFrontendHelper=new tmtFrontendHelper();
//		$itemid=$tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=test');
//		$redirect=JRoute::_('index.php?option=com_tmt&view=test&Itemid='.$itemid,false);
//		$redirect=JRoute::_('index.php?option=com_tjlms&view=tjmodules&course_id='.$passdata['course_id'].'&mod_id='.$passdata['mod_id'],false);
//		$this->setRedirect($redirect,'');
	}

	/**
	 * Method to publish selected tests
	 *
	 * @since   1.0
	 */
	function publish1()		//NOT NEEDED
	{
		$input=JFactory::getApplication()->input;

		// Get question ids to publish
		$cid=$input->get('cid','', 'array');
		JArrayHelper::toInteger($cid);

		// Call model function
		$model=$this->getModel('tests');
		$successCount=$model->setItemState($cid,1);

		// Show success / error message & redirect
		if($successCount)
		{
			if($successCount >1)
				$msg=JText::sprintf(JText::_('COM_TMT_TESTS_PUBLISHED'),$successCount);
			else
				$msg=JText::sprintf(JText::_('COM_TMT_TEST_PUBLISHED'),$successCount);
		}else{
			$msg=JText::_('COM_TMT_TEST_ERROR_PUBLISH').'</br>'.$model->getError();
		}

		$tmtFrontendHelper=new tmtFrontendHelper();
		$itemid=$tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=tests');
		$redirect=JRoute::_('index.php?option=com_tmt&view=tests&Itemid='.$itemid,false);

		$this->setMessage($msg);
		$this->setRedirect($redirect);
	}

		/**
	 * Method to unpublish selected tests
	 *
	 * @since   1.0
	 */
	function unpublish1()	//NOT NEEDED
	{
		$input=JFactory::getApplication()->input;

		// Get question ids to unpublish
		$cid=$input->get('cid','', 'array');
		JArrayHelper::toInteger($cid);

		// Call model function
		$model=$this->getModel('tests');
		$successCount=$model->setItemState($cid,0);

		// Show success / error message & redirect
		if($successCount)
		{
			if($successCount >1)
				$msg=JText::sprintf(JText::_('COM_TMT_TESTS_UNPUBLISHED'),$successCount);
			else
				$msg=JText::sprintf(JText::_('COM_TMT_TEST_UNPUBLISHED'),$successCount);
		}else{
			$msg=JText::_('COM_TMT_TEST_ERROR_UNPUBLISH').'</br>'.$model->getError();
		}

		$tmtFrontendHelper=new tmtFrontendHelper();
		$itemid=$tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=tests');
		$redirect=JRoute::_('index.php?option=com_tmt&view=tests&Itemid='.$itemid,false);

		$this->setMessage($msg);
		$this->setRedirect($redirect);
	}

	/**
	 * Method to delete selected tests
	 *
	 * @since   1.0
	 */
	function delete()
	{
		$input=JFactory::getApplication()->input;
		$app = JFactory::getApplication();

		// Get category ids to delete
		$cid=$input->get('cid','', 'array');
		JArrayHelper::toInteger($cid);

		// Call model function
		$model=$this->getModel('tests');
		$successCount=$model->delete($cid);

		// Show success / error message & redirect
		if($successCount)
		{

			if($successCount >1)
			{
				$msg=JText::sprintf(JText::_('COM_TMT_TESTS_DELETED'),$successCount);
			}
			else
			{
				$msg=JText::sprintf(JText::_('COM_TMT_TEST_DELETED'),$successCount);
			}

			$app->enqueueMessage($msg);
		}
		else
		{
			$msg=JText::_('COM_TMT_TEST_ERROR_DELETE').'</br>'.$model->getError();
			$app->enqueueMessage($msg, 'error');
		}

		$tmtFrontendHelper=new tmtFrontendHelper();
		$itemid=$tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=tests');
		$redirect=JRoute::_('index.php?option=com_tmt&view=tests&Itemid='.$itemid,false);
		$this->setRedirect($redirect);
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
		// Get the input
		$input = JFactory::getApplication()->input;
		$pks = $input->post->get('cid', array(), 'array');
		$order = $input->post->get('order', array(), 'array');

		// Sanitize the input
		JArrayHelper::toInteger($pks);
		JArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		JFactory::getApplication()->close();
	}



}
