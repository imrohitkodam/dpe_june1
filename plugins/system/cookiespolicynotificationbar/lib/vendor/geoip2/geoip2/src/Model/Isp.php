<?php
/* ======================================================
# Cookies Policy Notification Bar for Joomla! - v4.0.0 (Pro version)
# -------------------------------------------------------
# For Joomla! CMS
# Author: Web357 (Yiannis Christodoulou)
# Copyright (©) 2009-2020 Web357. All rights reserved.
# License: GNU/GPLv3, http://www.gnu.org/licenses/gpl-3.0.html
# Website: https:/www.web357.com/
# Demo: https://demo.web357.com/joomla/cookiespolicynotificationbar
# Support: support@web357.com
# Last modified: 26 Nov 2020, 01:41:47
========================================================= */

namespace GeoIp2\Model;

/**
 * This class provides the GeoIP2 ISP model.
 *
 * @property-read int|null $autonomousSystemNumber The autonomous system number
 *     associated with the IP address.
 * @property-read string|null $autonomousSystemOrganization The organization
 *     associated with the registered autonomous system number for the IP
 *     address.
 * @property-read string|null $isp The name of the ISP associated with the IP
 *     address.
 * @property-read string|null $organization The name of the organization associated
 *     with the IP address.
 * @property-read string $ipAddress The IP address that the data in the model is
 *     for.
 */
class Isp extends AbstractModel
{
    protected $autonomousSystemNumber;
    protected $autonomousSystemOrganization;
    protected $isp;
    protected $organization;
    protected $ipAddress;

    /**
     * @ignore
     *
     * @param mixed $raw
     */
    public function __construct($raw)
    {
        parent::__construct($raw);
        $this->autonomousSystemNumber = $this->get('autonomous_system_number');
        $this->autonomousSystemOrganization =
            $this->get('autonomous_system_organization');
        $this->isp = $this->get('isp');
        $this->organization = $this->get('organization');

        $this->ipAddress = $this->get('ip_address');
    }
}
