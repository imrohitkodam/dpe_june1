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

/**
 * TwitterChannelHelper.
 *
 * @since       1.0
 */
class TwitterChannelHelper extends ChannelHelper
{
    protected $twitter;

    /**
     * sendMessage.
     *
     * @param string $message Param
     * @param object $data    Params
     *
     * @return bool
     */
    public function sendMessage($message, $data)
    {
        $imageFile = null;

        try {
            $imageUrl = $data->image_url;

            if (($this->isMediaModeTextOnlyPost()) || (empty($imageUrl))) {
                return $this->publishTweet($message, null);
            }

            $imageFile = ImageUtil::getInstance()->downloadImage($imageUrl);

            return $this->publishTweet(
                $message,
                $imageFile,
                $data
            );
        } catch (Exception $e) {
            $result = [
                false,
                $e->getMessage(),
            ];
        }

        if ($imageFile) {
            ImageUtil::getInstance()->releaseImage($imageFile);
        }

        return $result;
    }

    /**
     * hasWeight.
     *
     * @return bool
     */
    public function hasWeight()
    {
        return true;
    }

    /**
     * includeHashTags.
     *
     * @return bool
     */
    public function includeHashTags()
    {
        return $this->channel->params->get('hashtags', true);
    }

    /**
     * Internal service functions.
     *
     * @return object
     */
    protected function getApiInstance()
    {
        if (!$this->twitter) {
            $this->twitter = new \XTS_BUILD\Abraham\TwitterOAuth\TwitterOAuth(
                $this->get('consumer_key'),
                $this->get('consumer_secret'),
                $this->get('access_token'),
                $this->get('access_token_secret')
            );
            // $this->twitter->setApiVersion('2');
        }

        return $this->twitter;
    }

    /**
     * publishTweet.
     *
     * @param string             $statusMessage Param
     * @param string             $imagefile     Param
     * @param AutotweetTablePost $data          Param
     *
     * @return array
     */
    private function publishTweet($statusMessage, $imagefile = null, $data = null)
    {
        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'publishTweet: '.$statusMessage.' - '.$imagefile);

        $connection = $this->getApiInstance();

        $parameters = [
            'status' => $statusMessage,
        ];

        if ($imagefile) {
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'publishTweet media/upload '.$imagefile);

            $media = $connection->upload(
                'media/upload',
                [
                    'media' => $imagefile,
                ]
            );

            if (isset($media->error)) {
                $message = 'Error '.$connection->getLastHttpCode();

                $message = [
                    false,
                    $message,
                ];

                return $message;
            }

            $parameters['media_ids'] = implode(',', [$media->media_id_string]);

            // https://developer.twitter.com/en/docs/twitter-api/v1/media/upload-media/api-reference/post-media-metadata-create
            $metadata = $connection->post(
                'media/metadata/create',
                [
                    'media_id' => $media->media_id_string,
                    'alt_text' => [
                        'text' => PostHelper::getAltText($statusMessage, $data),
                    ],
                ],
                true
            );

            if (isset($metadata->error)) {
                $message = 'Error '.$connection->getLastHttpCode();

                $message = [
                    false,
                    $message,
                ];

                return $message;
            }
        }

        $logger->log(\Joomla\CMS\Log\Log::INFO, 'publishTweet statuses/update', $parameters);

        $response = $connection->post('statuses/update', $parameters);

        if (200 === (int) $connection->getLastHttpCode()) {
            $postUrl = $response->id_str;
            $postUrl = 'https://twitter.com/'
                .$response->user->screen_name.'/status/'.$postUrl;

            return [
                true,
                'OK - '.$postUrl,
            ];
        }

        if (isset($response->errors)) {
            $error = array_shift($response->errors);
            $message = $error->code.' - '.$error->message;
        } else {
            $message = 'Error '.$connection->getLastHttpCode();
        }

        $message = [
            false,
            $message,
        ];

        return $message;
    }
}
