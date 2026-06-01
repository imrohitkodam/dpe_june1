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

use Joomla\CMS\Input\Input as CMSWebInput;

/**
 * JwtHelperForJoomla class.
 */
final class JwtHelperForJoomla
{
    private $jwtCoder;

    public function __construct($apiToken)
    {
        $this->jwtCoder = new JwtCoder($apiToken);
    }

    public function generateToken($data = null)
    {
        return $this->encode($data);
    }

    public function generateTokenHeader($data = null)
    {
        return 'Bearer '.$this->generateToken($data);
    }

    public function checkToken($input = null)
    {
        $tokenHeader = $this->readTokenHeader($this->getInput($input));

        if (empty($tokenHeader)) {
            throw new JwtTokenException('Invalid token header.');
        }

        if (0 !== strpos($tokenHeader, 'Bearer ')) {
            throw new JwtTokenException('Invalid token (Bearer).');
        }

        [$bearerTag, $accessToken] = explode('Bearer ', $tokenHeader);

        $this->jwtCoder->decode($accessToken);

        return true;
    }

    public function encode($data)
    {
        return $this->jwtCoder->encode($data);
    }

    public function decode($field, $default = null, $input = null)
    {
        $data = $this->getInput($input)->get($field);

        if (!$data) {
            return $default;
        }

        return $this->jwtCoder->decode($data);
    }

    private function readTokenHeader($input = null)
    {
        if (isset($_SERVER[JwtCoder::SERVER_HEADER_NAME])) {
            return $_SERVER[JwtCoder::SERVER_HEADER_NAME];
        }

        if ($input && $header = $input->get(JwtCoder::HEADER_NAME, null, 'STRING')) {
            return $header;
        }

        return null;
    }

    private function getInput($input = null)
    {
        if (!$input) {
            return new CMSWebInput();
        }

        return $input;
    }
}
