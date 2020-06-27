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

namespace Servicehome\TaxRateUpdater\Observer;


use Exception;
use InvalidArgumentException;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Indexer\Model\Indexer;
use Magento\Indexer\Model\IndexerFactory;
use Psr\Log\LoggerInterface;

class TaxRateUpdateCompletedObserver implements ObserverInterface
{

    protected $indexerIds = [
        'catalog_product_price'
    ];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var IndexerFactory
     */
    protected $indexerFactory;

    /**
     * @var Manager
     */
    private $cacheManager;

    public function __construct(LoggerInterface $logger,
                                ScopeConfigInterface $scopeConfig,
                                IndexerFactory $indexerFactory,
                                Manager $cacheManager)
    {
        $this->logger = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->indexerFactory = $indexerFactory;
        $this->cacheManager = $cacheManager;
    }

    public function execute(Observer $observer)
    {
        if ($this->_scopeConfig->isSetFlag('servicehome_taxrateupdater/setup/reindex')) {
            $this->triggerReindexing();
        }

        if ($this->_scopeConfig->isSetFlag('servicehome_taxrateupdater/setup/flush_cache')) {
            $this->flushCache();
        }
    }

    protected function triggerReindexing()
    {
        $this->logger->debug("TaxRateUpdateCompletedObserver started reindexing...");

        /** @var Indexer $indexer */
        $indexer = $this->indexerFactory->create();

        foreach ($this->indexerIds as $indexerCode) {
            try {
                $_tmp = $indexer->load($indexerCode);
                $_tmp->reindexAll();
            } catch (InvalidArgumentException $e) {
                $this->logger->error($e->getMessage());
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    protected function flushCache()
    {
        $this->logger->debug("TaxRateUpdateCompletedObserver started cache flushing...");

        $this->cacheManager->flush($this->cacheManager->getAvailableTypes());
    }
}