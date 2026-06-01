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

namespace XTP_BUILD\Extly\Infrastructure\Service\Cms;

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Contracts\CmsServiceInterface;
use XTP_BUILD\Extly\Infrastructure\Support\Estring;
use XTP_BUILD\Extly\Infrastructure\Support\HttpClient\RequestSchemaEnum;
use XTP_BUILD\League\Uri\Schemes\Http as Uri;
use XTP_BUILD\MyCLabs\Enum\Enum;
use XTP_BUILD\Stringy\Stringy;

abstract class CmsServiceAbstract implements CmsServiceInterface
{
    const NAMED_OBJECT_CATEGORY = 'category';

    public $testingPublishMode;

    protected $config;

    protected $name;

    public function __construct($name, array $config = null)
    {
        $this->name = $name;
        $this->config = $config;
        $this->testingPublishMode = false;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTemporaryFilename($filename = null, $ext = null)
    {
        if (empty($ext)) {
            $ext = 'tmp';
        }

        if (empty($filename)) {
            $filename = md5(rand()).'.'.$ext;
        }

        return $this->getTemporaryFolderPath().\DIRECTORY_SEPARATOR.$filename;
    }

    public function isTemporaryFile($file)
    {
        // $dirname = pathinfo($file, PATHINFO_DIRNAME);
        $dirname = $this->getTemporaryFolderPath();
        $basename = pathinfo($file, PATHINFO_BASENAME);

        return $file === $dirname.\DIRECTORY_SEPARATOR.$basename;
    }

    public function releaseTemporaryFile($file)
    {
        // Double check
        if (($file) && (file_exists($file))
            && ($this->isTemporaryFile($file))) {
            unlink($file);
        }
    }

    public function isCli()
    {
        return \PHP_SAPI === 'cli';
    }

    public function isLocalUrl($url)
    {
        $hUrl = Estring::create($url);

        return ($hUrl->startsWith($this->getHttpRootUrl())) || ($hUrl->startsWith($this->getHttpsRootUrl()));
    }

    public function convertLocalUrlToFile($url)
    {
        $hUrl = Estring::create($url);

        $localRelativeUrl = $hUrl
            ->removeLeft($this->getHttpRootUrl())
            ->removeLeft($this->getHttpsRootUrl());

        return (string) $localRelativeUrl->prepend($this->getRootFolderPath());
    }

    public function getContentManager(Enum $contentType)
    {
        return ContentManager::create($this, $contentType);
    }

    public function translateLogLevel($customLogLevel)
    {
        switch ($customLogLevel) {
            case 0:
                return LoggerLevelsEnum::EMERGENCY;
            case 8:
                return LoggerLevelsEnum::ERROR;
            case 16:
                return LoggerLevelsEnum::INFO;
            case 64:
                return LoggerLevelsEnum::DEBUG;
            default:
                return LoggerLevelsEnum::ERROR;
        }

        return LoggerLevelsEnum::ERROR;
    }

    public function translateOrTitleize($value, $default = null)
    {
        if ($default) {
            return $this->translate($value, $default);
        }

        $label = $this->translate($value);

        if ($label === $value) {
            return Stringy::create($label)->titleize()->toString();
        }

        return $label;
    }

    private function getHttpRootUrl()
    {
        $uri = Uri::createFromString($this->getRootUri());
        $http = (string) $uri->withScheme(RequestSchemaEnum::HTTP);

        return $http;
    }

    private function getHttpsRootUrl()
    {
        $uri = Uri::createFromString($this->getRootUri());
        $https = (string) $uri->withScheme(RequestSchemaEnum::HTTPS);

        return $https;
    }
}
