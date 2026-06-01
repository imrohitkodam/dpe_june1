<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * User controller class.
 *
 * @since  1.6
 */
class TcControllerContent extends JControllerForm
{
	/**
	 * Method which will redirect to T&C page after accepted the Terms & conditions
	 *
	 * @return void
	 */
	public function accept()
	{
		$app        = JFactory::getApplication();
		$input      = $app->input;

		$userId     = $input->get('user_id', '', 'INT');
		$tcId 		= $input->get('tc_id', '', 'INT');
		$returnURL 		= $input->get('return_url', '', 'STRING');

		// Add T&C accepted user entry in #_tc_acceptance table
		require_once JPATH_ADMINISTRATOR . '/components/com_tc/models/usertcs.php';

		$model                      = JModelLegacy::getInstance('Usertcs', 'TcModel');
		$storeUserEntryAndRedirect  = $model->save($userId, $tcId, $returnURL);

		$redirectURL = base64_decode($storeUserEntryAndRedirect);

		$message = JText::_('COM_TC_LATEST_TERMSANDCONDITIONS_ACCEPTED');

		// Redirect to refresh page for accepting next T&C
		$app->redirect(JRoute::_($redirectURL), $message, 'success');
	}
}
