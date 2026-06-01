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

class XTS_Google_Service_Keep_Section extends XTS_Google_Model
{
  protected $listType = 'XTS_Google_Service_Keep_ListContent';
  protected $listDataType = '';
  protected $textType = 'XTS_Google_Service_Keep_TextContent';
  protected $textDataType = '';

  /**
   * @param Google_Service_Keep_ListContent
   */
  public function setList(XTS_Google_Service_Keep_ListContent $list)
  {
    $this->list = $list;
  }
  /**
   * @return Google_Service_Keep_ListContent
   */
  public function getList()
  {
    return $this->list;
  }
  /**
   * @param Google_Service_Keep_TextContent
   */
  public function setText(XTS_Google_Service_Keep_TextContent $text)
  {
    $this->text = $text;
  }
  /**
   * @return Google_Service_Keep_TextContent
   */
  public function getText()
  {
    return $this->text;
  }
}
