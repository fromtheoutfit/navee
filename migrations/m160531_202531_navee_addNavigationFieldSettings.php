<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160531_202531_navee_addNavigationFieldSettings extends BaseMigration {

  /**
   * Any migration code in here is wrapped inside of a transaction.
   *
   * @return bool
   */
  public function safeUp()
  {
    $table = 'navee_navigations';
    $after = 'maxLevels';
    $columns = array(
      'hasClass'      => ColumnType::TinyInt,
      'hasId'         => ColumnType::TinyInt,
      'hasRel'        => ColumnType::TinyInt,
      'hasName'       => ColumnType::TinyInt,
      'hasTitle'      => ColumnType::TinyInt,
      'hasAccessKey'  => ColumnType::TinyInt,
      'hasTarget'     => ColumnType::TinyInt,
      'hasUserGroups' => ColumnType::TinyInt,
    );

    foreach ($columns as $name => $type)
    {
      if (!craft()->db->columnExists($table, $name))
      {
        $this->addColumnAfter($table, $name, array('column' => $type, 'null'   => false), $after);

        // log that we created the table
        NaveePlugin::log('Created the ' . $name . ' column in the ' . $table, LogLevel::Info, true);
      }
      else 
      {
        // log that we couldn't create it
        NaveePlugin::log($name . ' already exists in ' . $table, LogLevel::Info, true);
      }
    }

    return true;
  }
}
