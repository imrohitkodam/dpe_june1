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

class XTS_Google_Service_Pagespeedonline_LighthouseResultV5Categories extends XTS_Google_Model
{
  protected $internal_gapi_mappings = array(
        "bestPractices" => "best-practices",
  );
  protected $accessibilityType = 'XTS_Google_Service_Pagespeedonline_LighthouseCategoryV5';
  protected $accessibilityDataType = '';
  protected $bestPracticesType = 'XTS_Google_Service_Pagespeedonline_LighthouseCategoryV5';
  protected $bestPracticesDataType = '';
  protected $performanceType = 'XTS_Google_Service_Pagespeedonline_LighthouseCategoryV5';
  protected $performanceDataType = '';
  protected $pwaType = 'XTS_Google_Service_Pagespeedonline_LighthouseCategoryV5';
  protected $pwaDataType = '';
  protected $seoType = 'XTS_Google_Service_Pagespeedonline_LighthouseCategoryV5';
  protected $seoDataType = '';

  /**
   * @param Google_Service_Pagespeedonline_LighthouseCategoryV5
   */
  public function setAccessibility(XTS_Google_Service_Pagespeedonline_LighthouseCategoryV5 $accessibility)
  {
    $this->accessibility = $accessibility;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseCategoryV5
   */
  public function getAccessibility()
  {
    return $this->accessibility;
  }
  /**
   * @param Google_Service_Pagespeedonline_LighthouseCategoryV5
   */
  public function setBestPractices(XTS_Google_Service_Pagespeedonline_LighthouseCategoryV5 $bestPractices)
  {
    $this->bestPractices = $bestPractices;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseCategoryV5
   */
  public function getBestPractices()
  {
    return $this->bestPractices;
  }
  /**
   * @param Google_Service_Pagespeedonline_LighthouseCategoryV5
   */
  public function setPerformance(XTS_Google_Service_Pagespeedonline_LighthouseCategoryV5 $performance)
  {
    $this->performance = $performance;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseCategoryV5
   */
  public function getPerformance()
  {
    return $this->performance;
  }
  /**
   * @param Google_Service_Pagespeedonline_LighthouseCategoryV5
   */
  public function setPwa(XTS_Google_Service_Pagespeedonline_LighthouseCategoryV5 $pwa)
  {
    $this->pwa = $pwa;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseCategoryV5
   */
  public function getPwa()
  {
    return $this->pwa;
  }
  /**
   * @param Google_Service_Pagespeedonline_LighthouseCategoryV5
   */
  public function setSeo(XTS_Google_Service_Pagespeedonline_LighthouseCategoryV5 $seo)
  {
    $this->seo = $seo;
  }
  /**
   * @return Google_Service_Pagespeedonline_LighthouseCategoryV5
   */
  public function getSeo()
  {
    return $this->seo;
  }
}
