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

namespace Servicehome\TaxRateUpdater\Model\ResourceModel\Task;


use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        parent::_construct();

        $this->_init('Servicehome\TaxRateUpdater\Model\Task', 'Servicehome\TaxRateUpdater\Model\ResourceModel\Task');
    }
}