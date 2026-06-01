<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Table\Table;

JLoader::import('components.com_tjcertificate.includes.tjcertificate', JPATH_ADMINISTRATOR);
JLoader::import('components.com_tjlms.includes.tjlms', JPATH_ADMINISTRATOR);
JLoader::import('components.com_tjlms.models.courses', JPATH_SITE);
JLoader::import('components.com_multiagency.includes.multiagency', JPATH_SITE);
JLoader::import('components.com_tjucm.includes.tjucm', JPATH_SITE);
JLoader::import('components.com_tjlms.helpers.courses', JPATH_SITE);
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);

/**
 * View to edit
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeViewannualreport extends HtmlView
{
	protected $state;

	protected $item;

	protected $params;

	protected $clusterId;

	

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

		$app        = Factory::getApplication();
		$input      = Factory::getApplication()->input;
		$user = Factory::getUser();

		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters         = $clusterUserModel->getUsersClusters($user->id);
		$clusterIds = array_map(function($item) {
		    return $item->cluster_id;
		}, $clusters);
		if (!$user->id)
		{
			$msg = Text::_('COM_TJUCM_LOGIN_MSG');

			// Get current url.
			$current = Uri::getInstance()->toString();
			$url = base64_encode($current);
			Factory::getApplication()->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, Text::_('COM_TJUCM_LOGIN_MSG')));
		}

		$this->state = $this->get('State');
		
		if($input->get('id', '', 'INT'))
		{
			$reportId = $input->get('id', '', 'INT');
			$this->state->set('filter.id',$reportId);
		}

		$this->form = $this->get('Form');
        $this->items = $this->get('Item'); // Get existing item data
        $assignedDPO = json_decode($this->items->section_filters)->jform_leadConsultantDropdow;
        Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_dpe/tables');
	    $annualreportTable = Table::getInstance('Annualreport', 'DpeTable');
	    $annualreportTable->load(array('id' => $input->get('id', '', 'INT')));
	    $reportClusters=explode(',',$annualreportTable->cluster_ids);

        if (empty(array_intersect($reportClusters, $clusterIds)) && !$user->authorise('core.manageall', 'com_cluster') && ($input->get('id', '', 'INT'))) {
	    	Factory::getApplication()->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
				return false;
	    }

        if(($user->id != $assignedDPO) && ($user->authorise('core.manageall', 'com_cluster')) && ($this->items->report_status == 'Draft'))
        {
        	// if (($this->items->report_status == 'Draft') && $user->authorise('core.manageall', 'com_cluster'))
	        // {
	        	// Factory::getApplication()->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
				// return false;
	        // }
        }
        


        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }
		parent::display($tpl);
	}
}
