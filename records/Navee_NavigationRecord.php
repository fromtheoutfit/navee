<?php
namespace Craft;

class Navee_NavigationRecord extends BaseRecord {

  public function getTableName()
  {
    return 'navee_navigations';
  }

  protected function defineAttributes()
  {
    return array(
      'name'           => array(AttributeType::String, 'required' => true, 'unique' => true),
      'handle'         => array(AttributeType::Handle, 'required' => true, 'unique' => true),
      'maxLevels'      => AttributeType::Number,
      'fieldLayoutId'  => AttributeType::Number,
      'structureId'    => AttributeType::Number,
      'showClass'      => array(AttributeType::Bool, 'default' => false),
      'showId'         => array(AttributeType::Bool, 'default' => false),
      'showRel'        => array(AttributeType::Bool, 'default' => false),
      'showName'       => array(AttributeType::Bool, 'default' => false),
      'showTitle'      => array(AttributeType::Bool, 'default' => false),
      'showAccessKey'  => array(AttributeType::Bool, 'default' => false),
      'showTarget'     => array(AttributeType::Bool, 'default' => false),
      'showUserGroups' => array(AttributeType::Bool, 'default' => false),
    );
  }

  public function defineRelations()
  {
    return array(
      'fieldLayout' => array(static::BELONGS_TO, 'FieldLayoutRecord', 'onDelete' => static::SET_NULL),
      'creator'     => array(static::BELONGS_TO, 'UserRecord', 'required' => false, 'onDelete' => static::SET_NULL),
    );
  }

  public function create()
  {
    $class  = get_class($this);
    $record = new $class();

    return $record;
  }

  /**
   * @return array
   */
  public function scopes()
  {
    return array(
      'ordered' => array('order' => 'name'),
    );
  }
}