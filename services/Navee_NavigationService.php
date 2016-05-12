<?php

namespace Craft;

/**
 * Navee Service
 * Provides a consistent API for our plugin to access the database
 */
class Navee_NavigationService extends BaseApplicationComponent {

  private $_allNavigationIds;
  private $_navigationsById;
  private $_fetchedAllNavigations = false;

  /**
   * Returns all of the calendar IDs.
   *
   * @return array
   */
  public function getAllNavigationIds()
  {
    if (!isset($this->_allNavigationIds))
    {
      if ($this->_fetchedAllNavigations)
      {
        $this->_allNavigationIds = array_keys($this->_navigationsById);
      }
      else
      {
        $this->_allNavigationIds = craft()->db->createCommand()
          ->select('id')
          ->from('navee_navigations')
          ->queryColumn();
      }
    }

    return $this->_allNavigationIds;
  }

  /**
   * Get all navigations from the database.
   *
   * @return array
   */
  public function getAllNavigations($indexBy = null)
  {
    if (!$this->_fetchedAllNavigations)
    {
      $navigationRecords      = Navee_NavigationRecord::model()->ordered()->findAll();
      $this->_navigationsById = Navee_NavigationModel::populateModels($navigationRecords, 'id');
      $this->_fetchedAllNavigations = true;
    }

    if ($indexBy == 'id')
    {
      return $this->_navigationsById;
    }
    else if (!$indexBy)
    {
      return array_values($this->_navigationsById);
    }
    else
    {
      $navigations = array();

      foreach ($this->_navigationsById as $navigation)
      {
        $navigations[$navigation->$indexBy] = $navigation;
      }

      return $navigations;
    }
  }

  /**
   * Gets the total number of navigations.
   *
   * @return int
   */
  public function getTotalNavigations()
  {
    return count($this->getAllNavigationIds());
  }

  /**
   * Returns a navigation by its ID.
   *
   * @param $navigationId
   * @return Navee_NavigationModel|null
   */
  public function getNavigationById($navigationId)
  {
    if (!isset($this->_navigationsById) || !array_key_exists($navigationId, $this->_navigationsById))
    {
      $navigationRecord = Navee_NavigationRecord::model()->findById($navigationId);

      if ($navigationRecord)
      {
        $this->_navigationsById[$navigationId] = Navee_NavigationModel::populateModel($navigationRecord);
      }
      else
      {
        $this->_navigationsById[$navigationId] = null;
      }
    }

    return $this->_navigationsById[$navigationId];
  }

  /**
   * Gets a calendar by its handle.
   *
   * @param string $calendarHandle
   * @return Events_CalendarModel|null
   */
  public function getNavigationByHandle($navigationHandle)
  {
    $navigationRecord = Navee_NavigationRecord::model()->findByAttributes(array(
      'handle' => $navigationHandle
    ));

    if ($navigationRecord)
    {
      return Navee_NavigationModel::populateModel($navigationRecord);
    }
  }

  /**
   * Saves a calendar.
   *
   * @param Navee_NavigationModel $navigation
   * @throws \Exception
   * @return bool
   */
  public function saveNavigation(Navee_NavigationModel $navigation)
  {
    if ($navigation->id)
    {
      $navigationRecord = Navee_NavigationRecord::model()->findByPk($navigation->id);

      if (!$navigationRecord)
      {
        throw new Exception(Craft::t('No navigation exists with the ID “{id}”', array('id' => $navigation->id)));
      }

      $oldNavigation   = Navee_NavigationModel::populateModel($navigationRecord);
      $isNewNavigation = false;
    }
    else
    {
      $navigationRecord            = new Navee_NavigationRecord();
      $navigationRecord->creatorId = craft()->userSession->getUser()->id;
      $isNewNavigation             = true;
    }

    $navigationRecord->name = $navigation->name;
    $navigationRecord->handle = $navigation->handle;
    $navigationRecord->maxLevels = $navigation->maxLevels;

    $navigationRecord->validate();
    $navigation->addErrors($navigationRecord->getErrors());

    if (!$navigation->hasErrors())
    {
      $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
      try
      {
        // Create/update the structure
        if ($isNewNavigation)
        {
          $structure = new StructureModel();
          $structure->maxLevels = $navigation->maxLevels;
          craft()->structures->saveStructure($structure);
          $navigationRecord->structureId = $structure->id;
          $navigation->structureId = $structure->id;
        }
        else
        {
          $structure =  craft()->structures->getStructureById($navigation->structureId);
          $structure->maxLevels = $navigation->maxLevels;
          craft()->structures->saveStructure($structure);
        }


        if (!$isNewNavigation && $oldNavigation->fieldLayoutId)
        {
          // Drop the old field layout
          craft()->fields->deleteLayoutById($oldNavigation->fieldLayoutId);
        }

        // Save the new one
        $fieldLayout = $navigation->getFieldLayout();
        craft()->fields->saveLayout($fieldLayout);

        // Update the navigation record/model with the new layout ID
        $navigation->fieldLayoutId       = $fieldLayout->id;
        $navigationRecord->fieldLayoutId = $fieldLayout->id;

        // Save it!
        $navigationRecord->save(false);

        // Now that we have a calendar ID, save it on the model
        if (!$navigation->id)
        {
          $navigation->id = $navigationRecord->id;
        }

        // Might as well update our cache of the calendar while we have it.
        $this->_navigationsById[$navigation->id] = $navigation;

        if ($transaction !== null)
        {
          $transaction->commit();
        }
      } catch (\Exception $e)
      {
        if ($transaction !== null)
        {
          $transaction->rollback();
        }

        throw $e;
      }

      return true;
    }
    else
    {
      return false;
    }
  }

  /**
   * Deletes a navigation by its ID.
   *
   * @param int $navigationId
   * @throws \Exception
   * @return bool
   */

  public function deleteNavigationById($navigationId)
  {
    if (!$navigationId)
    {
      return false;
    }

    $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
    try
    {
      // Delete the field layout
      $fieldLayoutId = craft()->db->createCommand()
        ->select('fieldLayoutId')
        ->from('navee_navigations')
        ->where(array('id' => $navigationId))
        ->queryScalar();

      if ($fieldLayoutId)
      {
        craft()->fields->deleteLayoutById($fieldLayoutId);
      }

      // Grab the event ids so we can clean the elements table.
      $eventIds = craft()->db->createCommand()
        ->select('id')
        ->from('navee_nodes')
        ->where(array('navigationId' => $navigationId))
        ->queryColumn();

      craft()->elements->deleteElementById($eventIds);

      $affectedRows = craft()->db->createCommand()->delete('navee_navigations', array('id' => $navigationId));

      if ($transaction !== null)
      {
        $transaction->commit();
      }

      return (bool) $affectedRows;
    }
    catch (\Exception $e)
    {
      if ($transaction !== null)
      {
        $transaction->rollback();
      }

      throw $e;
    }
  }
}