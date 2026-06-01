<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

class XTS_Google_Service_Pagespeedonline_LighthouseResultV5 extends XTS_Google_Collection
{
  protected $collection_key = 'stackPacks';
  protected $auditsType = 'XTS_Google_Service_Pagespeedonline_LighthouseAuditResultV5';
  protected $auditsDataType = 'map';
  protected $categoriesType = 'XTS_Google_Service_Pagespeedonline_LighthouseResultV5Categories';
  protected $categoriesDataType = '';
  protected $categoryGroupsType = 'XTS_Google_Service_Pagespeedonline_LighthouseResultV5CategoryGroupsElement';
  protected $categoryGroupsDataType = 'map';
  protected $configSettingsType = 'XTS_Google_Service_Pagespeedonline_LighthouseResultV5ConfigSettings';
  protected $configSettingsDataType = '';
  protected $environmentType = 'XTS_Google_Service_Pagespeedonline_LighthouseResultV5Environment';
  protected $environmentDataType = '';
  public $fetchTime;
  public $finalUrl;
  protected $i18nType = 'XTS_Google_Service_Pagespeedonline_LighthouseResultV5I18n';
  protected $i18nDataType = '';
  public $lighthouseVersion;
  public $requestedUrl;
  public $runWarnings;
  protected $runtimeErrorType = 'XTS_Google_Service_Pagespeedonline_LighthouseResultV5RuntimeError';
  protected $runtimeErrorDataType = '';
  protected $stackPacksType = 'XTS_Google_Service_Pagespeedonline_LighthouseResultV5StackPacks';
  protected $stackPacksDataType = 'array';
  protected $timingType = 'XTS_Google_Service_Pagespeedonline_LighthouseResultV5Timing';
  protected $timingDataType = '';
  public $userAgent;

  /**
   * @param Google_Service_Pagespeedonline_LighthouseAuditResultV5
   */
  public function setAudits($audits)
  {
    $this->audits = $audits;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseAuditResultV5
   */
  public function getAudits()
  {
    return $this->audits;
  }
  /**
   * @param Google_Service_Pagespeedonline_LighthouseResultV5Categories
   */
  public function setCategories(XTS_Google_Service_Pagespeedonline_LighthouseResultV5Categories $categories)
  {
    $this->categories = $categories;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseResultV5Categories
   */
  public function getCategories()
  {
    return $this->categories;
  }
  /**
   * @param Google_Service_Pagespeedonline_LighthouseResultV5CategoryGroupsElement
   */
  public function setCategoryGroups($categoryGroups)
  {
    $this->categoryGroups = $categoryGroups;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseResultV5CategoryGroupsElement
   */
  public function getCategoryGroups()
  {
    return $this->categoryGroups;
  }
  /**
   * @param Google_Service_Pagespeedonline_LighthouseResultV5ConfigSettings
   */
  public function setConfigSettings(XTS_Google_Service_Pagespeedonline_LighthouseResultV5ConfigSettings $configSettings)
  {
    $this->configSettings = $configSettings;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseResultV5ConfigSettings
   */
  public function getConfigSettings()
  {
    return $this->configSettings;
  }
  /**
   * @param Google_Service_Pagespeedonline_LighthouseResultV5Environment
   */
  public function setEnvironment(XTS_Google_Service_Pagespeedonline_LighthouseResultV5Environment $environment)
  {
    $this->environment = $environment;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseResultV5Environment
   */
  public function getEnvironment()
  {
    return $this->environment;
  }
  public function setFetchTime($fetchTime)
  {
    $this->fetchTime = $fetchTime;
  }
  public function getFetchTime()
  {
    return $this->fetchTime;
  }
  public function setFinalUrl($finalUrl)
  {
    $this->finalUrl = $finalUrl;
  }
  public function getFinalUrl()
  {
    return $this->finalUrl;
  }
  /**
   * @param Google_Service_Pagespeedonline_LighthouseResultV5I18n
   */
  public function setI18n(XTS_Google_Service_Pagespeedonline_LighthouseResultV5I18n $i18n)
  {
    $this->i18n = $i18n;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseResultV5I18n
   */
  public function getI18n()
  {
    return $this->i18n;
  }
  public function setLighthouseVersion($lighthouseVersion)
  {
    $this->lighthouseVersion = $lighthouseVersion;
  }
  public function getLighthouseVersion()
  {
    return $this->lighthouseVersion;
  }
  public function setRequestedUrl($requestedUrl)
  {
    $this->requestedUrl = $requestedUrl;
  }
  public function getRequestedUrl()
  {
    return $this->requestedUrl;
  }
  public function setRunWarnings($runWarnings)
  {
    $this->runWarnings = $runWarnings;
  }
  public function getRunWarnings()
  {
    return $this->runWarnings;
  }
  /**
   * @param Google_Service_Pagespeedonline_LighthouseResultV5RuntimeError
   */
  public function setRuntimeError(XTS_Google_Service_Pagespeedonline_LighthouseResultV5RuntimeError $runtimeError)
  {
    $this->runtimeError = $runtimeError;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseResultV5RuntimeError
   */
  public function getRuntimeError()
  {
    return $this->runtimeError;
  }
  /**
   * @param Google_Service_Pagespeedonline_LighthouseResultV5StackPacks
   */
  public function setStackPacks($stackPacks)
  {
    $this->stackPacks = $stackPacks;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseResultV5StackPacks
   */
  public function getStackPacks()
  {
    return $this->stackPacks;
  }
  /**
   * @param Google_Service_Pagespeedonline_LighthouseResultV5Timing
   */
  public function setTiming(XTS_Google_Service_Pagespeedonline_LighthouseResultV5Timing $timing)
  {
    $this->timing = $timing;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseResultV5Timing
   */
  public function getTiming()
  {
    return $this->timing;
  }
  public function setUserAgent($userAgent)
  {
    $this->userAgent = $userAgent;
  }
  public function getUserAgent()
  {
    return $this->userAgent;
  }
}
