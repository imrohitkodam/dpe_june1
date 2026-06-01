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
use XTP_BUILD\League\Uri\Components\HierarchicalPath as Path;
use XTP_BUILD\League\Uri\Modifiers\AddLeadingSlash;
use XTP_BUILD\League\Uri\Modifiers\AppendSegment;
use XTP_BUILD\League\Uri\Modifiers\Relativize;
use XTP_BUILD\League\Uri\Schemes\Http as Uri;

final class Helper
{
    use CreatorTrait;

    const HTTP_PROTOCOL = 'http:';

    public function isAbsoluteUrl($url)
    {
        // Kind of absolute Url //mail.google.com ...
        if (('/' === $url[0]) && ('/' === $url[1])) {
            return true;
        }

        $uri = Uri::createFromString($url);
        $uriReference = \XTP_BUILD\League\Uri\Modifiers\uri_reference($uri);

        return $uriReference['absolute_uri'];
    }

    public function absolutizeUrl($url)
    {
        if (empty($url)) {
            return null;
        }

        if ($this->isAbsoluteUrl($url)) {
            return $url;
        }

        $url = Estring::create($url);

        if ($url->startsWith('//')) {
            return $url->ensureLeft('http:');
        }

        return $url->ensureLeft('http://');
    }

    public function relativizeAbsUrl($url)
    {
        $uri = Uri::createFromString($url);
        $query = $uri->getQuery();

        return 'index.php?'.$query;
    }

    public function relativize($baseUrl, $url)
    {
        $baseUri = Uri::createFromString($baseUrl);
        $relativizer = new Relativize($baseUri);
        $uri = Uri::createFromString($url);
        $relativeUri = $relativizer($uri);

        return (string) $relativeUri;
    }

    /**
     * getRootUrl.
     *
     * @param string $url Params
     *
     * @return string
     */
    public function getRootUrl($url)
    {
        $uri = Uri::createFromString($url);
        $path = $uri->getPath();

        if (empty($path)) {
            return $url;
        }

        list($rootUrl, $discarded) = explode($path, $url);

        return rtrim($rootUrl, '/');
    }

    public function fastCombine($rootUrl, $additionalPathQuery)
    {
        $rootUri = Uri::createFromString($this->absolutizeUrl($rootUrl));

        // Combine segments
        $rootSegments = $rootUri->path->getSegments();
        $additionalUri = Uri::createFromString($additionalPathQuery);

        $segments = $additionalUri->path->getSegments();
        $fullSegments = array_filter(array_merge($rootSegments, $segments), function ($value) {
            return '' !== $value;
        });
        $fragment = $additionalUri->getFragment();

        // Create new Url
        $newRootPath = Path::createFromSegments($fullSegments, PATH::IS_ABSOLUTE);
        $uri = $rootUri
            ->withPath((string) $newRootPath)
            ->withQuery((string) $additionalUri->query)
            ->withFragment($fragment);

        return (string) $uri;
    }

    public function combine($urlBase, $relativeUrl)
    {
        $urlBase = $this->absolutizeUrl($urlBase);

        $uri = Uri::createFromString($urlBase);

        $relativePart = Uri::createFromString($relativeUrl);
        $modifier = new AddLeadingSlash();
        $relativePart = $modifier->__invoke($relativePart);

        if ($part = $relativePart->getScheme()) {
            $uri = $uri->withScheme($part);
        }

        if ($part = $relativePart->getUserInfo()) {
            $uri = $uri->withUserInfo($part);
        }

        if ($part = $relativePart->getHost()) {
            $uri = $uri->withHost($part);
        }

        if ($part = $relativePart->getPort()) {
            $uri = $uri->withPort($part);
        }

        if ($part = $relativePart->getPath()) {
            if (($uriPath = $uri->getPath()) && (0 !== strpos($part, $uriPath))) {
                $modifier = new AppendSegment($part);
                $uri = $modifier->__invoke($uri);
            } else {
                $uri = $uri->withPath($part);
            }
        }

        if ($part = $relativePart->getQuery()) {
            $uri = $uri->withQuery($part);
        }

        if ($part = $relativePart->getFragment()) {
            $uri = $uri->withFragment($part);
        }

        return (string) $uri;
    }

    public function getHost($stringUri)
    {
        return Uri::createFromString($stringUri)->getHost();
    }

    public function updateHost($url, $host)
    {
        if (!$this->isAbsoluteUrl($host)) {
            $host = $this->absolutizeUrl($host);
        }

        $hostUri = Uri::createFromString($host);

        return (string) Uri::createFromString($url)
            ->withScheme($hostUri->getScheme())
            ->withHost($hostUri->getHost())
            ->withPort($hostUri->getPort());
    }

    /**
     * detectMimeType.
     *
     * @deprecated
     *
     * @param string $url Param
     *
     * @return string
     */
    public function detectMimeType($url)
    {
        return Browser::create()->detectMimeType($url);
    }

    /**
     * download.
     *
     * @deprecated
     *
     * @param string $url        Param
     * @param string $tempFolder Param
     *
     * @return string
     */
    public function download($url, $tempFolder)
    {
        return Browser::create()->download($url, $tempFolder);
    }

    /**
     * extractPage.
     *
     * @deprecated
     *
     * @param mixed $url
     *
     * @return string
     */
    public function extractPage($url)
    {
        return Browser::create()->extractPage($url);
    }
}
