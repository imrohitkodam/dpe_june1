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

class XTS_Google_Service_Keep_CreatePermissionRequest extends XTS_Google_Model
{
  public $parent;
  protected $permissionType = 'XTS_Google_Service_Keep_Permission';
  protected $permissionDataType = '';

  public function setParent($parent)
  {
    $this->parent = $parent;
  }
  public function getParent()
  {
    return $this->parent;
  }
  /**
   * @param Google_Service_Keep_Permission
   */
  public function setPermission(XTS_Google_Service_Keep_Permission $permission)
  {
    $this->permission = $permission;
  }
  /**
   * @return Google_Service_Keep_Permission
   */
  public function getPermission()
  {
    return $this->permission;
  }
}
