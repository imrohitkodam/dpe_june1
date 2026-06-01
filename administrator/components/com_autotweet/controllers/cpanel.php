<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

require_once 'default.php';

/**
 * AutoTweetControllerCpanel - The Control Panel controller class.
 *
 * @since       1.0
 */
class AutoTweetControllerCpanel extends AutotweetControllerDefault
{
    /**
     * onBeforeBrowse.
     */
    public function onBeforeBrowse()
    {
        $result = parent::onBeforeBrowse();

        if ($result) {
            // Run the automatic update site refresh
            $updateModel = XTF0FModel::getTmpInstance('LiveUpdates', 'AutoTweetModel');
            $updateModel->refreshUpdateSite();
        }

        return $result;
    }

    /**
     * getUpdateInfo.
     */
    public function getUpdateInfo()
    {
        $input = new JInputJSON();
        $token = $input->get('token', 'ALNUM');
        $this->input->set($token, 1);

        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $updateModel = XTF0FModel::getTmpInstance('LiveUpdates', 'AutoTweetModel');
        $updateInfo = (object) $updateModel->getUpdates();

        $updateInfo->result = false;

        if ($updateInfo->hasUpdate) {
            $strings = [
                'header' => JText::sprintf('COM_AUTOTWEET_CPANEL_MSG_UPDATEFOUND', VersionHelper::getFlavourName(), $updateInfo->version),
                'button' => JText::sprintf('COM_AUTOTWEET_CPANEL_MSG_UPDATENOW', $updateInfo->version),
                'infourl' => $updateInfo->infoURL,
                'infolbl' => JText::_('COM_AUTOTWEET_CPANEL_MSG_MOREINFO'),
            ];

            $strings['upgrade'] = null;

            if (!PERFECT_PUB_PRO) {
                $strings['upgrade'] = ' &nbsp; '.JText::_('COM_AUTOTWEET_UPDATE_TO_PERFECT_PUBLISHER_PRO_LABEL');
            }

            $updateInfo->result = <<<ENDRESULT
	<div class="xt-alert xt-alert-warning">
		<h3>
			<span class="xticon fas fa-info-circle glyphicon glyphicon-exclamation-sign"></span>
			{$strings['header']}
		</h3>
		<p>
			<a href="index.php?option=com_installer&view=update" class="btn btn-primary">
				{$strings['button']}
			</a>
			<a href="{$strings['infourl']}" target="_blank" class="btn btn-info">
				{$strings['infolbl']}
			</a>
			{$strings['upgrade']}
		</p>
	</div>
ENDRESULT;
        }

        echo $this->encodeJsonPackage($updateInfo);

        // Cut the execution short
        \Joomla\CMS\Factory::getApplication()->close();
    }

    public static function encodeJsonPackage($message, $callback = null)
    {
        $result = json_encode($message);

        if (!$result) {
            throw new Exception('JSON encoding error');
        }

        if ($callback) {
            $document = XTF0FPlatform::getInstance()->getDocument();
            $document->setMimeEncoding('application/javascript');

            $message = $callback.'('.$result.');';

            return $message;
        }

        return EJSON_START.$result.EJSON_END;
    }
}
