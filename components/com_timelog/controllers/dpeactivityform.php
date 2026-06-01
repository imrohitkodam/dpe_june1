<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

JLoader::import('components.com_timelog.controllers.activityform', JPATH_SITE);

/**
 * TimelogControllerDpeActivityForm class
 *
 * @since  __DEPLOY_VERSION__
 */
class TimelogControllerDpeActivityForm extends TimelogControllerActivityForm
{
	/**
	 * Method to check out an item for editing and redirect to the edit form.
	 *
	 * @param   INT  $key     key
	 * @param   INT  $urlVar  urlVar
	 *
	 * @return void
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	public function edit($key = null, $urlVar = null)
	{
		$app = Factory::getApplication();
		$appendUrl = '';

		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $app->getUserState('com_timelog.edit.activity.id');
		$editId     = $app->input->getInt('id', 0);

		// Set the user id for the user to edit in the session.
		$app->setUserState('com_timelog.edit.activity.id', $editId);

		// Check license exist or not.
		$licenseId   = $app->input->getInt('licence_id', 0);
		$slaActivity = $app->input->getInt('sla_activity', 0);
		$state       = $app->input->get('state');

		if (!empty($licenseId))
		{
			$appendUrl = '&licence_id=' . $licenseId . '&sla_activity=' . $slaActivity . '&state=' . $state;
		}

		// Get the model.
		$model = $this->getModel('ActivityForm', 'TimelogModel');

		// Check out the item
		if ($editId)
		{
			$model->checkout($editId);
		}

		// Check in the previous user.
		if ($previousId)
		{
			$model->checkin($previousId);
		}

		$tmpl = $app->input->getString('tmpl', '');

		// Check template component set or not.
		if (!empty($tmpl))
		{
			$appendUrl .= '&tmpl=' . $tmpl;
		}

		// Redirect to the edit screen.
		$this->setRedirect(Route::_('index.php?option=com_timelog&view=activityform&layout=edit&id='.$editId . $appendUrl, false));
	}

	/**
	 * Method to save a user's profile data.
	 *
	 * @param   INT  $key     key
	 * @param   INT  $urlVar  urlVar
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since  __DEPLOY_VERSION__
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app   = Factory::getApplication();
		$input = $app->input;
		$user  = Factory::getUser();

		$appendUrl = '';
		$model = $this->getModel('ActivityForm', 'TimelogModel');

		// Get the user data.
		$data = $input->get('jform', array(), 'array');
		$data['attachment'] = ($data['attachment'])?$data['attachment']:'NULL';

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			throw new Exception($model->getError(), 500);
		}

		// DPE Hack - Check if you have access timelog for a activity
		JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

		if ((!$data['client_id']) || !($data['license_id']))
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);

			return;
		}

		$slaSlaActivity = SlaSlaActivity::getInstance($data['client_id']);

		if (empty($slaSlaActivity->id))
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);

			return;
		}

		if (property_exists($slaSlaActivity, 'cluster_id'))
		{
			$clusterId = $slaSlaActivity->cluster_id;
		}

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			if (!$clusterId)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			$canCreateTimelog = RBACL::check($user->id, 'com_cluster', 'core.create.logs', 'com_timelog', $clusterId);

			if (!$canCreateTimelog)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}
		}

		/* If min attribute set from xml then it shows 0 as default value
		so setting min attribute after submitting the form */

		$form->setFieldAttribute('hours', 'min', 0);
		$form->setFieldAttribute('min', 'min', 0);

		// Concat hour and min and save in timelog column
		$data['timelog'] = $data['hours'] . ':' . $data['min'];

		// Validate the posted data.
		$data = $model->validate($form, $data);

		// Check license exist or not.
		$licenseId = $input->getInt('licence_id', 0);

		if (!empty($licenseId))
		{
			$appendUrl = '&licence_id=' . $licenseId;
		}

		$tmpl = $input->getString('tmpl', '');

		// Check template component set or not.
		if (!empty($tmpl))
		{
			$appendUrl .= '&tmpl=' . $tmpl;
		}

		
		// DPE Hack to show message  on popup

		$doc = Factory::getDocument();
		$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
		$doc = Factory::getDocument();
		$doc->addScript(Uri::root() . '/media/vendor/jquery/js/jquery.min.js');
		$doc->addScript(Uri::root() . 'media/system/js/messages.min.js');


			// Validation needed if timelog is set to 0
			if ($data['hours'] == 0 && $data['min'] == 0)
			{
				$msg = Text::_('COM_TIMELOG_ZERO_TIMELOG_ERROR');
				?>
				<script type="text/javascript">
					var msg = '<?php echo $msg;?>';
					jQuery('<div id="system-message-container"></div>').appendTo('.com-timelog');
					Joomla.renderMessages({'warning' : [msg]});				
					setTimeout(function() {	window.parent.SqueezeBox.close();
					}, 2000);

				</script>
		<?php return false;	}

		// Check for errors.
		if ($data === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					// $app->enqueueMessage($errors[$i]->getMessage(), 'warning');
					$msg = $errors[$i]->getMessage();
				}
				else
				{
					// $app->enqueueMessage($errors[$i], 'warning');
					$msg = $errors[$i];

				}
			}

			$jform = $input->get('jform', array(), 'ARRAY');

			// Save the data in the session.
			//$app->setUserState('com_timelog.edit.activity.data', $jform);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_timelog.edit.activity.id');
			// $this->setRedirect(Route::_('index.php?option=com_timelog&view=activityform&layout=edit&id=' . $id . $appendUrl, false));
			?>
			<script type="text/javascript">
					var msg = '<?php echo $msg;?>';
					jQuery('<div id="system-message-container"></div>').appendTo('.com-timelog');
					Joomla.renderMessages({'warning' : [msg]});				
					setTimeout(function() {	window.SqueezeBox.close();
						// window.location.href='<?php //echo Route::_('index.php?option=com_timelog&view=activityform&layout=edit&id=' . $id . $appendUrl, false);?>'
					}, 2000);setTimeout(function() {
						window.parent.location.reload();
					},500);
				</script>

			<!-- $this->redirect(); -->
		<?php 

	}

		// Upload File - start -Saving an uploaded file
		$file = $input->files->get('jform', array(), 'array');

		if (!empty($file['attachment']))
		{
			$data['old_media_ids'] = $input->get('oldFiles', array(), 'array');

			$uploadedMediaIds = $model->uploadMedia($file, $data);

			if (!empty($model->getError()))
			{
				$msg = implode(',', $model->getError());
				// $app->enqueueMessage($msg, 'warning');

				// Save the data in the session.
				//$app->setUserState('com_timelog.edit.activity.data', $jform);

				// Redirect back to the edit screen.
				$id = (int) $data['id'];

			//	$this->setRedirect(Route::_('index.php?option=com_timelog&tmpl=component&task=dpeactivityform.edit&id=' . $id . $appendUrl, false));

			?>
			<script type="text/javascript">
					var msg = '<?php echo $msg;?>';
					jQuery('<div id="system-message-container"></div>').appendTo('.com-timelog');
					Joomla.renderMessages({'warning' : [msg]});				
					setTimeout(function() {

						window.SqueezeBox.close();
						// window.location.href='<?php //echo Route::_('/index.php?option=com_timelog&tmpl=component&task=dpeactivityform.edit&id=' . $id . $appendUrl, false);?>'
					}, 1500);setTimeout(function() {
						window.parent.location.reload();
					},500);
				</script>
				<!-- $this->redirect(); -->
			<?php }

			$data['new_media_ids'] = $uploadedMediaIds;
		}

		$now = Factory::getDate();
		$data['modified_date'] = $now->toSql();// DPE HAck can Go IN Core 

		// Upload File - end
		// Attempt to save the data.
		$return = $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			//$this->setMessage(Text::_('COM_TIMELOG_ITEM_SAVE_FAILED'), 'warning');
			$msg = Text::_('COM_TIMELOG_ITEM_SAVE_FAILED');
			$type = 'warning';
		}
		else
		{
			$id = $model->getState('activity.id');

			// $this->setMessage(Text::_('COM_TIMELOG_ITEM_SAVED_SUCCESSFULLY'), 'success');
			$msg = Text::_('COM_TIMELOG_ITEM_SAVED_SUCCESSFULLY');
			$type ='success';
		}
		//$app->setUserState('com_timelog.edit.activity.data', null);
		?>
			<script type="text/javascript">
					var msg  = '<?php echo $msg;?>';
					var type = '<?php echo $type;?>';
					jQuery('<div id="system-message-container"></div>').appendTo('.com-timelog');
					Joomla.renderMessages({[type] : [msg]});				
					setTimeout(function() {	window.parent.SqueezeBox.close();
						// window.location.href='<?php //echo Route::_('index.php?option=com_timelog&task=dpeactivityform.edit&id=' . $id . $appendUrl, false);?>'
					}, 1000);setTimeout(function() {
						window.parent.location.reload();
					},500);
				</script>

<?php 
		// $this->setRedirect(Route::_('index.php?option=com_timelog&task=dpeactivityform.edit&id=' . $id . $appendUrl, false));

		// Flush the data from the session.
		$app->setUserState('com_timelog.edit.activity.data', null);
	}

	/**
	 * Method to abort current operation
	 *
	 * @param   INT  $key  key
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function cancel($key = null)
	{
		$app = Factory::getApplication();
		$appendUrl = '';

		// Get the current edit id.
		$editId = (int) $app->getUserState('com_timelog.edit.activity.id');

		// Get the model.
		$model = $this->getModel('ActivityForm', 'TimelogModel');

		// Check in the item
		if ($editId)
		{
			$model->checkin($editId);
		}

		// Check license exist or not.
		$licenseId = $app->input->getInt('licence_id', 0);

		if (!empty($licenseId))
		{
			$appendUrl = '&licence_id=' . $licenseId;
		}

		$tmpl = $app->input->getString('tmpl', '');

		// Check template component set or not.
		if (!empty($tmpl))
		{
			$appendUrl .= '&layout=activities&tmpl=' . $tmpl;
		}

		$menu = $app->getMenu();
		$item = $menu->getActive();
		$url  = (empty($item->link) ? 'index.php?option=com_timelog&view=activities' . $appendUrl : $item->link);
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Method to remove data
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function remove()
	{
		$app   = Factory::getApplication();
		$appendUrl = '';
		$model = $this->getModel('ActivityForm', 'TimelogModel');
		$pk    = $app->input->getInt('id');

		// Check license exist or not.
		$licenseId   = $app->input->getInt('licence_id', 0);
		$slaActivity = $app->input->getInt('sla_activity', 0);

		if (!empty($licenseId))
		{
			$appendUrl = '&licence_id=' . $licenseId . '&sla_activity=' . $slaActivity;
		}

		$tmpl = $app->input->getString('tmpl', '');

		// Check template component set or not.
		if (!empty($tmpl))
		{
			$appendUrl .= '&layout=activities&tmpl=' . $tmpl;
		}

		// Attempt to save the data
		try
		{
			$return = $model->delete($pk);

			// Check in the profile
			$model->checkin($return);

			// Clear the profile id from the session.
			$app->setUserState('com_timelog.edit.activity.id', $pk);

			$menu = $app->getMenu();
			$item = $menu->getActive();

			$url = (!empty($item->link) ? 'index.php?option=com_timelog&view=activities' . $appendUrl : $item->link);

			if( !$item->link )
			{
				$url = 'index.php?option=com_timelog&view=activities&layout=activities&tmpl=component'.$appendUrl.'&state=1';
			}
			
			// Redirect to the list screen
			$this->setMessage(Text::_('COM_TIMELOG_ITEM_DELETED_SUCCESSFULLY'));
			$this->setRedirect(Route::_($url, true));

			// Flush the data from the session.
			 $app->setUserState('com_timelog.edit.activity.data', null);

		}
		catch (Exception $e)
		{
			$errorType = ($e->getCode() == '404') ? 'error' : 'warning';
			$this->setMessage($e->getMessage(), $errorType);
			$this->setRedirect('index.php?option=com_timelog&view=activities' . $appendUrl);
		}
	}
}
