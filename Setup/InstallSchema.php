<?php
/**
 * Copyright 2020 Marco Saßmannshausen (servicehome)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Servicehome\TaxRateUpdater\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Psr\Log\LoggerInterface;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    const TABLE_TASKS = 'servicehome_taxrate_tasks';
    const COL_ID = 'servicehome_taxrate_tasks_id';
    const COL_TAX_RATE_ID = 'tax_rate_id';
    const COL_TIME_TO_UPDATE = 'time_to_update';
    const COL_NEW_RATE = 'rate_in_percent';
    const COL_WAS_PROCESSED = 'was_processed';

    /**
     * @var SchemaSetupInterface
     */
    private $installer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->installer = $setup;

        $this->installer->startSetup();

        if (false === $this->installer->tableExists(self::TABLE_TASKS)) {
            $table = $this->createTableStructure();

            try {
                $this->installer->getConnection()->createTable($table);
            } catch (\Zend_Db_Exception $e) {
                $this->logger->emergency(__("Could not create tax-rate-updater table. %1", $e->getMessage()));
            }
        }

        $this->installer->endSetup();
    }

    private function createTableStructure(): Table
    {
        $table = $this->installer->getConnection()->newTable($this->installer->getTable(self::TABLE_TASKS));

        $defaultOptions = [
            'nullable' => false
        ];

        try {
            $table->addColumn(self::COL_ID, Table::TYPE_INTEGER, null, array_merge($defaultOptions, ['primary' => true, 'auto_increment' => true]));

            $table->addColumn(self::COL_TAX_RATE_ID, Table::TYPE_INTEGER, null, $defaultOptions,
                'The tax_calculation_rate_id from tax_calculation_rate');
            $table->addForeignKey('fk_tax_rate_updater__tax_calculation_rate_id', self::COL_TAX_RATE_ID,
                $this->installer->getTable('tax_calculation_rate'), 'tax_calculation_rate_id',
                Table::ACTION_RESTRICT);

            $table->addColumn(self::COL_TIME_TO_UPDATE, Table::TYPE_DATETIME, null, $defaultOptions);

            $table->addColumn(self::COL_NEW_RATE, Table::TYPE_FLOAT, null, $defaultOptions);

            $table->addColumn(self::COL_WAS_PROCESSED, Table::TYPE_BOOLEAN, null,
                array_merge($defaultOptions, ['default' => 0]), 'Is this rate allready processed?');

            // unique index
            $table->addIndex('servicehome_tax_rate_updater',
                [self::COL_TAX_RATE_ID, self::COL_TIME_TO_UPDATE, self::COL_NEW_RATE, self::COL_WAS_PROCESSED],
                ['type' => 'UNIQUE']);

        } catch (\Zend_Db_Exception $e) {
            $this->logger->emergency(__("Could not add columns to table. %1", $e->getMessage()));
        }

        return $table;
    }
}