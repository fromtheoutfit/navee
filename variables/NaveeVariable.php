<?php

namespace Craft;

/**
 * Navee Variable provides access to database objects from templates
 */
class NaveeVariable {

  function nav($navigationHandle, $config = array())
  {
    $data = '';
    craft()->navee->setConfig($config);
    $nodes = craft()->navee->getNav($navigationHandle);

    if (sizeof($nodes))
    {
      $var = array(
        'nodes'  => $nodes,
        'config' => craft()->navee->config,
      );

      $oldPath = craft()->templates->getTemplatesPath();
      $newPath = craft()->path->getPluginsPath() . 'navee/templates';
      craft()->templates->setTemplatesPath($newPath);
      $data = craft()->templates->render('variables/nav', $var);
      craft()->templates->setTemplatesPath($oldPath);
    }

    return TemplateHelper::getRaw(rtrim($data));
  }

  function getNav($navigationHandle, $config = array())
  {
    craft()->navee->setConfig($config);
    $data = craft()->navee->getNav($navigationHandle);
    return $data;
  }
}
