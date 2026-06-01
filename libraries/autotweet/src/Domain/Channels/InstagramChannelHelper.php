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

class InstagramChannelHelper extends FacebookChannelHelper
{
    /**
     * sendMessage.
     *
     * @param string $message Params
     * @param object $data    Params
     *
     * @return booleans
     */
    public function sendMessage($message, $data)
    {
        try {
            $fbId = $this->getFbChannelId();
            // $accessToken = $this->get('fbchannel_access_token');
            // $accessToken = $this->matchToken($accessToken, $fbId);

            // Instagram Media uploads fail - "The requested resource does not exist - Media not found"
            // https://developers.facebook.com/support/bugs/971771926884581/?join_id=f3e1112d25c7338
            $accessToken = $this->get('access_token');

            $igUserId = $this->isInstagramEnabled($fbId, $accessToken);

            if (!$igUserId) {
                return [
                    false,
                    'The Page is not linked to Instagram.',
                ];
            }

            $message = TextUtil::truncString($message, self::MAX_CHARS_NAME, false, true);

            $picture = $data->image_url;

            if (empty($picture)) {
                return [
                    false,
                    'The Post does not have an image.',
                ];
            }

            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'sendInstagramMessage', $message);

            $apiUrl = '/'.$igUserId.'/media';
            $arguments = [
                'image_url' => $picture,
                'caption' => $message,
            ];

            $logger->log(\Joomla\CMS\Log\Log::INFO, 'sendInstagramMessage callApiPost '.$apiUrl, $arguments);
            // $logger->log(\Joomla\CMS\Log\Log::INFO, 'sendInstagramMessage access_token', $accessToken);

            $post = $this->callApiPost($apiUrl, $arguments, $accessToken);
            $creationId = $post['id'];

            $apiUrl = '/'.$igUserId.'/media_publish';
            $arguments = [
                'creation_id' => $creationId,
            ];
            $post = $this->callApiPost($apiUrl, $arguments, $accessToken);

            $msg = 'Facebook Creation id: '.$creationId;

            $result = [
                true,
                $msg,
            ];
        } catch (Exception $e) {
            $code = $e->getCode();
            $msg = $code.' - '.$e->getMessage().' - '.$message;

            $result = [
                false,
                $msg,
            ];
        }

        return $result;
    }

    private function isInstagramEnabled($fbId, $accessToken)
    {
        $apiUrl = '/'.$fbId.'?fields=instagram_business_account';
        $result = $this->callApiGet($apiUrl, $accessToken);

        if (!is_array($result)) {
            $result = $result->asArray();
        }

        if (!isset($result['instagram_business_account']['id'])) {
            return false;
        }

        return $result['instagram_business_account']['id'];
    }
}
