<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

/*
 * @package     Extly Infrastructure Support
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2021 Extly, CB. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see         https://www.extly.com
 */

namespace XTP_BUILD\Extly\Infrastructure\Support\UrlTools;

use XTP_BUILD\Extly\Infrastructure\Creator\CreatorTrait;
use XTP_BUILD\Extly\Infrastructure\Support\Estring;
use XTP_BUILD\Extly\Infrastructure\Support\HttpClient\Helper as HttpClientHelper;
use XTP_BUILD\ForceUTF8\Encoding;
use XTP_BUILD\League\Uri\Schemes\Http as Uri;

final class Browser
{
    use CreatorTrait;

    private $withAutentication;

    private $username;

    private $password;

    public function authWithBasicAuth($username, $password)
    {
        $this->withAutentication = true;
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * detectMimeType.
     *
     * @param string $url Param
     *
     * @return string
     */
    public function detectMimeType($url)
    {
        $absUrl = Helper::create()->absolutizeUrl($url);
        $response = HttpClientHelper::create()->get($absUrl);

        $contentType = $response->getHeader('Content-Type');
        $mimeType = array_shift($contentType);

        return $mimeType;
    }

    /**
     * download.
     *
     * @param string $url        Param
     * @param string $tempFolder Param
     *
     * @return string
     */
    public function download($url, $tempFolder)
    {
        $absUrl = Helper::create()->absolutizeUrl($url);

        $httpClientHelper = HttpClientHelper::create();

        if ($this->withAutentication) {
            $httpClientHelper->authWithBasicAuth($this->username, $this->password);
        }

        $response = $httpClientHelper->get($absUrl);

        $contentType = $response->getHeader('Content-Type');
        $mimeType = array_shift($contentType);
        $extension = Estring::create($mimeType)->mimeToExtension();

        $urlUri = Uri::createFromString($absUrl);
        $fullPath = $urlUri->getPath();
        $pathinfoFilename = pathinfo($fullPath, PATHINFO_FILENAME);
        $pathinfoExtension = pathinfo($fullPath, PATHINFO_EXTENSION);
        $filename = $pathinfoFilename.'.'.$pathinfoExtension;

        if (empty($pathinfoExtension)) {
            $filename = (string) Estring::create($pathinfoFilename)->ensureRight('.'.$extension);
        }

        $toFile = $tempFolder.\DIRECTORY_SEPARATOR.$filename;

        $file = fopen($toFile, 'w');
        $handle = $response->getBody();

        while (!$handle->eof()) {
            fwrite($file, $handle->read(131072));
        }

        fclose($file);

        return $toFile;
    }

    /**
     * extractPage.
     *
     * @param mixed $url
     *
     * @return string
     */
    public function extractPage($url)
    {
        $response = HttpClientHelper::create()->get($url);
        $page = $response->getBody()->getContents();
        $page = Encoding::toUTF8($page);

        if (empty($page)) {
            throw new SupportException('Unable to retrieve the Page.');
        }

        return $page;
    }
}
