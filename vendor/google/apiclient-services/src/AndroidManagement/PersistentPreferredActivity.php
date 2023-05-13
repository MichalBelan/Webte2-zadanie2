<?php
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

namespace Google\Service\AndroidManagement;

class PersistentPreferredActivity extends \Google\Collection
{
  protected $collection_key = 'categories';
  /**
   * @var string[]
   */
  public $actions = [];
  /**
   * @var string[]
   */
  public $categories = [];
  /**
   * @var string
   */
  public $receiverActivity;

  /**
   * @param string[]
   */
  public function setActions($actions)
  {
    $this->actions = $actions;
  }
  /**
   * @return string[]
   */
  public function getActions()
  {
    return $this->actions;
  }
  /**
   * @param string[]
   */
  public function setCategories($categories)
  {
    $this->categories = $categories;
  }
  /**
   * @return string[]
   */
  public function getCategories()
  {
    return $this->categories;
  }
  /**
   * @param string
   */
  public function setReceiverActivity($receiverActivity)
  {
    $this->receiverActivity = $receiverActivity;
  }
  /**
   * @return string
   */
  public function getReceiverActivity()
  {
    return $this->receiverActivity;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PersistentPreferredActivity::class, 'Google_Service_AndroidManagement_PersistentPreferredActivity');