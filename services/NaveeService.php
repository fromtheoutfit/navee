<?php
/**
 * Navee plugin for Craft CMS
 * Navee Service
 *
 * @author    The Outfit
 * @copyright Copyright (c) 2016 The Outfit
 * @link      http://fromtheoutfit.com
 * @package   Navee
 * @since     1.0.0
 */

namespace Craft;

class NaveeService extends BaseApplicationComponent {

  public function __construct()
  {
    $this->config = new Navee_ConfigModel();
  }

  /**
   * Sets the configuration variables passed into a navee tag
   *
   * @access public
   * @param $config
   */
  public function setConfig($config)
  {
    $this->config             = Navee_ConfigModel::populateModel($config);
    $this->config->userGroups = $this->getUserGroupIdArray();
  }

  /**
   * @param $navigationHandle
   * @return string
   * @throws Exception
   * @return string
   */
  public function getNav($navigationHandle)
  {
    // get the nodes for this navigation
    if ($this->config->reverseNodes)
    {
      $criteria = craft()->elements->getCriteria('Navee_Node')->limit(null)->order('lft desc');
    }
    else
    {
      $criteria = craft()->elements->getCriteria('Navee_Node')->limit(null);
    }

    $criteria->navigation = $navigationHandle;
    $nodes                = $criteria->find();
    $removedNodes         = array();

    // variables
    $activeNodes = array();

    // before we proceed, ensure that we have nodes to work with
    if (sizeof($nodes))
    {
      // loop through all nodes to do the following
      // - set active classes
      // - remove any nodes not mean to be included
      foreach ($nodes as $k => $node)
      {
        // Set the link for this node based on the type
        $node       = $this->setLink($node);
        $node->text = $node->title;

        // Check to see if this node should be removed because it
        // is a descendant of a previously removed node
        if ($this->ancestorRemoved($node, $removedNodes))
        {
          unset($nodes[$k]);
          continue;
        }

        // check to see if node should be included
        if ($this->nodeIncluded($node))
        {
          // check to see if the node is active
          if ($this->nodeActive($node))
          {
            $activeNodes[] = $node;
          }
        }
        else
        {
          // if not, push the node into the removed nodes array
          // and remove it from the navigation
          array_push($removedNodes, $node);
          unset($nodes[$k]);
          continue;
        }
      }

      if (sizeof($activeNodes))
      {
        $nodes = $this->setActiveNodeRelatives($nodes, $activeNodes);
      }

      // set the appropriate active classes for each node
      $nodes = $this->setActiveClasses($nodes);

      // now let's limit the nav based on which parameters are passed
      $nodes = $this->getSubsetOfNodes($nodes, $activeNodes);

      // Mark all nodes that have children
      $nodes = $this->setHasChildren($nodes);

    }

    return $nodes;
  }

  /**
   * Sets the link of a node based on the node type
   *
   * @access public
   * @param Navee_NodeModel $node
   * @return Navee_NodeModel
   */

  public function setLink(Navee_NodeModel $node)
  {
    switch ($node->linkType)
    {
      case 'entryId':
        $node->link = ($this->isHomepageEntry($node->entryLink)) ? '/' : '/' . $node->entryLink;
        break;
      case 'categoryId':
        $node->link = '/' . $node->categoryLink;
        break;
      case 'customUri':
        $node->link = ($this->isGlobalVariable($node->customUri)) ? $this->getGlobalUrl($node->customUri) : $node->customUri;
        break;
      case 'assetId':
        $file = craft()->assets->getFileById($node->assetId);
        if ($file)
        {
          $sourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
          $node->link = AssetsHelper::generateUrl($sourceType, $file);
        }
        else
        {
          $node->link = '';
        }

        break;
    }

    return $node;

  }

  /**
   * Determines if a linked entry is outputting __home__
   *
   * @access private
   * @param $entryLink
   * @return bool
   */
  private function isHomepageEntry($entryLink)
  {
    return ($entryLink == '__home__') ? true : false;
  }

  /**
   * Determines if a custom uri matches a global variable
   *
   * @access private
   * @param $customUri
   * @return bool
   */
  private function isGlobalVariable($customUri)
  {
    $globalUrls = array('loginUrl', 'logoutUrl');

    $customUri = $this->getGlobalVariableSlug($customUri);

    if (in_array($customUri, $globalUrls))
    {
      return true;
    }

    return false;
  }

  /**
   * Replaces a global variable template string with the actual url
   *
   * @access private
   * @param $customUri
   * @return string
   */
  private function getGlobalUrl($customUri)
  {
    $trimmedUri = $this->getGlobalVariableSlug($customUri);

    switch ($trimmedUri)
    {
      case 'loginUrl':
        return UrlHelper::getUrl(craft()->config->getLoginPath());
        break;
      case 'logoutUrl':
        return UrlHelper::getUrl(craft()->config->getLogoutPath());
        break;
      default:
        return $customUri;
    }

  }

  /**
   * Trims out the {{ }} from a potential global variable
   *
   * @access private
   * @param $customUri
   * @return mixed|string
   */
  private function getGlobalVariableSlug($customUri)
  {
    $replace = array('{', '}');
    $customUri = str_replace($replace, '', $customUri);
    $customUri = trim($customUri);

    return $customUri;
  }

  /**
   * Checks to see if a node is active
   *
   * @access private
   * @param Navee_NodeModel $node
   * @return bool
   */

  private function nodeActive(Navee_NodeModel $node)
  {
    $data       = false;
    $currentUri = ltrim(craft()->request->getPath(), '/');
    $link       = ltrim($node->link, '/');

    if (!$node->passive)
    {
      if ($link == $currentUri || $this->nodeActiveRegex($node, $currentUri))
      {
        $node->active = true;
        $data         = true;
      }
    }


    return $data;
  }

  /**
   * Checks to see if a node regex matches the current URI
   *
   * @access private
   * @param Navee_NodeModel $node
   * @param string          $currentUri
   * @return boolean
   */

  private function nodeActiveRegex(Navee_NodeModel $node, $currentUri)
  {
    $data = false;

    if (strlen($node->regex) && preg_match($node->regex, $currentUri))
    {
      $data = true;
    }

    return $data;
  }

  /**
   * Determines if a node should be included in the nav
   *
   * @access private
   * @param Navee_NodeModel $node
   * @return bool
   */

  private function nodeIncluded(Navee_NodeModel $node)
  {
    // let's start by assuming this node is good to go
    $included = true;

    // the node is limited to certain user groups
    if (sizeof($node->userGroups))
    {
      // so let's undo our previous assumption
      $included = false;

      // then let's loop through all this nodes user groups
      foreach ($node->userGroups as $nodeUserGroup)
      {
        // and check to see if it is in the current users assigned groups
        if (in_array($nodeUserGroup, $this->config->userGroups))
        {
          // and if any of them check out, let's include it again and break the loop
          $included = true;
          break;
        }
      }
    }

    // if at this point we haven't already found a reason to omit the node
    // and the config hasn't overridden the include in navigation functionality
    if ($included && !$this->config->ignoreIncludeInNavigation)
    {
      // let's see if the node itself is marked to be included
      $included = $node->includeInNavigation;
    }

    // checks to see if disabled entries should be skipped
    if ($this->config->skipDisabledEntries && !is_null($node->entryEnabled))
    {
      $included = $node->entryEnabled;
    }


    // and return what we've got at the end of that confusing mess
    return $included;
  }

  /**
   * Figures out if the ancester of a node was previously removed
   *
   * @access private
   * @param Navee_NodeModel $node
   * @param array           $removedNodes
   * @return bool
   */

  private function ancestorRemoved(Navee_NodeModel $node, $removedNodes = array())
  {
    $data = false;

    foreach ($removedNodes as $removedNode)
    {
      if ($node->lft > $removedNode->lft && $node->rgt < $removedNode->rgt)
      {
        $data = true;
      }
    }

    return $data;
  }

  /**
   * Sets flags in the relatives of active nodes marking them ancestors/descendants
   *
   * @access private
   * @param array $nodes
   * @param array $activeNodes
   * @return array
   */

  private function setActiveNodeRelatives($nodes = array(), $activeNodes = array())
  {
    foreach ($nodes as $node)
    {
      foreach ($activeNodes as $activeNode)
      {
        $node->descendantActive = $this->setDescendantActive($node, $activeNode);
        $node->ancestorActive   = $this->setAncestorActive($node, $activeNode);
        $node->siblingActive    = $this->setSiblingActive($node, $activeNode);
      }
    }

    return $nodes;
  }

  /**
   * Determines if an ancestor of a node is active
   *
   * @access private
   * @param Navee_NodeModel $node
   * @param array           $activeNode
   * @return bool
   */

  private function setAncestorActive(Navee_NodeModel $node, $activeNode)
  {
    return (($node->lft > $activeNode->lft) && ($node->rgt < $activeNode->rgt));
  }

  /**
   * Determines if a descendant of a node is active
   *
   * @access private
   * @param Navee_NodeModel $node
   * @param array           $activeNode
   * @return bool
   */

  private function setDescendantActive(Navee_NodeModel $node, $activeNode)
  {
    return (($node->lft < $activeNode->lft) && ($node->rgt > $activeNode->rgt));
  }

  /**
   * Determines if a sibling of a node is active
   *
   * @access private
   * @param Navee_NodeModel $node
   * @param array           $activeNode
   * @return bool
   */

  private function setSiblingActive(Navee_NodeModel $node, $activeNode)
  {
    return (!$node->active && ($node->level == $activeNode->level));
  }

  /**
   * Sets appropriate active classes for each node
   *
   * @access private
   * @param array $nodes
   * @return array
   */

  private function setActiveClasses($nodes = array())
  {
    if (!$this->config->disableActiveClass)
    {
      foreach ($nodes as $node)
      {
        // this is the active node
        if ($node->active)
        {
          $node->class = (strlen($node->class)) ? $node->class . ' ' . $this->config->activeClass : $this->config->activeClass;
        }

        // these are ancestors of the active node
        if ($this->config->activeClassOnAncestors && $node->descendantActive)
        {
          $node->class = (strlen($node->class)) ? $node->class . ' ' . $this->config->ancestorActiveClass : $this->config->ancestorActiveClass;
        }

      }
    }

    return $nodes;
  }

  private function setHasChildren($nodes)
  {
    foreach ($nodes as $node)
    {
      $node->hasChildren = (((int) $node->rgt - $node->lft) > 1) ? true : false;

    }

    return $nodes;
  }

  private function getUserGroupIdArray()
  {
    $data        = array();
    $currentUser = craft()->userSession->getUser();

    if ($currentUser)
    {
      $user       = craft()->users->getUserById($currentUser->id);
      $userGroups = $user->getGroups();

      foreach ($userGroups as $userGroup)
      {
        array_push($data, $userGroup->id);
      }
    }

    return $data;
  }

  /**
   * Returns a subset of nodes based on the config parameters passed
   *
   * @access private
   * @param array $nodes
   * @param array $activeNodes
   * @return array
   */

  private function getSubsetOfNodes($nodes, $activeNodes)
  {
    $removedNodes    = array();
    $breadcrumbCount = 0;

    if (sizeof($activeNodes))
    {
      // If there are more than one active nodes, we have to just take the first one
      $activeNode = $activeNodes[0];


      // Set the top ancestor level
      $ancestorLevel = (($this->config->startDepth > 1) && ($activeNode->level - $this->config->startDepth >= 1)) ? $activeNode->level - $this->config->startDepth : 1;

      // Variable overrides for startXLevelsAboveActive
      if ($this->config->startXLevelsAboveActive)
      {
        $xLevelsAboveActive = $activeNode->level - $this->config->startXLevelsAboveActive;
        if ($xLevelsAboveActive > $ancestorLevel)
        {
          $ancestorLevel = $xLevelsAboveActive;
        }

        if ($this->config->maxDepth)
        {
          $this->config->maxDepth = ($ancestorLevel + $this->config->maxDepth) - 1;
        }
      }

      // Variable overrides for startWithChildrenOfActive
      if ($this->config->startWithChildrenOfActive && $this->config->maxDepth)
      {
        $this->config->maxDepth = $activeNode->level + $this->config->maxDepth;
      }

      // Variable overrides for startWithActive / startWithSiblingsOfActive
      if (($this->config->startWithActive || $this->config->startWithSiblingsOfActive) && $this->config->maxDepth)
      {
        $this->config->maxDepth = $activeNode->level + $this->config->maxDepth - 1;
      }

    }
    else
    {
      // There are no active nodes - which means that we should return an empty array
      // if any of the parameters that are dependant on an active node have been passed
      if ($this->config->startWithActive ||
        $this->config->startWithAncestorOfActive ||
        $this->config->startWithChildrenOfActive ||
        $this->config->startWithSiblingsOfActive ||
        $this->config->startXLevelsAboveActive ||
        $this->config->breadcrumbs
      )
      {
        return array();
      }

    }

    foreach ($nodes as $k => $node)
    {
      if (!isset($rootNode))
      {
        if ($this->config->startWithNodeId || $this->config->startWithChildrenOfNodeId)
        {
          if ((int) $node->id == (int) $this->config->startWithNodeId
            || (int) $node->id == (int) $this->config->startWithChildrenOfNodeId)
          {
            $rootNode = $node;
            break;
          }
        }
        elseif ($node->level == 1 && ($node->descendantActive || $node->active))
        {
          $rootNode = $node;
          break;
        }
      }
    }

    foreach ($nodes as $k => $node)
    {
      // Check to see if this node should be removed because it
      // is a descendant of a previously removed node
      if ($this->ancestorRemoved($node, $removedNodes))
      {
        unset($nodes[$k]);
        continue;
      }

      // make sure all nodes are above the startDepth
      if ($this->config->startDepth > $node->level)
      {
        unset($nodes[$k]);
        continue;
      }

      // make sure all nodes are below the maxDepth
      if ($this->config->maxDepth && $this->config->maxDepth < $node->level)
      {
        array_push($removedNodes, $node);
        unset($nodes[$k]);
        continue;
      }

      // the rest of these parameters rely on there actually being an active node
      if (sizeof($activeNodes))
      {
        // breadcrumbs
        if ($this->config->breadcrumbs)
        {
          if (!$node->active && !$node->descendantActive)
          {
            array_push($removedNodes, $node);
            unset($nodes[$k]);
            continue;
          }
          else
          {
            $node->lft       = $breadcrumbCount + 2;
            $node->rgt       = $breadcrumbCount + 3;
            $node->level     = 1;
            $breadcrumbCount = $breadcrumbCount + 2;
          }
        }
        // start with the ancestor of the active node
        elseif ($this->config->startWithAncestorOfActive)
        {
          if ($this->config->startDepth == $node->level && !$node->descendantActive && !$node->active)
          {
            array_push($removedNodes, $node);
            unset($nodes[$k]);
            continue;
          }
        }
        // start with the active node
        elseif ($this->config->startWithActive)
        {
          if (!$this->nodeInBranchOfActiveNode($rootNode, $node) || ($node->level == $activeNode->level && !$node->active))
          {
            array_push($removedNodes, $node);
            unset($nodes[$k]);
            continue;
          }
          elseif (($node->lft <= $activeNode->lft || $node->rgt >= $activeNode->rgt) && !$node->active)
          {
            unset($nodes[$k]);
            continue;
          }
        }
        // start with a given node id
        elseif ((int) $this->config->startWithNodeId && isset($rootNode))
        {
          if ($node->lft <= $rootNode->lft || $node->rgt >= $rootNode->rgt)
          {
            unset($nodes[$k]);
            continue;
          }
        }
        // start with children of a given node id
        elseif ((int) $this->config->startWithChildrenOfNodeId && isset($rootNode))
        {
          if ($node->lft < $rootNode->lft || $node->rgt > $rootNode->rgt)
          {
            unset($nodes[$k]);
            continue;
          }
        }
        // start with the active node
        elseif ($this->config->startWithSiblingsOfActive)
        {
          if (!$this->nodeInBranchOfActiveNode($rootNode, $node) && !$node->active)
          {
            array_push($removedNodes, $node);
            unset($nodes[$k]);
            continue;
          }
          elseif (!($node->lft >= $activeNode->lft) && !($node->rgt <= $activeNode->rgt))
          {
            unset($nodes[$k]);
            continue;
          }
        }
        // start with the children of the active node
        elseif ($this->config->startWithChildrenOfActive)
        {
          if (!$node->ancestorActive)
          {
            if (!$node->descendantActive && !$node->active)
            {
              array_push($removedNodes, $node);
            }
            unset($nodes[$k]);
            continue;
          }
        }
        // start x levels above the active node
        elseif ($this->config->startXLevelsAboveActive)
        {
          // if the active node is a descendant, and the node level is less than ancestor level,
          // remove but do not put it in the deleted nodes array
          if (isset($rootNode) && $this->nodeInBranchOfActiveNode($rootNode, $node))
          {
            if ($node->level < $ancestorLevel)
            {
              unset($nodes[$k]);
              continue;
            }
          }
          // if the active node is NOT a descendant, remove the node and put it in deleted nodes array
          else
          {
            array_push($removedNodes, $node);
            unset($nodes[$k]);
            continue;
          }

        }
      }
    }

    $nodes = $this->rebuildNestedSet($nodes);

    return $nodes;
  }

  /**
   * Determines if a given node is in the branch of an active
   *
   * @access private
   * @param Navee_NodeModel $rootNode
   * @param Navee_NodeModel $node
   * @return boolean
   */

  private function nodeInBranchOfActiveNode(Navee_NodeModel $rootNode, Navee_NodeModel $node)
  {
    $data = ($node->lft >= $rootNode->lft && $node->rgt <= $rootNode->rgt) ? true : false;

    return $data;
  }

  /**
   * Rebuilds the nested set after elements have been removed
   *
   * @access private
   * @param array $nodes
   * @return array
   */

  private function rebuildNestedSet($nodes)
  {
    foreach ($nodes as $k => $v)
    {

      if (isset($prevIndex) && ((int) $nodes[$prevIndex]->lft + 1 !== (int) $nodes[$prevIndex]->rgt))
      {
        if ((int) $nodes[$prevIndex]->lft + 1 !== (int) $v->lft)
        {
          $nodes[$prevIndex]->rgt = $nodes[$prevIndex]->lft + 1;
        }
      }

      $prevIndex = $k;

    }

    return $nodes;
  }

}
