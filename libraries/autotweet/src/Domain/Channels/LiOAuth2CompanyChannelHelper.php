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
 * LiOAuth2CompanyChannelHelper.
 *
 * @since       1.0
 */
class LiOAuth2CompanyChannelHelper extends LiOAuth2ChannelHelper
{
    /**
     * getAuthorizationUrl.
     *
     * @return string
     */
    public function getAuthorizationUrl()
    {
        return $this->getAuthorizationUrlInternal(self::API2_COMPANY_PERMS);
    }

    /**
     * getMyCompanies.
     *
     * @return object
     */
    public function getMyCompanies()
    {
        return $this->getMyCompaniesv2();
    }

    /**
     * getMyCompanies.
     *
     * @return object
     */
    public function getMyCompaniesv2()
    {
        $result = null;

        try {
            $api = $this->getLinkedInAPIv2();
            $response = $api->api('GET', '/v2/organizationalEntityAcls?q=roleAssignee&count=100');

            if (isset($response['message'])) {
                return $this->processResponse($response);
            }

            return $this->getAdministratedCompaniesv2($response);
        } catch (Exception $e) {
            $result = ['id' => false, 'name' => $e->getMessage()];
        }

        return $result;
    }

    protected function getAdministratedCompaniesv2($response)
    {
        $elements = $response['elements'];
        $companies = [];

        foreach ($elements as $company) {
            $isAdministrated = ('ADMINISTRATOR' === $company['role']) && ('APPROVED' === $company['state']);

            if (!$isAdministrated) {
                continue;
            }

            $item = $this->getCompanyv2($company['organizationalTarget']);

            if (($item) && (is_object($item))) {
                $companies[] = $item;
            }
        }

        return $companies;
    }

    protected function getCompanyv2($organizationId)
    {
        $api = $this->getLinkedInAPIv2();
        [$url, $li, $org, $id] = explode(':', $organizationId);

        $response = $api->api('GET', '/v2/organizations/'.$id);

        if (isset($response['message'])) {
            return $this->processResponse($response);
        }

        if (empty($response)) {
            return null;
        }

        $company = new StdClass();
        $company->id = $id;
        $company->name = $response['localizedName'];
        $company->url = 'https://www.linkedin.com/company/'.$response['vanityName'];

        return $company;
    }

    protected function sendMessageAPIv2($author, $message, $data)
    {
        $companyId = $this->get('company_id');

        if (empty($companyId)) {
            throw new Exception('Invalid Company. Please, select a Company.');
        }

        return parent::sendMessageAPIv2($companyId, $message, $data);
    }

    protected function getUrn($id)
    {
        return 'urn:li:company:'.$id;
    }

    protected function getMeAPIv2()
    {
        $api = $this->getLinkedInAPIv2();

        return $api->get('/v2/me?fields=id,firstName,lastName,vanityName');
    }
}
