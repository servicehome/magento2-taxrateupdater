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


use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Api\Data\BookmarkSearchResultsInterfaceFactory;
use Servicehome\TaxRateUpdater\Model\ResourceModel\Task as TaskResourceModel;
use Servicehome\TaxRateUpdater\Setup\InstallSchema;

class Repository
{
    /**
     * @var TaskResourceModel
     */
    private $resourceModel;

    /**
     * @var TaskFactory
     */
    private $taskFactory;

    /**
     * @var TaskResourceModel\CollectionFactory
     */
    private $taskCollectionFactory;

    /**
     * @var BookmarkSearchResultsInterfaceFactory
     */
    private $bookmarkSearchResultsFactory;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    public function __construct(TaskResourceModel $resourceModel, TaskFactory $taskFactory,
                                TaskResourceModel\CollectionFactory $taskCollectionFactory,
                                BookmarkSearchResultsInterfaceFactory $bookmarkSearchResultsInterfaceFactory,
                                SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
                                FilterBuilder $filterBuilder)
    {
        $this->resourceModel = $resourceModel;
        $this->taskFactory = $taskFactory;
        $this->taskCollectionFactory = $taskCollectionFactory;
        $this->bookmarkSearchResultsFactory = $bookmarkSearchResultsInterfaceFactory;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * @param int $id
     * @return Task
     * @throws NoSuchEntityException
     */
    public function getById(int $id): Task
    {
        $task = $this->taskFactory->create();

        $this->resourceModel->load($task, $id);
        if (!$task->getId()) {
            throw new NoSuchEntityException(__('Task with specified ID "%1" not found.', $id));
        }

        return $task;
    }

    public function save(Task $task)
    {
        if ($task->getId()) {
            $task = $this->getById($task->getId())->addData($task->getData());
        }
        $this->resourceModel->save($task);
    }

    /**
     * @return SearchResultsInterface
     * @throws NoSuchEntityException
     */
    public function getTasksToProcess()
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $filters = [];
        $filters[] = $this->filterBuilder->setField(InstallSchema::COL_WAS_PROCESSED)
            ->setValue(0)
            ->setConditionType('=')
            ->create();

        $now = new \DateTime();
        $filters[] = $this->filterBuilder->setField(InstallSchema::COL_TIME_TO_UPDATE)
            ->setConditionType('lt') // the database field is in the past
            ->setValue($now->format('Y-m-d H:i:s'))
            ->create();

        $searchCriteriaBuilder->addFilters($filters);

        $searchCriteria = $searchCriteriaBuilder->create();
        return $this->getList($searchCriteria);
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     * @throws NoSuchEntityException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        /** @var SearchResultsInterface $searchResults */
        $searchResults = $this->bookmarkSearchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var TaskResourceModel\Collection $taskCollection */
        $taskCollection = $this->taskCollectionFactory->create();
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $taskCollection);
        }

        $searchResults->setTotalCount($taskCollection->getSize());

        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            $this->addOrderToCollection($sortOrders, $taskCollection);
        }

        $items = [];
        /** @var Task $task */
        foreach ($taskCollection->getItems() as $task) {
            $items[] = $task;
        }
        $searchResults->setItems($items);

        return $searchResults;
    }

    /**
     * @param FilterGroup $filterGroup
     * @param TaskResourceModel\Collection $collection
     */
    private function addFilterGroupToCollection(FilterGroup $filterGroup, TaskResourceModel\Collection $collection): void
    {
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
        }
    }
}