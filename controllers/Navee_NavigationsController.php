<?php
namespace Craft;

class Navee_NavigationsController extends BaseController {

  /**
   * Saves a navigation
   */
  public function actionSaveNavigation()
  {
    $this->requirePostRequest();

    $navigation = new Navee_NavigationModel();

    // Shared attributes
    $navigation->id        = craft()->request->getPost('navigationId');
    $navigation->name      = craft()->request->getPost('name');
    $navigation->handle      = craft()->request->getPost('handle');
    $navigation->maxLevels = craft()->request->getPost('maxLevels');
    $navigation->structureId     = craft()->request->getPost('structureId');

    // Set the field layout
    $fieldLayout       = craft()->fields->assembleLayoutFromPost();
    $fieldLayout->type = 'Navee_Node';
    $navigation->setFieldLayout($fieldLayout);

    // Save it
    if (craft()->navee_navigation->saveNavigation($navigation))
    {
      craft()->userSession->setNotice(Craft::t('Navigation saved.'));
      $this->redirectToPostedUrl($navigation);
    }
    else
    {
      craft()->userSession->setError(Craft::t('Couldn’t save navigation.'));
    }

    // Send the calendar back to the template
    craft()->urlManager->setRouteVariables(array(
      'navigation' => $navigation
    ));
  }

  /**
   * Navigation index
   */
  public function actionNavigationIndex()
  {
    $variables['navigations'] = craft()->navee_navigation->getAllNavigations();

    $this->renderTemplate('navee/navigations', $variables);
  }


  /**
   * Edit a calendar.
   *
   * @param array $variables
   * @throws HttpException
   * @throws Exception
   */
  public function actionEditNavigation(array $variables = array())
  {
    $variables['brandNewNavigation'] = false;

    if (!empty($variables['navigationId']))
    {
      if (empty($variables['navigation']))
      {
        $variables['navigation'] = craft()->navee_navigation->getNavigationById($variables['navigationId']);

        if (!$variables['navigation'])
        {
          throw new HttpException(404);
        }
      }

      $variables['title'] = $variables['navigation']->name;
    }
    else
    {
      if (empty($variables['navigation']))
      {
        $variables['navigation']         = new Navee_NavigationModel();
        $variables['brandNewNavigation'] = true;
      }

      $variables['title'] = Craft::t('Create a new navigation');
    }

    $variables['crumbs'] = array(
      array('label' => Craft::t('Navee'), 'url' => UrlHelper::getUrl('navee')),
      array('label' => Craft::t('Navigations'), 'url' => UrlHelper::getUrl('navee/navigations')),
    );

    $this->renderTemplate('navee/navigations/_edit', $variables);
  }

  /**
   * Deletes a navigation.
   */
  public function actionDeleteNavigation()
  {
    $this->requirePostRequest();
    $this->requireAjaxRequest();

    $navigationId = craft()->request->getRequiredPost('id');

    craft()->navee_navigation->deleteNavigationById($navigationId);
    $this->returnJson(array('success' => true));
  }
}