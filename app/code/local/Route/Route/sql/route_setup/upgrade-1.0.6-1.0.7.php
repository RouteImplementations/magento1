<?php

/* @var $installer Mage_Core_Model_Resource_Setup */

/**
 * Script to adapt merchants that have installed old version of Route Module,
 * that includes wrong column types which's causing wrong calculation due to precision.
 */

$installer = $this;
$installer->startSetup();

$tables = [
    $this->getTable('sales/order') => [
        'fee_amount', 'base_fee_amount', 'fee_amount_refunded',
        'base_fee_amount_refunded', 'fee_amount_invoiced', 'base_fee_amount_invoiced'
    ],
    $this->getTable('sales/quote') => ['fee_amount', 'base_fee_amount'],
    $this->getTable('sales/invoice') => ['fee_amount', 'base_fee_amount'],
    $this->getTable('sales/creditmemo') => ['fee_amount', 'base_fee_amount'],
    $this->getTable('sales/quote_address') => ['fee_amount', 'base_fee_amount']
];

$connection = $installer->getConnection();

foreach ($tables as $table => $columns){
    $tableColumns = $connection->describeTable($table);
    foreach ($columns as $column){
        if( isset($tableColumns[$column]) && isset($tableColumns[$column]['SCALE']) && $tableColumns[$column]['SCALE'] < 2 ) {
            $connection->modifyColumn($table, $column,'DECIMAL( 10, 2 ) NOT NULL');
        }
    }
}

$installer->endSetup();