<?php

/* @var $installer Mage_Sales_Model_Entity_Setup */

$installer = $this;

$installer->run("
        UPDATE 
          {$installer->getTable('sales/order_grid')} as G
        INNER JOIN
          {$installer->getTable('sales/order')} as O
        SET 
          G.route_is_insured = 1
        WHERE 
          G.increment_id = O.increment_id
        AND 
          O.fee_amount > 0
    ");

$installer->endSetup();