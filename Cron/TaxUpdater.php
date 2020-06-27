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

namespace Servicehome\TaxRateUpdater\Cron;


use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Servicehome\TaxRateUpdater\Model\Repository;
use Servicehome\TaxRateUpdater\Model\Task;
use Magento\Framework\Event\ManagerInterface as EventManager;

class TaxUpdater
{
    const EVENT_TAX_RATE_UPDATED_SUCCESSFULLY = 'servicehome_taxupdater_updaterate_after';
    const EVENT_TAX_RATE_PROCESSING_COMPLETED = 'servicehome_taxupdater_processing_completed';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\Api\SearchResultsInterface
     */
    private $task_collection;

    /**
     * @var TaxRateRepositoryInterface
     */
    private $taxRateRepo;

    /**
     * @var Repository
     */
    private $taskRepo;

    /**
     * @var EventManager
     */
    private $eventManager;
    
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    public function __construct(LoggerInterface $logger,
                                ScopeConfigInterface $scopeConfig,
                                TaxRateRepositoryInterface $taxRateRepo,
                                Repository $repo,
                                EventManager $eventManager)
    {
        $this->logger = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->taxRateRepo = $taxRateRepo;
        $this->taskRepo = $repo;
        $this->eventManager = $eventManager;
    }

    public function execute()
    {
        if (false === $this->_scopeConfig->isSetFlag('servicehome_taxrateupdater/setup/module_active')) {
            return;
        }

        if ($this->hasTasks()) {
            $this->logger->debug(sprintf("Handle %d tax-rate update tasks...", $this->task_collection->getTotalCount()));

            $this->process();
        }

    }

    protected function hasTasks(): bool
    {
        $hasTasks = false;

        try {
            $this->task_collection = $this->fetchTasks();
            $hasTasks = $this->task_collection->getTotalCount() > 0;
        } catch (NoSuchEntityException $e) {
            // nothing
        }

        return $hasTasks;
    }

    /**
     * @return \Magento\Framework\Api\SearchResultsInterface
     * @throws NoSuchEntityException
     */
    protected function fetchTasks()
    {
        return $this->taskRepo->getTasksToProcess();
    }

    protected function process()
    {
        /** @var Task $task */
        $items = $this->task_collection->getItems();
        foreach ($items as $task) {
            $this->logger->debug("Tax-Rate Update", [$task]);

            try {
                $taxRate = $this->taxRateRepo->get($task->taxRateId());
                $taxRate->setRate($task->percent());
                $this->taxRateRepo->save($taxRate);

                $task->markAsProcessed();
                $this->taskRepo->save($task);

                $this->eventManager->dispatch(self::EVENT_TAX_RATE_UPDATED_SUCCESSFULLY, ['task' => $task]);

            } catch (NoSuchEntityException $e) {
                $this->logger->warning(sprintf("Unknown tax rate identifier! [%d]", $task->taxRateId()));
            } catch (InputException $e) {
                $this->logger->warning(sprintf("Error on updating tax rate! [%s]", $e->getMessage()));
            } catch (Exception $e) {
                $this->logger->warning(sprintf("Error on updating tax rate! [%s]", $e->getMessage()));
            }
        }

        $this->eventManager->dispatch(self::EVENT_TAX_RATE_PROCESSING_COMPLETED);
    }
}