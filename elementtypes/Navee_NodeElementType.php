<?php
namespace Craft;

/**
 * Events - Event element type
 */
class Navee_NodeElementType extends BaseElementType {

  /**
   * Returns the element type name.
   *
   * @return string
   */
  public function getName()
  {
    return Craft::t('NaveeNode');
  }

  /**
   * Returns whether this element type has content.
   *
   * @return bool
   */
  public function hasContent()
  {
    return true;
  }

  /**
   * Returns whether this element type has titles.
   *
   * @return bool
   */
  public function hasTitles()
  {
    return true;
  }

  /**
   * @inheritDoc IElementType::isLocalized()
   * @return bool
   */
  public function isLocalized()
  {
    return true;
  }

  /**
   * @inheritDoc IElementType::hasStatuses()
   * @return bool
   */
  public function hasStatuses()
  {
    return true;
  }

  /**
   * Returns this element type's sources.
   *
   * @param string|null $context
   * @return array|false
   */
  public function getSources($context = null)
  {
    $sources = array();

    foreach (craft()->navee_navigation->getAllNavigations() as $navigation)
    {
      $key = 'navigation:' . $navigation->id;

      $sources[$key] = array(
        'label'             => $navigation->name,
        'criteria'          => array('navigationId' => $navigation->id),
        'structureId'       => $navigation->structureId,
        'structureEditable' => true,
      );
    }

    return $sources;
  }

  /**
   * @inheritDoc IElementType::defineSortableAttributes()
   * @retrun     array
   */
  public function defineSortableAttributes()
  {
    $attributes = array(
      'title' => Craft::t('Title')
    );


    return $attributes;
  }

  /**
   * Returns the attributes that can be shown/sorted by in table views.
   *
   * @param string|null $source
   * @return array
   */
  public function defineTableAttributes($source = null)
  {

    return array(
      'title' => Craft::t('Title'),
      'linkedElementCpEditUrl'  => Craft::t('Type'),
    );
  }

  /**
   * Returns the table view HTML for a given attribute.
   *
   * @param BaseElementModel $element
   * @param string           $attribute
   * @return string
   */
  public function getTableAttributeHtml(BaseElementModel $element, $attribute)
  {
    switch ($attribute)
    {
      case 'linkedElementCpEditUrl':
        switch ($element->linkType)
        {
          case 'entryId':
            $criteria = craft()->elements->getCriteria(ElementType::Entry);
            $criteria->id = $element->entryId;
            $criteria->status = array(EntryModel::LIVE, EntryModel::PENDING, EntryModel::EXPIRED, EntryModel::ARCHIVED, EntryModel::DISABLED, EntryModel::ENABLED);
            $entry = $criteria->first();
            if (!$entry) {
              return '<a href="#" data-icon="alert"></a>';
            }
            else
            {
              $cpEditUrl = $entry->getCpEditUrl();
              return '<a href="' . $cpEditUrl . '" data-icon="section"></a>';
            }
            break;
          case 'categoryId':
            $criteria = craft()->elements->getCriteria(ElementType::Category);
            $criteria->id = $element->categoryId;
            $criteria->status = array(CategoryModel::ENABLED, CategoryModel::DISABLED, CategoryModel::ARCHIVED);
            $entry = $criteria->first();
            if (!$entry) {
              return '<a href="#" data-icon="alert"></a>';
            }
            else
            {
              $cpEditUrl = $entry->getCpEditUrl();
              return '<a href="' . $cpEditUrl . '" data-icon="categories"></a>';
            }
            break;
          case 'assetId':
            $file       = craft()->assets->getFileById($element->assetId);
            if (!$file)
            {
              return '<a href="#" data-icon="alert"></a>';
            }
            else
            {
              $sourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
              $asset = AssetsHelper::generateUrl($sourceType, $file);
              return '<a href="' . $asset . '" data-icon="assets"></a>';
            }
            break;
          default:
            return '';
        }
        break;
      default:
      {
        return parent::getTableAttributeHtml($element, $attribute);
      }
    }
  }

  /**
   * Defines any custom element criteria attributes for this element type.
   *
   * @return array
   */
  public function defineCriteriaAttributes()
  {
    return array(
      'navigation'       => AttributeType::Mixed,
      'navigationId'     => AttributeType::Mixed,
      'navigationHandle' => AttributeType::Mixed,
      'order'            => array(AttributeType::String, 'default' => 'lft'),
    );
  }

  /**
   * Modifies an element query targeting elements of this type.
   *
   * @param DbCommand            $query
   * @param ElementCriteriaModel $criteria
   * @return mixed
   */
  public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
  {
    $query
      ->addSelect('nodes.*, i18nEntry.uri as entryLink, i18nCategory.uri as categoryLink, elementEntry.enabled as entryEnabled')
      ->join('navee_nodes nodes', 'nodes.id = elements.id')
      ->join('navee_navigations navigations', 'navigations.id = nodes.navigationId')
      ->leftJoin('elements elementEntry', 'elementEntry.id = nodes.entryId')
      ->leftJoin('elements_i18n i18nEntry', 'i18nEntry.elementId = nodes.entryId')
      ->leftJoin('elements_i18n i18nCategory', 'i18nCategory.elementId = nodes.categoryId')
      ->leftJoin('structures structures', 'structures.id = navigations.structureId')
      ->leftJoin('structureelements structureelements', array('and', 'structureelements.structureId = structures.id', 'structureelements.elementId = nodes.id'));

    if ($criteria->navigationId)
    {
      $query->andWhere(DbHelper::parseParam('nodes.navigationId', $criteria->navigationId, $query->params));
    }

    if ($criteria->navigation)
    {
      $query->andWhere(DbHelper::parseParam('navigations.handle', $criteria->navigation, $query->params));
    }
  }

  /**
   * Populates an element model based on a query result.
   *
   * @param array $row
   * @return array
   */
  public function populateElementModel($row)
  {
    return Navee_NodeModel::populateModel($row);
  }

  /**
   * Returns the HTML for an editor HUD for the given element.
   *
   * @param BaseElementModel $element
   * @return string
   */
  public function getEditorHtml(BaseElementModel $element)
  {
    $variables = array(
      'node'             => $element,
    );

    // get potential elements
    $variables['entryElements'][]    = craft()->entries->getEntryById($element->entryId);
    $variables['assetElements'][]    = craft()->assets->getFileById($element->assetId);
    $variables['categoryElements'][] = craft()->categories->getCategoryById($element->categoryId);

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

    $html = craft()->templates->render('navee/nodes/_hud', $variables);

    $html .= parent::getEditorHtml($element);

    return $html;
  }

  public function saveElement(BaseElementModel $element, $vars)
  {
    $element->linkType = $vars['linkType'];

    if (is_array($vars['assetId']) && sizeof($vars['assetId']))
    {
      $element->assetId = $vars['assetId'][0];
    }

    if (is_array($vars['entryId']) && sizeof($vars['entryId']))
    {
      $element->entryId = $vars['entryId'][0];
    }

    if (is_array($vars['categoryId']) && sizeof($vars['categoryId']))
    {
      $element->categoryId = $vars['categoryId'][0];
    }


    return craft()->navee_node->saveNode($element);
  }

  /**
   * @inheritDoc IElementType::getAvailableActions()
   * @param string|null $source
   * @return array|null
   */
  public function getAvailableActions($source = null)
  {
    if (preg_match('/^navigation:(\d+)$/', $source, $matches))
    {
      $navigation = craft()->navee_navigation->getNavigationById($matches[1]);
    }

    if (empty($navigation))
    {
      return;
    }

    $actions = array();

    // Set Status
    $actions[] = 'SetStatus';

    // Edit
    $editAction = craft()->elements->getAction('Edit');
    $editAction->setParams(array(
      'label' => Craft::t('Edit node'),
    ));
    $actions[] = $editAction;

    // New Child
    $structure = craft()->structures->getStructureById($navigation->structureId);

    if ($structure)
    {
      $newChildAction = craft()->elements->getAction('NewChild');
      $newChildAction->setParams(array(
        'label'       => Craft::t('Create a new child node'),
        'maxLevels'   => $structure->maxLevels,
        'newChildUrl' => 'navee/node/' . $navigation->handle . '/new',
      ));
      $actions[] = $newChildAction;
    }

    // Delete
    $deleteAction = craft()->elements->getAction('Delete');
    $deleteAction->setParams(array(
      'confirmationMessage' => Craft::t('Are you sure you want to delete the selected nodes?'),
      'successMessage'      => Craft::t('Nodes deleted.'),
    ));
    $actions[] = $deleteAction;

    return $actions;
  }
}
