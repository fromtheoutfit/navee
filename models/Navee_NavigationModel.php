<?php

namespace Craft;

/**
 * Navigation Model
 * Provides a read-only object representing an ingredient, which is returned
 * by our service class and can be used in our templates and controllers.
 */
class Navee_NavigationModel extends BaseModel {

  /**
   * Defines what is returned when someone puts {{ ingredient }} directly
   * in their template.
   *
   * @return string
   */
  public function __toString()
  {
    return $this->name;
  }

  /**
   * Define the attributes this model will have.
   *
   * @return array
   */
  public function defineAttributes()
  {
    return array(
      'id'            => AttributeType::Number,
      'name'          => AttributeType::String,
      'handle'          => AttributeType::Handle,
      'fieldLayoutId' => AttributeType::Number,
      'maxLevels'     => AttributeType::Number,
      'structureId'   => AttributeType::Number,
    );
  }

  /**
   * @return array
   */
  public function behaviors()
  {
    return array(
      'fieldLayout' => new FieldLayoutBehavior('Navee_Node'),
    );
  }
}
