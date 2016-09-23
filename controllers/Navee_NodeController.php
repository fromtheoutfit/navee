<?php
namespace Craft;

/**
 * Events controller
 */
class Navee_NodeController extends BaseController {

  /**
   * Event index
   */
  public function actionNodeIndex()
  {
    $variables['navigations'] = craft()->navee_navigation->getAllNavigations();

    if (!sizeof($variables['navigations']))
    {
      craft()->request->redirect(UrlHelper::getCpUrl('navee/navigations/new'));
    }

    $this->renderTemplate('navee/index', $variables);
  }

  /**
   * Edit a node.
   *
   * @param array $variables
   * @throws HttpException
   */
  public function actionEditNode(array $variables = array())
  {
    // lets figure out which navigation this node belongs to
    if (!empty($variables['navigationHandle']))
    {
      $variables['navigation'] = craft()->navee_navigation->getNavigationByHandle($variables['navigationHandle']);
    }
    else if (!empty($variables['navigationId']))
    {
      $variables['navigation'] = craft()->navee_navigation->getNavigationById($variables['navigationId']);
    }

    // if we don't have a navigation, freak out and 404
    if (empty($variables['navigation']))
    {
      throw new HttpException(404);
    }

    // get different link element types
    $variables['entryElementType']    = craft()->elements->getElementType('Entry');
    $variables['assetElementType']    = craft()->elements->getElementType('Asset');
    $variables['categoryElementType'] = craft()->elements->getElementType('Category');

    // check to make sure assets and categories exist
    $variables['assetSourcesExist'] = sizeof(craft()->assetSources->getAllSources()) ? true : false;
    $variables['categoriesExist']   = sizeof(craft()->categories->getAllGroups()) ? true : false;

    // set up the different link Types
    $variables['linkTypes'] = array(
      'entryId'   => 'Entry',
      'customUri' => 'Custom',
    );

    // if asset sources exist, add Asset as a link type option
    if ($variables['assetSourcesExist'])
    {
      $variables['linkTypes']['assetId'] = 'Asset';
    }

    // if asset sources exist, add Asset as a link type option
    if ($variables['categoriesExist'])
    {
      $variables['linkTypes']['categoryId'] = 'Category';
    }

    // Now let's set up the actual node
    if (empty($variables['node']))
    {
      if (!empty($variables['nodeId']))
      {
        $variables['node'] = craft()->navee_node->getNodeById($variables['nodeId']);

        if (!$variables['node'])
        {
          throw new HttpException(404);
        }
      }
      else
      {
        $variables['node']               = new Navee_NodeModel();
        $variables['node']->navigationId = $variables['navigation']->id;
      }
    }

    foreach ($variables['navigation']->getFieldLayout()->getTabs() as $index => $tab)
    {
      // Do any of the fields on this tab have errors?
      $hasErrors = false;

      if ($variables['node']->hasErrors())
      {
        foreach ($tab->getFields() as $field)
        {
          if ($variables['node']->getErrors($field->getField()->handle))
          {
            $hasErrors = true;
            break;
          }
        }
      }

      $variables['tabs'][] = array(
        'label' => $tab->name,
        'url'   => '#tab' . ($index + 1),
        'class' => ($hasErrors ? 'error' : null)
      );
    }

    if (!$variables['node']->id)
    {
      $variables['title']            = Craft::t('Create a new node');
      $variables['entryElements']    = array();
      $variables['assetElements']    = array();
      $variables['categoryElements'] = array();
    }
    else
    {
      $variables['title']              = $variables['node']->title;
      $variables['entryElements'][]    = craft()->entries->getEntryById($variables['node']->entryId);
      $variables['assetElements'][]    = craft()->assets->getFileById($variables['node']->assetId);
      $variables['categoryElements'][] = craft()->categories->getCategoryById($variables['node']->categoryId);

      // get a link to the element in the cp if it is an entry or category
      switch ($variables['node']->linkType)
      {
        case 'entryId':
          $criteria     = craft()->elements->getCriteria(ElementType::Entry);
          $criteria->id = $variables['node']->entryId;
          $entry        = $criteria->first();
          if ($entry)
          {
            $variables['node']->linkedElementCpEditUrl = $entry->getCpEditUrl();
          }
          $variables['node']->linkedElementType = 'Entry';
          break;
        case 'categoryId':
          $criteria                                  = craft()->elements->getCriteria(ElementType::Category);
          $criteria->id                              = $variables['node']->categoryId;
          $entry                                     = $criteria->first();
          $variables['node']->linkedElementCpEditUrl = $entry->getCpEditUrl();
          $variables['node']->linkedElementType      = 'Category';
          break;
      }

    }

    if ($variables['navigation']->maxLevels != 1)
    {
      $variables['elementType'] = new ElementTypeVariable(craft()->elements->getElementType('Navee_Node'));

      // Define the parent options criteria
      $variables['parentOptionCriteria'] = array(
        'navigationId'  => $variables['navigation']->id,
        'status'        => null,
        'localeEnabled' => null,
      );

      if ($variables['navigation']->maxLevels)
      {
        $variables['parentOptionCriteria']['level'] = '< ' . $variables['navigation']->maxLevels;
      }

      if ($variables['node']->id)
      {
        // Prevent the current node, or any of its descendants, from being options
        $idParam = array('and', 'not ' . $variables['node']->id);

        $descendantCriteria                = craft()->elements->getCriteria('Navee_Node');
        $descendantCriteria->descendantOf  = $variables['node'];
        $descendantCriteria->status        = null;
        $descendantCriteria->localeEnabled = null;
        $descendantIds                     = $descendantCriteria->ids();

        foreach ($descendantIds as $id)
        {
          $idParam[] = 'not ' . $id;
        }

        $variables['parentOptionCriteria']['id'] = $idParam;
      }

      // Get the initially selected parent
      $parentId = craft()->request->getParam('parentId');

      if ($parentId === null && $variables['node']->id)
      {
        $parentIds = $variables['node']->getAncestors(1)->status(null)->localeEnabled(null)->ids();

        if ($parentIds)
        {
          $parentId = $parentIds[0];
        }
      }

      if ($parentId)
      {
        $variables['parent'] = craft()->navee_node->getNodeById($parentId);
      }
    }


    // Breadcrumbs
    $variables['crumbs'] = array(
      array('label' => Craft::t('Navee'), 'url' => UrlHelper::getUrl('navee')),
      array('label' => $variables['navigation']->name, 'url' => UrlHelper::getUrl('navee'))
    );

    // Set the "Continue Editing" URL
    $variables['continueEditingUrl'] = 'navee/node/' . $variables['navigation']->handle . '/{id}';

    // Render the template!
    $this->renderTemplate('navee/nodes/_edit', $variables);
  }


  /**
   * Saves an event.
   */
  public function actionSaveNode()
  {
    $this->requirePostRequest();

    $nodeId = craft()->request->getPost('nodeId');

    if ($nodeId)
    {
      $node = craft()->navee_node->getNodeById($nodeId);

      if (!$node)
      {
        throw new Exception(Craft::t('No node exists with the ID “{id}”', array('id' => $eventId)));
      }
    }
    else
    {
      $node = new Navee_NodeModel();
    }

    // Set the event attributes, defaulting to the existing values for whatever is missing from the post data
    $node->navigationId        = craft()->request->getPost('navigationId', $node->navigationId);
    $node->linkType            = craft()->request->getPost('linkType', $node->linkType);
    $node->customUri           = (craft()->request->getPost('customUri')) ? craft()->request->getPost('customUri') : '';
    $node->class               = (craft()->request->getPost('class')) ? craft()->request->getPost('class') : '';
    $node->idAttr              = (craft()->request->getPost('idAttr')) ? craft()->request->getPost('idAttr') : '';
    $node->rel                 = (craft()->request->getPost('rel')) ? craft()->request->getPost('rel') : '';
    $node->name                = (craft()->request->getPost('name')) ? craft()->request->getPost('name') : '';
    $node->titleAttr           = (craft()->request->getPost('titleAttr')) ? craft()->request->getPost('titleAttr') : '';
    $node->accessKey           = (craft()->request->getPost('accessKey')) ? craft()->request->getPost('accessKey') : '';
    $node->regex               = (craft()->request->getPost('regex')) ? craft()->request->getPost('regex') : '';
    $node->target              = (craft()->request->getPost('target')) ? craft()->request->getPost('target') : '';
    $node->includeInNavigation = (craft()->request->getPost('includeInNavigation')) ? craft()->request->getPost('includeInNavigation') : false;
    $node->passive             = (craft()->request->getPost('passive')) ? craft()->request->getPost('passive') : false;
    $node->enabled             = (bool) craft()->request->getPost('enabled', $node->enabled);
    $node->userGroups          = (craft()->request->getPost('userGroups')) ? craft()->request->getPost('userGroups') : array();

    // entry
    $entryId = craft()->request->getPost('entryId');
    if (is_array($entryId))
    {
      $node->entryId = isset($entryId[0]) ? $entryId[0] : null;
    }

    // asset
    $assetId = craft()->request->getPost('assetId');
    if (is_array($assetId))
    {
      $node->assetId = isset($assetId[0]) ? $assetId[0] : null;
    }

    // category
    $categoryId = craft()->request->getPost('categoryId');
    if (is_array($categoryId))
    {
      $node->categoryId = isset($categoryId[0]) ? $categoryId[0] : null;
    }

    $node->getContent()->title = craft()->request->getPost('title', $node->title);
    $node->setContentFromPost('fields');

    $parentId = craft()->request->getPost('parentId');

    if (is_array($parentId))
    {
      $parentId = isset($parentId[0]) ? $parentId[0] : null;
    }

    $node->newParentId = $parentId;

    if (craft()->navee_node->saveNode($node))
    {
      craft()->userSession->setNotice(Craft::t('Node saved.'));
      $this->redirectToPostedUrl($node);
    }
    else
    {
      craft()->userSession->setError(Craft::t('Couldn’t save node.'));

      // Send the event back to the template
      craft()->urlManager->setRouteVariables(array(
        'node' => $node
      ));
    }
  }

  /**
   * Deletes an event.
   */
  public function actionDeleteEvent()
  {
    $this->requirePostRequest();

    $eventId = craft()->request->getRequiredPost('eventId');

    if (craft()->elements->deleteElementById($eventId))
    {
      craft()->userSession->setNotice(Craft::t('Event deleted.'));
      $this->redirectToPostedUrl();
    }
    else
    {
      craft()->userSession->setError(Craft::t('Couldn’t delete event.'));
    }
  }
}
