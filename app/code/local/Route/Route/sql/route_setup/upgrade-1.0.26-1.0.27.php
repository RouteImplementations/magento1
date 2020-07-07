<?php

/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;
$installer->startSetup();

$shipmentTrackTable = $this->getTable('sales/shipment_track');
$routeFallbackTable = $this->getTable('route/route_operation_fallback');

$connection = $installer->getConnection();

if ($connection->tableColumnExists($shipmentTrackTable, 'route_shipment_id') === false) $connection->addColumn($shipmentTrackTable, 'route_shipment_id', 'VARCHAR(50) NOT NULL DEFAULT 0');

if ($connection->isTableExists($routeFallbackTable)) {
    $observer = Mage::getModel('route/observer');
    $observer->sanitizeFallbackOperations();

    $tableName = $installer->getTable('route/route_operation_fallback');
    $query = "CREATE TABLE `{$tableName}_temp` LIKE `{$tableName}`;";
    $installer->run($query);
    $query = "ALTER TABLE `{$tableName}_temp` ADD UNIQUE INDEX route_operation_fallback_idx (`entity_id`, `type`, `operation`);";
    $installer->run($query);
    $query = "INSERT IGNORE INTO `{$tableName}_temp` SELECT * FROM `{$tableName}`;";
    $installer->run($query);
    $query = "DROP TABLE `{$tableName}`;";
    $installer->run($query);
    $query = "RENAME TABLE `{$tableName}_temp` TO `{$tableName}`;";
    $installer->run($query);
}
$installer->endSetup();
