<?php
/*------------------------------------------------------------------------
  Mail Catcher - Email logging extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   https://www.solidres.com
  @Copyright Copyright (C) 2016 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

defined('_JEXEC') or die;

class MailcatcherController extends JControllerLegacy
{
	protected $default_view = 'dashboard';

	public function __construct($config = array())
	{
		parent::__construct($config);
		$downloadID = JComponentHelper::getParams('com_mailcatcher')->get('download_id');

		if (empty($downloadID))
		{
			return;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__update_sites'))
			->set($db->qn('extra_query') . '=' . $db->q('dlid=' . $downloadID))
			->where($db->qn('name') . '=' . $db->q('Mail Catcher Update Server'));
		$db->setQuery($query)
			->execute();
	}
}