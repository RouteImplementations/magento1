<?php

/* @var $installer Mage_Sales_Model_Entity_Setup */

$installer = $this;

$installer->run("
        UPDATE 
          {$installer->getTable('sales/order_grid')} as G
        INNER JOIN
          {$installer->getTable('sales/order')} as O
        SET 
          G.fee_amount = O.fee_amount,
          G.base_fee_amount = O.fee_amount
        WHERE 
          G.increment_id = O.increment_id
        AND 
          O.fee_amount > 0
    ");

$installer->endSetup();