<?php
/**
 * @package    Plg_User_Dpeuser
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2020. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die( 'Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Methods supporting to validate DPE user profile.
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgUserDpeUser extends CMSPlugin
{
	/**
	 * Load plugin language file automatically so that it can be used inside component
	 *
	 * @var    boolean
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Before store user method.
	 *
	 * Method is called before user data is stored in the database.
	 *
	 * @param   array    $oldData  Holds the old user data.
	 * @param   boolean  $isnew    True if a new user is stored.
	 * @param   array    $data     Holds the new user data.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onUserBeforeSave($oldData, $isnew, $data)
	{
		if (Factory::getApplication()->isClient('site'))
		{
			// If user is dpe admin then don't validate username
			if (!in_array($this->params->get('dpe_admin_usergroup'), $data['groups']))
			{
				if ($data['username'] != str_replace( array( '\''), '', $data['email']))
				{
					throw new InvalidArgumentException(Text::_('PLG_USER_DPEUSER_ERROR_INVALID_USERNAME'));
				}
			}

			return true;
		}
	}
}
