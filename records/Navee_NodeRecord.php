<?php
namespace Craft;

/**
 * Events - Event record
 */
class Navee_NodeRecord extends BaseRecord {

  /**
   * @return string
   */
  public function getTableName()
  {
    return 'navee_nodes';
  }

  /**
   * @access protected
   * @return array
   */
  protected function defineAttributes()
  {
    return array(
      'linkType'            => array(AttributeType::Enum, 'values' => ['entryId', 'assetId', 'categoryId', 'customUri', 'none']),
      'entryId'             => array(AttributeType::Number, 'required' => false),
      'assetId'             => array(AttributeType::Number, 'required' => false),
      'categoryId'          => array(AttributeType::Number, 'required' => false),
      'customUri'           => array(AttributeType::String, 'required' => ''),
      'class'               => array(AttributeType::String, 'required' => false),
      'idAttr'              => array(AttributeType::String, 'required' => false),
      'rel'                 => array(AttributeType::String, 'required' => false),
      'name'                => array(AttributeType::String, 'required' => false),
      'titleAttr'           => array(AttributeType::String, 'required' => false),
      'accessKey'           => array(AttributeType::String, 'required' => false),
      'target'              => array(AttributeType::String, 'required' => false),
      'includeInNavigation' => array(AttributeType::Bool, 'required' => true),
      'passive'             => array(AttributeType::Bool, 'required' => true),
      'userGroups'          => array(AttributeType::Mixed, 'required' => false),
      'regex'           => array(AttributeType::String, 'required' => false),
    );
  }


  /**
   * @return array
   */
  public function defineRelations()
  {
    return array(
      'element'    => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
      'navigation' => array(static::BELONGS_TO, 'Navee_NavigationRecord', 'required' => true, 'onDelete' => static::CASCADE),
      'entry'      => array(static::HAS_ONE, 'EntryRecord', 'entryId', 'required' => false),
      'asset'      => array(static::HAS_ONE, 'AssetRecord', 'assetId', 'required' => false),
      'category'   => array(static::HAS_ONE, 'CategoryRecord', 'categoryId', 'required' => false),
    );
  }
}
