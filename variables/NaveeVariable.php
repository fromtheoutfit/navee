<?php

namespace Craft;

/**
 * Navee Variable provides access to database objects from templates
 */
class NaveeVariable {

  function nav($navigationHandle, $config = array())
  {
    craft()->navee->setConfig($config);
    $data = craft()->navee->getNav($navigationHandle);
    return TemplateHelper::getRaw(rtrim($data));
  }

  function getCustomNav($navigationHandle, $config)
  {

  }
}
