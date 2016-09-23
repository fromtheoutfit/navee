<?php
namespace Craft;

/**
 * Thanks to nycstudio107 for the migration blueprint. ;)
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160922_150924_navee_AddRegexField extends BaseMigration {

  /**
   * Any migration code in here is wrapped inside of a transaction.
   *
   * @return bool
   */
  public function safeUp()
  {
    // specify columns and AttributeType
    $newColumns = array(
      'regex' => ColumnType::Varchar,
    );

    $this->_addColumnsAfter('navee_nodes', $newColumns, 'target');

    // return true and let craft know its done
    return true;
  }

  private function _addColumnsAfter($tableName, $newColumns, $afterColumnHandle)
  {

    // this is a foreach loop, enough said
    foreach ($newColumns as $columnName => $columnType)
    {
      // check if the column does NOT exist
      if (!craft()->db->columnExists($tableName, $columnName))
      {
        $this->addColumnAfter($tableName, $columnName, array(
          'column' => $columnType,
          'null'   => false,
        ),
          $afterColumnHandle
        );


      }

    }
  }
}
