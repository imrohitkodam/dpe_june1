<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Language\Text;

class PlgSystemJmaCaptcha extends CMSPlugin
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  3.1
     */
    protected $autoloadLanguage = true;

    /**
     * Display the hCaptcha widget.
     *
     * @return string|null
     */
    public function onDisplayJmaCaptcha()
    {
        $user = Factory::getUser();

        // Only show for guests
        /*
        if ($user->id != 0) {
            return null;
        }
        */

        $siteKey = $this->params->get('hcaptcha_site_key');
        
        if (empty($siteKey)) {
            return null;
        }

        $doc = Factory::getDocument();
        $doc->addScript('https://js.hcaptcha.com/1/api.js');

        return '<div class="h-captcha" data-sitekey="' . $siteKey . '"></div>';
    }

    /**
     * Validate the hCaptcha.
     *
     * @param object $data Post data object
     * @return bool
     * @throws Exception
     */
    public function onBeforeJmaAlertSubscriptionSave($data = null)
    {
        $app = Factory::getApplication();
        $user = Factory::getUser();

        // Only validate for guests
        if ($user->id != 0) {
            return true;
        }

        $secretKey = $this->params->get('hcaptcha_secret_key');
        
        if (empty($secretKey)) {
            return true;
        }

        $captchaToken = $app->input->get('h-captcha-response', '', 'string');

        if (empty($captchaToken)) {
            throw new \Exception(Text::_('PLG_SYSTEM_JMACAPTCHA_ERROR_INVALID_CAPTCHA'), 403);
        }

        // Verify with hCaptcha
        $url = 'https://hcaptcha.com/siteverify';
        $params = [
            'secret'   => $secretKey,
            'response' => $captchaToken,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];

        // Use cURL for verification
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response);

        if (!$result || !$result->success) {
            throw new \Exception(Text::_('PLG_SYSTEM_JMACAPTCHA_ERROR_INVALID_CAPTCHA'), 403);
        }

        return true;
    }
}
