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

namespace Servicehome\TaxRateUpdater\Model;


use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Servicehome\TaxRateUpdater\Setup\InstallSchema;

class Task extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'servicehome_tax_rate_update_task';

    protected $_cacheTag = self::CACHE_TAG;

    public function __construct(Context $context, Registry $registry, AbstractResource $resource = null,
                                AbstractDb $resourceCollection = null, array $data = [])
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_init('Servicehome\TaxRateUpdater\Model\ResourceModel\Task');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function taxRateId()
    {
        return $this->getData(InstallSchema::COL_TAX_RATE_ID);
    }

    public function taskTime()
    {
        return $this->getData(InstallSchema::COL_TIME_TO_UPDATE);
    }

    public function percent()
    {
        return $this->getData(InstallSchema::COL_NEW_RATE);
    }

    public function setTaskTime(string $taskTimestamp)
    {
        $this->setData(InstallSchema::COL_TIME_TO_UPDATE, $taskTimestamp);
    }

    public function setPercentage(float $percent)
    {
        $this->setData(InstallSchema::COL_NEW_RATE, $percent);
    }

    public function markAsProcessed()
    {
        $this->setData(InstallSchema::COL_WAS_PROCESSED, 1);
    }

    public function __toString()
    {
        return sprintf('Tax rate identifier: %4d, TimeToUpdate: %s, new rate: %7.2f %% [%d]', $this->taxRateId(), $this->taskTime(), $this->percent(), $this->getId());
    }
}