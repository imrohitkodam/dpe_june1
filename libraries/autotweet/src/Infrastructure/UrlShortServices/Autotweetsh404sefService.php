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

use Joomla\CMS\Uri\Uri as CMSUri;

/**
 * Autotweetsh404sefService.
 *
 * @since       1.0
 */
class Autotweetsh404sefService extends AutotweetShortservice
{
    /**
     * getShortURL.
     *
     * @param string $longUrl param
     *
     * @return string
     */
    public function getShortUrl($longUrl)
    {
        if (!defined('SH404SEF_IS_RUNNING')) {
            return $longUrl;
        }

        $relativeShurl = $this->createShurl($longUrl);

        return CMSUri::root().$relativeShurl;
    }

    protected static function createShurl($nonSefUrl)
    {
        if (empty($nonSefUrl)) {
            return $nonSefUrl;
        }

        // Not on homepage
        if (shIsAnyHomepage($nonSefUrl)) {
            return $nonSefUrl;
        }

        // Not for format = raw, format = pdf or printing
        $format = Sh404sefHelperUrl::getUrlVar($nonSefUrl, 'format');

        if (in_array(strtolower($format), ['raw', 'pdf'], true)) {
            return $nonSefUrl;
        }

        $print = Sh404sefHelperUrl::getUrlVar($nonSefUrl, 'print');

        if ((bool) $print) {
            return $nonSefUrl;
        }

        // Not if tmpl not empty or not index
        $tmpl = Sh404sefHelperUrl::getUrlVar($nonSefUrl, 'tmpl');

        if (!empty($tmpl) && 'index' !== $tmpl) {
            return $nonSefUrl;
        }

        // Force global setting
        shMustCreatePageId('set', true);

        // Get a model and create shURL
        $model = ShlMvcModel_Base::getInstance('Pageids', 'Sh404sefModel');

        return $model->createPageId('', $nonSefUrl);
    }
}
