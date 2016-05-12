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
      ->addSelect('nodes.*, i18nEntry.uri as entryLink, i18nCategory.uri as categoryLink')
      ->join('navee_nodes nodes', 'nodes.id = elements.id')
      ->join('navee_navigations navigations', 'navigations.id = nodes.navigationId')
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

//    if ($criteria->navigationHandle)
//    {
//      $query->andWhere(DbHelper::parseParam('navigations.handle', $criteria->navigationHandle, $query->params));
//    }
//
//    if ($criteria->calendar)
//    {
//      $query->join('events_calendars events_calendars', 'events_calendars.id = events.calendarId');
//      $query->andWhere(DbHelper::parseParam('events_calendars.handle', $criteria->calendar, $query->params));
//    }
//
//    if ($criteria->startDate)
//    {
//      $query->andWhere(DbHelper::parseDateParam('events.startDate', $criteria->startDate, $query->params));
//    }
//
//    if ($criteria->endDate)
//    {
//      $query->andWhere(DbHelper::parseDateParam('events.endDate', $criteria->endDate, $query->params));
//    }
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
    $html = craft()->templates->render('node/_edit', array(
      'element'             => $element,
      'entryElements'       => craft()->entries->getEntryById($element->entryId),
      'entryElementType'    => craft()->elements->getElementType('Entry'),
      'assetElements'       => craft()->assets->getFileById($element->assetId),
      'assetElementType'    => craft()->elements->getElementType('Asset'),
      'categoryElements'    => craft()->categories->getCategoryById($element->categoryId),
      'categoryElementType' => craft()->elements->getElementType('Category'),
    ));

    $html .= parent::getEditorHtml($element);

    return $html;
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
        'newChildUrl' => 'navee/navigations/' . $navigation->handle . '/new',
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
