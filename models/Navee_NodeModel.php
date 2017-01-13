<?php
namespace Craft;

/**
 * Events - Event model
 */
class Navee_NodeModel extends BaseElementModel {

  protected $elementType = 'Navee_Node';

  /**
   * @access protected
   * @return array
   */
  protected function defineAttributes()
  {
    return array_merge(parent::defineAttributes(), array(
      'navigationId'           => AttributeType::Number,
      'linkType'               => array(AttributeType::Enum, 'values' => ['entryId', 'assetId', 'categoryId', 'customUri', 'none']),
      'entryId'                => AttributeType::Number,
      'assetId'                => AttributeType::Number,
      'categoryId'             => AttributeType::Number,
      'customUri'              => array(AttributeType::String, 'default' => ''),
      'class'                  => array(AttributeType::String, 'default' => ''),
      'idAttr'                 => array(AttributeType::String, 'default' => ''),
      'rel'                    => array(AttributeType::String, 'default' => ''),
      'name'                   => array(AttributeType::String, 'default' => ''),
      'titleAttr'              => array(AttributeType::String, 'default' => ''),
      'accessKey'              => array(AttributeType::String, 'default' => ''),
      'target'                 => array(AttributeType::String, 'default' => ''),
      'includeInNavigation'    => array(AttributeType::Bool, 'default' => true),
      'passive'                => array(AttributeType::Bool, 'default' => false),
      'active'                 => array(AttributeType::Bool, 'default' => false),
      'ancestorActive'         => array(AttributeType::Bool, 'default' => false),
      'descendantActive'       => array(AttributeType::Bool, 'default' => false),
      'entryEnabled'           => array(AttributeType::Bool, 'default' => false),
      'link'                   => array(AttributeType::String, 'default' => ''),
      'text'                   => array(AttributeType::String, 'default' => ''),
      'entryLink'              => array(AttributeType::String, 'default' => ''),
      'categoryLink'           => array(AttributeType::String, 'default' => ''),
      'newParentId'            => AttributeType::Number,
      'siblingActive'          => array(AttributeType::Bool, 'default' => false),
      'userGroups'             => array(AttributeType::Mixed, 'default' => ''),
      'linkedElementCpEditUrl' => array(AttributeType::String, 'default' => ''),
      'linkedElementType'      => array(AttributeType::String, 'default' => ''),
      'regex'                  => array(AttributeType::String, 'default' => ''),
      'hasChildren'          => array(AttributeType::Bool, 'default' => false),
    ));
  }

  /**
   * Returns whether the current user can edit the element.
   *
   * @return bool
   */
  public function isEditable()
  {
    return true;
  }

  /**
   * Returns the element's CP edit URL.
   *
   * @return string|false
   */
  public function getCpEditUrl()
  {
    $navigation = $this->getNavigation();

    if ($navigation)
    {
      return UrlHelper::getCpUrl('navee/node/' . $navigation->handle . '/' . $this->id);
    }
  }


  /**
   * Returns the field layout used by this element.
   *
   * @return FieldLayoutModel|null
   */
  public function getFieldLayout()
  {
    $navigation = $this->getNavigation();

    if ($navigation)
    {
      return $navigation->getFieldLayout();
    }
  }

  /**
   * Returns the event's calendar.
   *
   * @return Events_CalendarModel|null
   */
  public function getNavigation()
  {
    if ($this->navigationId)
    {
      return craft()->navee_navigation->getNavigationById($this->navigationId);
    }
  }

  /**
   * Checks to see if a particular user group is associated with a node
   *
   * @access public
   * @param int $userGroupId
   * @return bool
   */

  public function userGroupSelected($userGroupId)
  {
    $data = false;

    if (is_array($this->userGroups))
    {
      return (in_array($userGroupId, $this->userGroups));
    }

    return $data;
  }

  /**
   * Adding links to the base getDescendants functionality
   *
   * @param null $dist
   * @return ElementCriteriaModel
   */

  public function getDescendants($dist = null)
  {
    $descendants = parent::getDescendants($dist);
    foreach ($descendants as $d)
    {
      craft()->navee->setLink($d);
    }

    return $descendants;
  }
}
