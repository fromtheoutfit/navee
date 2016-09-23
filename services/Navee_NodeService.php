<?php
namespace Craft;

use Imagine\Exception\Exception;

/**
 * Events service
 */
class Navee_NodeService extends BaseApplicationComponent {

  /**
   * Returns a node by its ID.
   *
   * @param int $nodeId
   * @return Navee_NodeModel|null
   */
  public function getNodeById($nodeId)
  {
    return craft()->elements->getElementById($nodeId, 'Navee_Node');
  }

  /**
   * Saves a node
   *
   * @access public
   * @param Navee_NodeModel $node
   * @return bool
   * @throws Exception
   * @throws \CDbException
   * @throws \Exception
   */
  public function saveNode(Navee_NodeModel $node)
  {
    $isNewNode    = !$node->id;
    $hasNewParent = $this->_checkForNewParent($node);

    if ($hasNewParent)
    {
      if ($node->newParentId)
      {
        $parentNode = $this->getNodeById($node->newParentId);

        if (!$parentNode)
        {
          throw new Exception(Craft::t('No node exists with the ID “{id}”', array('id' => $node->newParentId)));
        }
      }
      else
      {
        $parentNode = null;
      }

      $node->setParent($parentNode);
    }

    // Event data
    if (!$isNewNode)
    {
      $nodeRecord = Navee_NodeRecord::model()->findById($node->id);

      if (!$nodeRecord)
      {
        throw new Exception(Craft::t('No node exists with the ID “{id}”', array('id' => $node->id)));
      }
    }
    else
    {
      $nodeRecord = new Navee_NodeRecord();
    }

    $nodeRecord->navigationId        = $node->navigationId;
    $nodeRecord->linkType            = $node->linkType;
    $nodeRecord->entryId             = $node->entryId;
    $nodeRecord->assetId             = $node->assetId;
    $nodeRecord->categoryId          = $node->categoryId;
    $nodeRecord->customUri           = $node->customUri;
    $nodeRecord->class               = $node->class;
    $nodeRecord->idAttr              = $node->idAttr;
    $nodeRecord->rel                 = $node->rel;
    $nodeRecord->name                = $node->name;
    $nodeRecord->titleAttr           = $node->titleAttr;
    $nodeRecord->accessKey           = $node->accessKey;
    $nodeRecord->regex               = $node->regex;
    $nodeRecord->target              = $node->target;
    $nodeRecord->includeInNavigation = $node->includeInNavigation;
    $nodeRecord->passive             = $node->passive;
    $nodeRecord->userGroups          = $node->userGroups;
    $nodeRecord->validate();
    $node->addErrors($nodeRecord->getErrors());

    if (!$node->hasErrors())
    {
      $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
      try
      {
        // Fire an 'onBeforeSaveEvent' event
        $this->onBeforeSaveNode(new Event($this, array(
          'node'      => $node,
          'isNewNode' => $isNewNode
        )));

        if (craft()->elements->saveElement($node))
        {
          // Now that we have an element ID, save it on the other stuff
          if ($isNewNode)
          {
            $nodeRecord->id = $node->id;
          }

          $nodeRecord->save(false);

          if ($hasNewParent)
          {
            if (!$node->newParentId)
            {
              craft()->structures->appendToRoot($node->getNavigation()->structureId, $node);
            }
            else
            {
              craft()->structures->append($node->getNavigation()->structureId, $node, $parentNode);
            }
          }

          craft()->elements->updateDescendantSlugsAndUris($node);

          // Fire an 'onSaveEvent' event
          $this->onSaveNode(new Event($this, array(
            'node'      => $node,
            'isNewNode' => $isNewNode
          )));

          if ($transaction !== null)
          {
            $transaction->commit();
          }

          return true;
        }
      } catch (\Exception $e)
      {
        if ($transaction !== null)
        {
          $transaction->rollback();
        }

        throw $e;
      }
    }

    return false;
  }

  // Events

  /**
   * Fires an 'onBeforeSaveEvent' event.
   *
   * @param Event $event
   */
  public function onBeforeSaveNode(Event $event)
  {
    $this->raiseEvent('onBeforeSaveNode', $event);
  }

  /**
   * Fires an 'onSaveEvent' event.
   *
   * @param Event $event
   */
  public function onSaveNode(Event $event)
  {
    $this->raiseEvent('onSaveNode', $event);
  }

  /**
   * Checks if an node was submitted with a new parent node selected.
   *
   * @param Navee_NodeModel $node
   * @return bool
   */
  private function _checkForNewParent(Navee_NodeModel $node)
  {
    // Is it a brand new node?
    if (!$node->id)
    {
      return true;
    }

    // Was a new parent ID actually submitted?
    if ($node->newParentId === null)
    {
      return false;
    }

    // Is it set to the top level now, but it hadn't been before?
    if ($node->newParentId === '' && $node->level != 1)
    {
      return true;
    }

    // Is it set to be under a parent now, but didn't have one before?
    if ($node->newParentId !== '' && $node->level == 1)
    {
      return true;
    }

    // Is the newParentId set to a different category ID than its previous parent?
    $criteria                = craft()->elements->getCriteria('Navee_Node');
    $criteria->ancestorOf    = $node;
    $criteria->ancestorDist  = 1;
    $criteria->status        = null;
    $criteria->localeEnabled = null;

    $oldParent   = $criteria->first();
    $oldParentId = ($oldParent ? $oldParent->id : '');

    if ($node->newParentId != $oldParentId)
    {
      return true;
    }

    // Must be set to the same one then
    return false;
  }
}
