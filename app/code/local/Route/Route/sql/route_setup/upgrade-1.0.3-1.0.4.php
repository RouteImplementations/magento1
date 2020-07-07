<?php

/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;

$installer->startSetup();

$connection = $installer->getConnection();

if(!$connection->isTableExists($installer->getTable('route/route_operation_fallback'))){
    $table = $installer->getConnection()
        ->newTable($installer->getTable('route/route_operation_fallback'))
        ->addColumn('operation_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
        ), "Primary Key")
        ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
            'nullable' => false,
        ), "Entity Id ")
        ->addColumn('type', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
            'nullable' => false,
        ), 'Entity Type')
        ->addColumn('operation', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(), 'Operation Type')
        ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(), 'Status')
        ->addColumn('retry', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(), 'Retry')
        ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(), 'Updated At')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(), 'Created At')
        ->addIndex($installer->getIdxName('route/route_operation_fallback', array('entity_id', 'type')),
            array('entity_id', 'type'))
        ->setComment('Operation Fallback');


    $installer->getConnection()->createTable($table);
}

$installer->endSetup();