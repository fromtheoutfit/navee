<?php
/**
 * Navee plugin for Craft CMS
 * Navee_ConfigModel Model
 *
 * @author    The Outfit
 * @copyright Copyright (c) 2016 The Outfit
 * @link      http://fromtheoutfit.com
 * @package   Navee
 * @since     1.0.0
 */

namespace Craft;

class Navee_ConfigModel extends BaseModel {

  /**
   * Defines this model's attributes.
   *
   * @return array
   */
  protected function defineAttributes()
  {
    // todo
    // skipClosedEntries
    // levelCount

    return array_merge(parent::defineAttributes(), array(
      'activeClass'               => array(AttributeType::String, 'default' => 'active'),
      'activeClassOnAncestors'    => array(AttributeType::Bool, 'default' => 'false'),
      'ancestorActiveClass'       => array(AttributeType::String, 'default' => 'active'),
      'breadcrumbs'               => array(AttributeType::Bool, 'default' => false),
      'class'                     => array(AttributeType::String, 'default' => ''),
      'disableActiveClass'        => array(AttributeType::Bool, 'default' => false),
      'id'                        => array(AttributeType::String, 'default' => ''),
      'ignoreIncludeInNavigation' => array(AttributeType::Bool, 'default' => false),
      'maxDepth'                  => array(AttributeType::Number, 'default' => 0),
      'reverseNodes'              => array(AttributeType::Bool, 'default' => false),
      'skipDisabledEntries'       => array(AttributeType::Bool, 'default' => false),
      'startDepth'                => array(AttributeType::Number, 'default' => 1),
      'startWithActive'           => array(AttributeType::Bool, 'default' => false),
      'startWithAncestorOfActive' => array(AttributeType::Bool, 'default' => false),
      'startWithChildrenOfActive' => array(AttributeType::Bool, 'default' => false),
      'startWithNodeId'           => array(AttributeType::Number, 'default' => 0),
      'startWithChildrenOfNodeId' => array(AttributeType::Number, 'default' => 0),
      'startWithSiblingsOfActive' => array(AttributeType::Bool, 'default' => false),
      'startXLevelsAboveActive'   => array(AttributeType::Number, 'default' => 0),
      'userGroups'                => array(AttributeType::Mixed, 'default' => ''),
      'wrapType'                  => array(AttributeType::String, 'default' => 'ul'),
    ));
  }

  /**
   * Returns whether the current user can edit the element.
   *
   * @return bool
   */
  public function isEditable()
  {
  }

  /**
   * Returns the element's CP edit URL.
   *
   * @return string|false
   */
  public function getCpEditUrl()
  {
  }
}