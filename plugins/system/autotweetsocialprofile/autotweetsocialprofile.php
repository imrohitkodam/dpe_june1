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
 * Perfect Publisher  Google's structured data / Social Profile Links.
 *
 * @since       1.0
 */
class PlgSystemAutotweetSocialProfile extends \Joomla\CMS\Plugin\CMSPlugin
{
    /**
     * onBeforeRender.
     *
     * @return array A four element array of (article_id, article_title, category_id, object)
     */
    public function onBeforeRender()
    {
        if (!defined('AUTOTWEET_API') && !@include_once(JPATH_ADMINISTRATOR.'/components/com_autotweet/api/autotweetapi.php')) {
            return;
        }

        // Use only for front-end site.
        if (\Joomla\CMS\Factory::getApplication()->isClient('administrator')) {
            return;
        }

        $type = $this->params->get('type', 'Organization');
        $customtype = $this->params->get('customtype');
        $name = $this->params->get('name', \Joomla\CMS\Factory::getConfig()->get('sitename'));
        $url = $this->params->get('url', \Joomla\CMS\Uri\Uri::root());

        $sameAsFacebook = $this->params->get('sameAsFacebook');
        $sameAsTwitter = $this->params->get('sameAsTwitter');
        $sameAsInstagram = $this->params->get('sameAsInstagram');
        $sameAsYoutube = $this->params->get('sameAsYoutube');
        $sameAsLinkedIn = $this->params->get('sameAsLinkedIn');
        $sameAsMyspace = $this->params->get('sameAsMyspace');

        $sameAsPinterest = $this->params->get('sameAsPinterest');
        $sameAsSoundCloud = $this->params->get('sameAsSoundCloud');
        $sameAsTumblr = $this->params->get('sameAsTumblr');

        $logo = $this->params->get('logo');
        $image = $this->params->get('image');

        $telephone = $this->params->get('telephone');

        $contactTelephone = $this->params->get('contactTelephone');
        $contactType = $this->params->get('contactType', 'customer support');
        $areaServed = $this->params->get('areaServed');
        $contactOption = $this->params->get('contactOption');
        $availableLanguage = $this->params->get('availableLanguage');

        $streetAddress = $this->params->get('streetAddress');
        $addressLocality = $this->params->get('addressLocality');
        $addressRegion = $this->params->get('addressRegion');
        $postalCode = $this->params->get('postalCode');
        $addressCountry = $this->params->get('addressCountry');
        $latitude = $this->params->get('latitude');
        $longitude = $this->params->get('longitude');

        $photo = $this->params->get('photo');
        $priceRange = $this->params->get('priceRange');

        $structuredMarkup = [];
        $sameAs = [];

        $structuredMarkup['@context'] = 'http://schema.org';

        if (empty($customtype)) {
            $structuredMarkup['@type'] = $type;
        } else {
            $structuredMarkup['@type'] = $customtype;
        }

        $structuredMarkup['name'] = $name;
        $structuredMarkup['url'] = $url;

        if (!empty($logo)) {
            $logo_url = RouteHelp::getInstance()->getAbsoluteUrl($logo, true);

            if (!empty($logo_url)) {
                $structuredMarkup['logo'] = $logo_url;
            }
        }

        if (!empty($image)) {
            $image_url = RouteHelp::getInstance()->getAbsoluteUrl($image, true);

            if (!empty($image_url)) {
                $structuredMarkup['image'] = $image_url;
            }
        }

        if (!empty($telephone)) {
            $structuredMarkup['telephone'] = $telephone;
        }

        if ($sameAsFacebook) {
            $sameAs[] = $sameAsFacebook;
        }

        if ($sameAsTwitter) {
            $sameAs[] = $sameAsTwitter;
        }

        if ($sameAsInstagram) {
            $sameAs[] = $sameAsInstagram;
        }

        if ($sameAsYoutube) {
            $sameAs[] = $sameAsYoutube;
        }

        if ($sameAsLinkedIn) {
            $sameAs[] = $sameAsLinkedIn;
        }

        if ($sameAsPinterest) {
            $sameAs[] = $sameAsPinterest;
        }

        if ($sameAsSoundCloud) {
            $sameAs[] = $sameAsSoundCloud;
        }

        if ($sameAsTumblr) {
            $sameAs[] = $sameAsTumblr;
        }

        if (!empty($sameAs)) {
            $structuredMarkup['sameAs'] = $sameAs;
        }

        if (!empty($contactTelephone)) {
            $contactPoint = [
                '@type' => 'ContactPoint',
                'telephone' => $contactTelephone,
            ];

            if (!empty($contactType)) {
                $contactPoint['contactType'] = $contactType;
            }

            if (!empty($areaServed)) {
                $areaServed = TextUtil::listToArray($areaServed);
                $contactPoint['areaServed'] = $areaServed;
            }

            if (!empty($contactOption)) {
                $contactPoint['contactOption'] = $contactOption;
            }

            if (!empty($availableLanguage)) {
                $availableLanguage = TextUtil::listToArray($availableLanguage);
                $contactPoint['availableLanguage'] = $availableLanguage;
            }

            $structuredMarkup['contactPoint'] = $contactPoint;
        }

        if ((!empty($streetAddress))
            && (!empty($addressLocality))
            && (!empty($addressRegion))
            && (!empty($postalCode))
            && (!empty($addressCountry))) {
            $address = [
                '@type' => 'PostalAddress',
                'streetAddress' => $streetAddress,
                'addressLocality' => $addressLocality,
                'addressRegion' => $addressRegion,
                'postalCode' => $postalCode,
                'addressCountry' => $addressCountry,
            ];

            $structuredMarkup['address'] = $address;
        }

        if (('LocalBusiness' === $type) && (!empty($latitude)) && (!empty($longitude))) {
            $geo = [
                '@type' => 'GeoCoordinates',
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];

            $structuredMarkup['geo'] = $geo;
        }

        if (('LocalBusiness' === $type) && (!empty($photo))) {
            $photo_url = RouteHelp::getInstance()->getAbsoluteUrl($photo, true);

            if (!empty($photo_url)) {
                $structuredMarkup['photo'] = $photo_url;
            }
        }

        if (('LocalBusiness' === $type) && (!empty($priceRange))) {
            $structuredMarkup['priceRange'] = $priceRange;
        }

        \Joomla\CMS\Factory::getDocument()->addScriptDeclaration(json_encode($structuredMarkup), 'application/ld+json');
    }
}
