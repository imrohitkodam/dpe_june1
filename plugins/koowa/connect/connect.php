<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 - 2014 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class PlgKoowaConnect extends PlgKoowaAbstract
{
    const URL = "https://api.joomlatools.com/";

    const VERSION = '4.0.1';

    /**
     * A list of APIs that can be directly called from frontend using the following URL structure:
     *
     * index.php?option=com_ajax&plugin=connect&group=koowa&format=json&path=PATH_TO_API
     *
     * @var array
     */
    protected static $_public_apis = ['mail/validate', 'embed/iframe', 'embed/oembed'];

    protected static $_instance;

    public function __construct($dispatcher, $config = array())
    {
        parent::__construct($dispatcher, $config);

        $this->getConfig()->api_key     = trim($this->params->get('api_key'));
        $this->getConfig()->secret_key  = trim($this->params->get('secret_key'));

        static::$_instance = $this;

        $this->loadLanguage();
    }

    /**
     * @return $this
     */
    public static function getInstance()
    {
        return static::$_instance;
    }

    public static function isSupported()
    {
        $instance = static::getInstance();

        if ($instance) {
            $api    = $instance->getApiKey();
            $secret = $instance->getSecretKey();

            return !empty($api) && !empty($secret);
        }

        return false;
    }

    /**
     * This method is used by com_ajax of Joomla to call plugins directly.
     *
     * It can be used to directly call APIs from JavaScript using the following URL structure:
     * index.php?option=com_ajax&plugin=connect&group=koowa&format=json&path=PATH_TO_API
     *
     * Additional query string parameters and the request body is directly passed upstream
     */
    public function onAjaxConnect()
    {
        if (!static::isSupported()) {
            throw new RuntimeException('Invalid credentials');
        }

        $manager = KObjectManager::getInstance();
        $request = $manager->getObject('request');
        $path    = $request->getQuery()->path;


        if ($path === 'activities-status') {
            return $this->_handleActivities();
        }
        if ($path === 'scanner-test') {
            return $this->_scannerTest();
        }

        if (!in_array($path, static::$_public_apis)) {
            throw new RuntimeException('Invalid public API');
        }

        $params = $request->getQuery()->toArray();

        foreach (['option', 'plugin', 'Itemid', 'group', 'format', 'path'] as $key) {
            unset($params[$key]);
        }

        $options = [
            'method' => $request->getMethod(),
            'query'  => $params,
            'data'   => $request->getMethod() === 'GET' ? null : $request->getData()->toArray()
        ];

        $response = static::sendRequest($path, $options);
        $status   = 200;

        if ($response->status_code && isset(KHttpResponse::$status_messages[$response->status_code])) {
            $status = $response->status_code;
        }

        $this->_sendResponse($response->body, $status);
    }

    /**
     * @return string
     */
    protected function _scannerTest()
    {
        $manager   = KObjectManager::getInstance();
        $request   = $manager->getObject('request');
        $query     = $request->getQuery();
        $operation = $query->get('test', 'cmd');

        $is_callback = ($request->getMethod() === 'POST' && $operation === 'callback');
        $is_get      = ($request->getMethod() === 'GET' && $operation === 'get');

        if ($is_callback || $is_get) {
            $data   = $request->getData();
            $body   = 'ok';
            $status = 200;

            if (!static::verifyToken($query->token)) {
                $status = 500;
                $body   = 'token-error';
            }
            elseif ($is_callback && !$data->has('post-data-check')) {
                $status = 500;
                $body   = 'post-data-error';
            }

            $response = $manager->getObject('response', [
                'request' => $request,
                'user'    => $manager->getObject('user')
            ]);

            return $response->setStatus($status)
                ->setContent($body, 'application/json')
                ->send();
        }

        $url   = clone $this->getObject('request')->getSiteUrl();
        $query = array(
            'option'  => 'com_ajax',
            'plugin'  => 'connect', 'group' => 'koowa', 'format' => 'json', 'path' => 'scanner-test',
            'connect' => 1,
            'token'   => static::generateToken()
        );

        if (substr($url->getPath(), -1) !== '/') {
            $url->setPath($url->getPath() . '/');
        }

        $url->setQuery($query);

        $callback_url = clone $url;
        $download_url = clone $url;
        $callback_url->setQuery(['test' => 'callback'], true);
        $download_url->setQuery(['test' => 'get'], true);

        $data = array(
            'download_url' => (string)$download_url,
            'callback_url' => (string)$callback_url,
            'filename'     => 'dummy.txt',
            'user_data'    => array(
                'uuid' => 'dummy'
            )
        );

        $response = PlgKoowaConnect::sendRequest('scanner/test', [
            'data' => $data, 'exception' => false
        ]);

        $body = @json_decode($response->body, true);

        if (!$response->status_code || json_last_error() !== JSON_ERROR_NONE || !is_array($body) || !isset($body['body'])) {
            $response->body = json_encode([
                'error' => 'endpoint-error',
                'statusCode' => 0,
                'body' => json_last_error() === JSON_ERROR_NONE ? $body : ($response->body ?: null),
                'isLocal' => static::isLocal(),
                'isSupported' => static::isSupported(),
                'version' => static::VERSION,
            ]);
            $response->status_code = 500;
        } elseif (is_array($body) && isset($body['body'])) {
            $body['isLocal'] = static::isLocal();
            $body['isSupported'] = static::isSupported();
            $body['version'] = static::VERSION;

            $response->body = json_encode($body);
        }

        return $this->_sendResponse($response->body, $response->status_code);
    }

    protected function _sendResponse($body = null, $status = KHttpResponse::OK)
    {
        $manager = KObjectManager::getInstance();

        $manager->getObject('response', [
                'request' => $manager->getObject('request'),
                'user' => $manager->getObject('user')
            ])
            ->setStatus($status)
            ->setContent($body, 'application/json')
            ->send();
    }

    protected function _handleActivities()
    {
        $request  = $this->getObject('request');
        $status   = KHttpResponse::OK;
        $response = [];

        if (!PlgKoowaConnect::verifyToken($request->getQuery()->token)) {
            throw new RuntimeException('Invalid JWT token');
        }

        if ($request->getMethod() == 'GET') {
            $response = [
                'enabled' => (bool) $this->params->get('activities')
            ];
        }
        else
        {
            $enabled = (int) $request->getData()->enabled;

            $this->params->set('activities', $enabled);

            if ($this->_saveParameters()) {
                $response = [
                    'enabled' => (bool) $enabled
                ];
            } else {
                $status = KHttpResponse::INTERNAL_SERVER_ERROR;
            }
        }

        $this->_sendResponse(json_encode($response), $status);
    }

    protected function _saveParameters()
    {
        if (!$this->getConfig()->id) {
            throw new RuntimeException('Cannot find Plugin ID');
        }

        $query = $this->getObject('database.query.update')
            ->table('extensions')
            ->values('params = :params')
            ->where('extension_id = :extension_id')
            ->bind([
                'params' => $this->params->toString(), 'extension_id' => $this->getConfig()->id
            ]);

        return $this->getObject('database.adapter.mysqli')->execute($query, KDatabase::RESULT_USE);
    }


    protected static function _getCaptchaCache()
    {
        $config  = \JFactory::getConfig();
        $group   = 'com_joomlatoolsconnect.captcha';
        $options = array(
            'caching' 		=> true,
            'defaultgroup'  => $group,
            'lifetime' 		=> 60,
            'cachebase'    => JPATH_ADMINISTRATOR.'/cache',
            'language'     => $config->get('language', 'en-GB'),
            'storage'      => $config->get('cache_handler', 'file')
        );

        return JCache::getInstance('output', $options);

    }

    public static function getCaptchaChallange(array $parameters = [])
    {
        ksort($parameters);

        $cache_handler = static::_getCaptchaCache();
        $cache_key     = 'captcha.challenge.'.json_encode($parameters);
        $output        = $cache_handler->get($cache_key);

        if (!$output) {
            $result = static::sendRequest('captcha/challenge', [
                'method' => 'GET',
                'query'  => $parameters
            ]);

            if ($result->status_code === 200) {
                $output = $result->body;

                $cache_handler->store($output, $cache_key);
            }
        }

        return $output;
    }

    public static function verifyCaptchaChallange($token = null)
    {
        if ($token === null) {
            $data  = KObjectManager::getInstance()->getObject('request')->getData();
            $token = $data->get('g-recaptcha-response', 'raw');
        }

        $body = (object) ['success' => false];

        try {
            $result = static::sendRequest('captcha/challenge', [
                'method' => 'POST',
                'data'   => ['g-recaptcha-response' => $token]
            ]);

            if ($result->status_code === 200) {
                $body = json_decode($result->body);
            }
        } catch (Exception $e) {
            if (JDEBUG) throw $e;
        }

        return $body;
    }

    public function getApiKey()
    {
        return $this->getConfig()->api_key;
    }

    public function getSecretKey()
    {
        return $this->getConfig()->secret_key;
    }

    /**
     * Sends an HTTP request and returns the response
     *
     * @param  string $path Request path including the query string
     * @param  array $options Request options. Valid keys include method, data, query, and callback
     * @return string
     */
    public static function sendRequest($path, $options = array())
    {
        $curl = curl_init();

        $url = static::URL.trim($path, '/').'/';

        if (isset($options['query'])) {
            if (is_array($options['query'])) {
                $options['query'] = http_build_query($options['query'], '', '&');
            }

            $url .= '?'.$options['query'];
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => isset($options['method']) ? strtoupper($options['method']) : "POST",
            CURLOPT_HTTPHEADER => array(
                "Content-type: application/json",
                "Referer: ".JURI::root(),
                "Authorization: Bearer ".static::generateToken()
            ),
        ));

        if (isset($options['data'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($options['data']));
        }

        if (isset($options['callback']) && is_callable($options['callback'])) {
            $callback = $options['callback'];
            $callback($curl, $path, $options);
        }

        $response = curl_exec($curl);

        if (curl_errno($curl) && (!isset($options['exception']) || $options['exception'] !== false)) {
            throw new RuntimeException('Curl Error: '.curl_error($curl));
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (isset($status_code) && ($status_code < 200 || $status_code >= 300)
         && (!isset($options['exception']) || $options['exception'] !== false)) {
            throw new UnexpectedValueException('Problem in the request. Request returned '. $status_code, $status_code);
        }

        curl_close($curl);

        $result = new stdClass();
        $result->status_code = $status_code;
        $result->body        = $response;

        return $result;
    }

    /**
     * Verifies a signed JWT token
     *
     * @param string $jwt_token JWT token
     * @return boolean
     */
    public static function verifyToken($jwt_token)
    {
        /** @var KHttpTokenInterface $token */
        $token = KObjectManager::getInstance()->getObject('http.token');
        $token->fromString($jwt_token);

        return static::isSupported() && $token->verify(static::getInstance()->getSecretKey()) && !$token->isExpired();
    }

    /**
     * Returns a signed JWT token for the current API key in plugin settings
     *
     * @return string
     */
    public static function generateToken()
    {
        /** @var KHttpTokenInterface $token */
        $token = KObjectManager::getInstance()->getObject('http.token');
        $date  = new DateTime('now', new DateTimeZone('UTC'));

        return $token
            ->setSubject(static::getInstance()->getApiKey())
            ->setExpireTime($date->modify('+1 hours'))
            ->sign(static::getInstance()->getSecretKey());
    }

    /**
     * Returns if the site is running on localhost
     *
     * @return string
     */
    public static function isLocal()
    {
        static $local_hosts = array('localhost', '127.0.0.1', '::1');

        $url  = KObjectManager::getInstance()->getObject('request')->getUrl();
        $host = $url->host;

        if (in_array($host, $local_hosts)) {
            return true;
        }

        // Returns true if host is an IP address
        if (ip2long($host)) {
            return (filter_var($host, FILTER_VALIDATE_IP,
                    FILTER_FLAG_IPV4 |
                    FILTER_FLAG_IPV6 |
                    FILTER_FLAG_NO_PRIV_RANGE |
                    FILTER_FLAG_NO_RES_RANGE) === false);
        }
        else {
            // If no TLD is present, it's definitely local
            if (strpos($host, '.') === false) {
                return true;
            }

            return preg_match('/(?:\.)(local|localhost|test|example|invalid|dev|box|intern|internal)$/', $host) === 1;
        }
    }
}
