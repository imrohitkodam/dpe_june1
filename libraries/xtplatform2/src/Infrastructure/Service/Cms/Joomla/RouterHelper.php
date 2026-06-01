<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

/*
 * @package     Extly Infrastructure Support
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see         https://www.extly.com
 */

namespace XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla;

use Exception;
use XTP_BUILD\Extly\Infrastructure\Creator\CreatorTrait;
use XTP_BUILD\Extly\Infrastructure\Support\UrlHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper as CMSComponentHelper;

// use Illuminate\Support\Facades\Log;

final class RouterHelper
{
    use CreatorTrait;

    public const COMPATIBILITY_MODE = 0;

    public const PERFORMANCE_MODE = 1;

    private $cmsService;

    private $mode = self::COMPATIBILITY_MODE;

    private $jRouter;

    public function __construct($cmsService)
    {
        $this->cmsService = $cmsService;
    }

    public function setMode($mode = self::COMPATIBILITY_MODE)
    {
        $this->mode = (int) $mode;
        $this->jRouter = null;

        if (self::PERFORMANCE_MODE === $this->mode) {
            $this->jRouter = CMSApplication::getInstance('site')->getRouter();
        }

        return $this;
    }

    public function calculateSefUrl($rawUrl, $rootUrl = null)
    {
        try {
            $rawUrl = $this->cleanAdministrator($rawUrl);
            $rawUrl = $this->checkMultilingualCase($rawUrl);

            if (self::PERFORMANCE_MODE === $this->mode) {
                return $this->buildSefUrl($rawUrl);
            }

            return $this->callHttpGetSefQuery($rawUrl, $rootUrl);
        } catch (Exception $e) {
            // Log::error('RouteHelper, calculateSefUrl: '.$e->getMessage());

            // Let's generate a workaround URL
            if (!$rootUrl) {
                $rootUrl = $this->cmsService->getRootUri();
            }

            $rawUrl = UrlHelper::create()->combine($rootUrl, $rawUrl.'#na');
        }

        // Log::warn('RouteHelper, calculateSefUrl NO Sef URL: '.$rawUrl);

        return $rawUrl;
    }

    /**
     * Better implementation to handle multiple menu entry for component (multiple itemids).
     *
     * @param string $compName Param
     * @param array  $needles  Param
     *
     * @return int
     */
    public function findItemid($compName, $needles = [])
    {
        $component = CMSComponentHelper::getComponent($compName);

        if (!isset($component->id)) {
            return null;
        }

        $menu = $this->cmsService->getMenu('site');
        $items = $menu->getItems('component_id', $component->id);

        if (empty($items)) {
            return null;
        }

        $matches = $this->calculateMatches($items, $needles);

        $bestMatchValue = max($matches);
        $bestMatches = array_filter($matches, function ($value) use ($bestMatchValue) {
            return $value === $bestMatchValue;
        });
        $keys = array_keys($bestMatches);
        $match = array_shift($keys);

        return (int) $match;
    }

    public function absolutize($relurl)
    {
        $rootUrl = $this->cmsService->getRootUri();

        return UrlHelper::create()->combine($rootUrl, $relurl);
    }

    private function calculateMatches($items, $needles)
    {
        $matches = [];

        foreach ($items as $item) {
            $url = parse_url($item->link);

            // No URL query ?, ignore it
            if (!isset($url['query'])) {
                $matches[$item->id] = 0;

                continue;
            }

            // We have a query
            parse_str($url['query'], $query);
            $matches[$item->id] = $this->calculateMatchRatio($item, $query, $needles);
        }

        return $matches;
    }

    private function calculateMatchRatio($item, $query, $needles)
    {
        $match = 0;

        // If we have a language needle and matches the language, +1!
        if ((isset($needles['language'])) && ($item->language === $needles['language'])) {
            ++$match;
            unset($needles['language']);
        }

        // Checking the query vs the defined needles
        foreach ($needles as $needle => $id) {
            if ((isset($query[$needle]))
                    && (($query[$needle] === $id) || ('*' === $id))) {
                ++$match;
            }
        }

        return $match;
    }

    private function callHttpGetSefQuery($rawUrl, $rootUrl = null)
    {
        $urlHelper = UrlHelper::create();

        if (!$rootUrl) {
            $rootUrl = $this->cmsService->getRootUri();
        }

        $sefQuery = $this->getSefQuery($rootUrl, $rawUrl);
        $body = $urlHelper->extractPage($sefQuery);
        $sefUrl = base64_decode($body, true);

        if (!$sefUrl) {
            return null;
        }

        // Let's make it relative
        try {
            if (!empty($sefUrl)) {
                // Log::info('RouteHelper, callHttpGetSefQuery: '.$rawUrl.' => '.$sefUrl);

                return $sefUrl;
            }
        } catch (\InvalidArgumentException $e) {
            // Log::warn('RouteHelper, callHttpGetSefQuery: '.$e->getMessage());
        }

        // Log::warn('RouteHelper, callHttpGetSefQuery NO Sef URL: '.$rawUrl);

        return $rawUrl;
    }

    private function getSefQuery($rootUrl, $rawUrl)
    {
        $sefQuery = 'index.php?option=com_xtdir4alg&task=sefQuery&url='.base64_encode($rawUrl);

        return UrlHelper::create()->combine($rootUrl, $sefQuery);
    }

    private function cleanAdministrator($url)
    {
        if (false === strpos($url, 'administrator/')) {
            return $url;
        }

        $parts = explode('administrator/', $url);

        return array_pop($parts);
    }

    private function checkMultilingualCase($rawUrl)
    {
        // It's a Multilingual Site
        if (!$this->cmsService->isMultilingualSite()) {
            return $rawUrl;
        }

        $currentSefCode = $this->cmsService->getCurrentSefCode();
        $defaultSefCode = $this->cmsService->getDefaultSefCode();

        if ($currentSefCode === $defaultSefCode) {
            return $rawUrl;
        }

        // It has a lang parameter
        if (false !== strpos($rawUrl, '&lang=') || false !== strpos($rawUrl, '&amp;lang=')) {
            return $rawUrl;
        }

        // We have to add the lanf sef code
        return $rawUrl.'&lang='.$this->cmsService->getCurrentSefCode();
    }

    private function buildSefUrl($rawUrl)
    {
        return (string) $this->jRouter->build($rawUrl);
    }
}
