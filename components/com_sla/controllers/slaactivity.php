<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;


/**
 * The sla activity controller
 *
 * @since  1.0.0
 */
class SlaControllerSlaActivity extends FormController
{
	/**
	 * Method to save activity record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   1.0
	 */
	public function save($key = 'id', $urlVar = 'id')
	{
		// Check for request forgeries.
		$this->checkToken();

		// Initialise variables.
		$app       = Factory::getApplication();
		$model     = $this->getModel();
		$user      = Factory::getUser();
		$clusterId = 0;
		$recordId = $this->input->getInt('id');

		// Get the activity data.
		$data = $app->input->get('jform', array(), 'array');

		// Validate the posted data.
		$form = $model->getForm($data, false);
		
		//DPE Hack
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sla/tables');
        $slaActivitiesTable = Table::getInstance('SlaActivities', 'SlaTable');
        $slaActivitiesTable->load(array('id'=>$data['id']));

		if (!$data['license_id'] || (!$user->id))
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

		// Get SLA details
		$slaClusterXrefTable = SlaFactory::table("slaclusterxrefs");
		$slaClusterXrefTable->load(array('license_id' => $data['license_id']));

		if (property_exists($slaClusterXrefTable, 'cluster_id'))
		{
			$clusterId = $slaClusterXrefTable->cluster_id;
		}

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			if (!$clusterId)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			// DPE hack to check permission
			$canCreateActivity = RBACL::check($user->id, 'com_cluster', 'core.create.activity', 'com_sla', $clusterId);

			if (!$canCreateActivity)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}
		}

		if (!$form)
		{
			throw new \Exception($model->getError(), 500);
		}

		$validData = $model->validate($form, $data);

		// Check for validation errors.
		if ($validData === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			if (!empty($errors))
			{
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
			}

			// Redirect back to the registration screen.
			$this->setRedirect(Route::_('index.php?option=com_sla&view=slaactivity' . $this->getRedirectToItemAppend($recordId, $urlVar), false));

			return false;
		}
		$now = new DateTime();
		$createdOn = $now->format("Y-m-d H:i:s");

		// created_on
		$validData['prev_due_date'] = $data['prev_due_date'];
		$validData['created_on'] = ($slaActivitiesTable->created_on !== '0000-00-00 00:00:00') ? $slaActivitiesTable->created_on : $createdOn;

		$slaSlaActivity = $model->save($validData);


		// Check for errors.
		if (empty($slaSlaActivity->id))
		{
			$this->setMessage(Text::_('COM_SLA_ACTIVITY_SAVE_FAILED'), 'warning');
			$msg = array('msg'=>Text::_('COM_SLA_ACTIVITY_SAVE_FAILED'), 'type'=>'warning');
		}
		else
		{
			$this->setMessage(Text::_('COM_SLA_ACTIVITY_ADDED_SUCCESSFULLY'), 'success');
			$msg = array('msg'=>Text::_('COM_SLA_ACTIVITY_ADDED_SUCCESSFULLY'), 'type'=>'success');
		}

			$doc = Factory::getDocument();
			$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
			// Added to load added group in dropdown without refresh the page
			?>			   
			 <script type="text/javascript" src="<?php echo Uri::root(true).'/media/vendor/jquery/js/jquery.min.js'?>"></script>
			 <script type="text/javascript" src="<?php echo Uri::root(true).'/media/system/js/messages.min.js'?>"></script>
				<script type="text/javascript">

					var msg = '<?php echo $msg["msg"];?>';
					var type = '<?php echo $msg["type"];?>';

					jQuery('<div id="system-message-container"></div>').appendTo('.com-sla');
					Joomla.renderMessages({[type] : [msg]});
					setTimeout(function() {window.parent.SqueezeBox.close();
						//window.location.href= "<?php //echo 'index.php?option=com_sla&view=slaactivity&layout=edit&tmpl=component&id=' . $slaSlaActivity->id; ?>";

					}, 1200); setTimeout(function() {
						window.parent.location.reload();
					},500);
				</script>
		<?php 
		// $redirectURl = 'index.php?option=com_sla&view=slaactivity&layout=edit&tmpl=component&id=' . $slaSlaActivity->id;
		// $this->setRedirect(Route::_($redirectURl . '&licence_id=' . $validData['license_id'], false));

		return true;
	}
}

?>