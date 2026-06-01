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

class XTS_Google_Service_Keep_Permission extends XTS_Google_Model
{
  public $deleted;
  public $email;
  protected $familyType = 'XTS_Google_Service_Keep_Family';
  protected $familyDataType = '';
  protected $groupType = 'XTS_Google_Service_Keep_Group';
  protected $groupDataType = '';
  public $name;
  public $role;
  protected $userType = 'XTS_Google_Service_Keep_User';
  protected $userDataType = '';

  public function setDeleted($deleted)
  {
    $this->deleted = $deleted;
  }
  public function getDeleted()
  {
    return $this->deleted;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  /**
   * @param Google_Service_Keep_Family
   */
  public function setFamily(XTS_Google_Service_Keep_Family $family)
  {
    $this->family = $family;
  }
  /**
   * @return Google_Service_Keep_Family
   */
  public function getFamily()
  {
    return $this->family;
  }
  /**
   * @param Google_Service_Keep_Group
   */
  public function setGroup(XTS_Google_Service_Keep_Group $group)
  {
    $this->group = $group;
  }
  /**
   * @return Google_Service_Keep_Group
   */
  public function getGroup()
  {
    return $this->group;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setRole($role)
  {
    $this->role = $role;
  }
  public function getRole()
  {
    return $this->role;
  }
  /**
   * @param Google_Service_Keep_User
   */
  public function setUser(XTS_Google_Service_Keep_User $user)
  {
    $this->user = $user;
  }
  /**
   * @return Google_Service_Keep_User
   */
  public function getUser()
  {
    return $this->user;
  }
}
