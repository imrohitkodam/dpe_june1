<?php
/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

JLoader::import('answersheet', JPATH_SITE . '/components/com_tmt/models');
$this->ansModel 	 = BaseDatabaseModel::getInstance('answersheet', 'TmtModel');
$this->item = $this->ansModel->getData();
$this->fromAssessment = 1;

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
$this->mediaLib = TJMediaStorageLocal::getInstance();

$lang = Factory::getLanguage();
$lang->load('com_tmt.quiz', JPATH_SITE, null, true, true);
?>

<?php
		$tjlmshelperObj	=	new comtjlmsHelper();
		$fileFormat = $tjlmshelperObj->getViewpath('com_tmt','answersheet','default_bs5','SITE','SITE');
		ob_start();
		include($fileFormat);
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
