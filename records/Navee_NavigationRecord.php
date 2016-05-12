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
      'name'          => array(AttributeType::String, 'required' => true, 'unique' => true),
      'handle'          => array(AttributeType::Handle, 'required' => true, 'unique' => true),
      'maxLevels'      => AttributeType::Number,
      'fieldLayoutId' => AttributeType::Number,
      'structureId'   => AttributeType::Number,
    );
  }

  public function defineRelations()
  {
    return array(
      'fieldLayout' => array(static::BELONGS_TO, 'FieldLayoutRecord', 'onDelete' => static::SET_NULL),
      'creator'     => array(static::BELONGS_TO, 'UserRecord', 'required' => false, 'onDelete' => static::SET_NULL),
    );
  }

//  /**
//   * @return array
//   */
//  public function defineIndexes()
//  {
//    return array(
//      array('columns' => array('name'), 'unique' => true),
//      array('columns' => array('handle'), 'unique' => true),
//    );
//  }

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