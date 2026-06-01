<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

/**
 * TjGoPhish Group Controller
 *
 * @since  1.0.0
 */
class TjGoPhishControllerGroup extends FormController
{
	/**
	 * Implement to allowAdd or not
	 *
	 * @param   array  $data  Data for the form.
	 *
	 * @return bool
	 */
	protected function allowAdd($data = Array())
	{
		return Factory::getUser()->authorise("core.create", "com_tjgophish");
	}

	/**
	 * Implement to allow edit or not
	 *
	 * @param   array  $data  Data for the form.
	 *
	 * @param   INT    $key   Id.
	 *
	 * @return bool
	 */
	protected function allowEdit($data = Array(), $key = 'id')
	{
		return Factory::getUser()->authorise("core.edit", "com_tjgophish");
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
	 * @since  1.0.0
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Get Logged in User
		$user = Factory::getUser();

		// Initialise variables.
		$app   = Factory::getApplication();
		$menu           = $app->getMenu();
		$groupsMenuItem = $menu->getItems('link', 'index.php?option=com_tjgophish&view=groups', true);
		$groupMenuItem  = $menu->getItems('link', 'index.php?option=com_tjgophish&view=group&layout=edit', true);
		$tmplVar = $app->input->get('tmpl', '', 'string');
		$tmpl = $tmplVar ? '&tmpl=' . $tmplVar : '';

		$model = $this->getModel('Group', 'TjGoPhishModel');

		// Get the user data.
		$data = $app->input->get('jform', array(), 'array');

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			throw new Exception($model->getError(), 500);
		}

		// Validate the posted data.
		$data = $model->validate($form, $data);

		// Get com_cluster component status
		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			// Get com_subusers component status
			$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;

			// Check user have permission to edit record of assigned cluster
			if ($subUserExist && ($data['cluster_id']) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

				/*
				 *  @Todo migration for client 'com_cluster' in dpe
				 *  Com_dpe - Hack - start
				 *  Original Code : RBACL::check($user->id, 'com_cluster', 'core.addItem', 'com_tjucm', $clusterId)
				 */

				// Check user has permission for mentioned cluster
				if (!RBACL::check($user->id, 'com_cluster', 'core.createGroup', 'com_tjgophish', $data['cluster_id']))
				{
					$this->setMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

					$this->setRedirect(Route::_('index.php?option=com_tjgophish&view=groups&Itemid=' . $groupsMenuItem->id, false));

					return false;
				}
			}
		}

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
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			$input = $app->input;
			$jform = $input->get('jform', array(), 'ARRAY');

			// Save the data in the session.
			$app->setUserState('com_tjgophish.edit.group.data', $jform);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tjgophish.edit.group.id');
			$this->setRedirect(Route::_('index.php?option=com_tjgophish&view=group&layout=edit&id=' . $id, false));

			$this->redirect();
		}

		// Attempt to save the data.
		$return = $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			if ($tmplVar == 'component')
			{		
				$doc = Factory::getDocument();
				$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
				// Added to load added group in dropdown without refresh the page
				?>			   
				 <script type="text/javascript" src="<?php echo Uri::root(true).'/media/vendor/jquery/js/jquery.min.js'?>"></script>
					<script type="text/javascript">
						var msg = '<?php echo Text::sprintf('COM_TJGOPHISH_GROUP_SAVE_FAILED', $model->getError());?>';
						jQuery('<div id="system-message-container"></div>').appendTo('.com-tjgophish');
						Joomla.renderMessages({'error' : [msg]});				

						setTimeout(function() {
							window.location.href= '<?php echo 'index.php?option=com_tjgophish&view=group&layout=edit_popup&tmpl=component&id=' . $id; ?>';
						}, 2000);
					</script>
				<?php
				// Flush the data from the session.
				$app->setUserState('com_tjgophish.edit.group.data', null);
			}
			else
			{
				// Save the data in the session.
				$app->setUserState('com_tjgophish.edit.group.data', $data);

				// Redirect back to the edit screen.
				$id = (int) $app->getUserState('com_tjgophish.edit.group.id');
				$this->setMessage(Text::sprintf('COM_TJGOPHISH_GROUP_SAVE_FAILED', $model->getError()), 'error');

				if ($app->input->get('layout') == "edit_popup")
				{
					$defaultURl = 'index.php?option=com_tjgophish&view=group&layout=edit_popup&tmpl=component&id=' . $id;
				}
				else
				{
					$defaultURl = 'index.php?option=com_tjgophish&view=group&layout=edit&id=' . $id;
				}

				$this->setRedirect(Route::_($defaultURl, false));

				return false;
			}
			
		}
		else
		{
			if ($tmplVar == 'component')
			{		
			$doc = Factory::getDocument();
			$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
			// Added to load added group in dropdown without refresh the page
			?>			   
			 <script type="text/javascript" src="<?php echo Uri::root(true).'/media/vendor/jquery/js/jquery.min.js'?>"></script>
				<script type="text/javascript">
					var msg = '<?php echo Text::_("COM_TJGOPHISH_GROUP_ADDED_SUCCESSFULLY");?>';
					jQuery('<div id="system-message-container"></div>').appendTo('.com-tjgophish');
					Joomla.renderMessages({'message' : [msg]});				
					window.parent.updateChzn();
					setTimeout(function() {
						parent.SqueezeBox.close();
					}, 2000);
				</script>
			<?php
			// Flush the data from the session.
			$app->setUserState('com_tjgophish.edit.group.data', null);
		}
		else
		{
				if ($return)
				{
					$this->setMessage(Text::_('COM_TJGOPHISH_GROUP_ADDED_SUCCESSFULLY'), 'success');
				}

				$id = (int) $model->getState('group.id');

				// Clear the profile id from the session.
				$app->setUserState('com_tjgophish.edit.group.id', null);

				$task = $this->getTask();

				if ($app->input->get('layout') == "edit_popup")
				{
					$defaultURl = 'index.php?option=com_tjgophish&view=group&layout=edit_popup&tmpl=component&id=' . $id;
				}
				else
				{
					$defaultURl = 'index.php?option=com_tjgophish&view=group&layout=edit&id=' . $id . '&Itemid=' . $groupMenuItem->id;
				}

				// Redirect the user and adjust session state based on the chosen task.
				switch ($task)
				{
					case 'save':
						// Redirect back to the List screen.
						$this->setRedirect(Route::_('index.php?option=com_tjgophish&view=groups&Itemid=' . $groupsMenuItem->id, false));
						break;

					default:

						// Redirect back to the edit screen.
						$this->setRedirect(Route::_($defaultURl, false));
						break;
				}

				// Flush the data from the session.
				$app->setUserState('com_tjgophish.edit.group.data', null);
			}
		}
		
	}
}
