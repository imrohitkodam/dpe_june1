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

use XTS_BUILD\Firebase\JWT\JWT;

/**
 * JwtHelper class.
 */
class JwtCoder
{
    public const HEADER_NAME = 'X-Access-Token';

    public const SERVER_HEADER_NAME = 'HTTP_X_ACCESS_TOKEN';

    public const ALGORITHM = 'HS256';

    // 1 Month
    public const TIMEOUT = 3672000;

    private $key;

    // Issuer
    private $iss;

    // Subject
    private $sub;

    // Audience
    private $aud;

    public function __construct($key, $iss = null, $sub = null, $aud = null)
    {
        $this->key = $key;
        $this->iss = $iss;
        $this->sub = $sub;
        $this->aud = $aud;

        JWT::$leeway = 10;
    }

    public function encode($data = null)
    {
        $date = new DateTime();
        $now = $date->getTimestamp();

        $payload = [
            'iss' => $this->iss,
            'sub' => $this->sub,
            'aud' => $this->aud,

            // Issued At
            'iat' => $now,

            // Expiration Time
            'exp' => $now + self::TIMEOUT,
        ];

        if ($data) {
            $payload['data'] = json_encode($data);
        }

        return JWT::encode($payload, $this->key, self::ALGORITHM);
    }

    public function decode($data)
    {
        $packet = JWT::decode($data, $this->key, [self::ALGORITHM]);

        return isset($packet->data) ? json_decode($packet->data) : null;
    }
}
