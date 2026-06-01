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
use Joomla\CMS\Table\Table;

class MailcatcherTableMail extends Table
{
	public function __construct(&$db)
	{
		parent::__construct('#__mailcatcher_mails', 'id', $db);
	}
}