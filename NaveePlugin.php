<?php
namespace Craft;

class NaveePlugin extends BasePlugin {

  public function getName()
  {
    return Craft::t('Navee');
  }

  public function getVersion()
  {
    return '1.3.0';
  }

  public function getDeveloper()
  {
    return 'The Outfit, Inc';
  }

  public function getDeveloperUrl()
  {
    return 'http://fromtheoutfit.com/navee';
  }

  public function hasCpSection()
  {
    return true;
  }

  public function getDocumentationUrl()
  {
    return 'https://github.com/fromtheoutfit/navee/wiki';
  }

  public function getReleaseFeedUrl()
  {
    return 'https://raw.githubusercontent.com/fromtheoutfit/navee/master/releases.json';
  }

  /**
   * Register control panel routes
   */
  public function registerCpRoutes()
  {
    return array(
      'navee'                                                        => array('action' => 'navee/node/nodeIndex'),
      'navee/navigations'                                            => array('action' => 'navee/navigations/navigationIndex'),
      'navee/navigations/new'                                        => array('action' => 'navee/navigations/editNavigation'),
      'navee/navigations/(?P<navigationId>\d+)'                      => array('action' => 'navee/navigations/editNavigation'),
      'navee\/node\/(?P<navigationHandle>{handle})\/new'             => array('action' => 'navee/node/editNode'),
      'navee\/node\/(?P<navigationHandle>{handle})\/(?P<nodeId>\d+)' => array('action' => 'navee/node/editNode'),
    );
  }


}