<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\KernelFeature\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Generated\Shared\Transfer\SprykerFeatureCriteriaTransfer;
use Spryker\Shared\Kernel\Transfer\AbstractTransfer;
use Spryker\Zed\KernelFeature\Business\KernelFeatureFacadeInterface;

/**
 * Base provider for API Platform resources with pagination and filtering support.
 *
 * @template T of object
 * @implements \ApiPlatform\State\ProviderInterface<T>
 */
abstract class AbstractBackendProvider implements ProviderInterface
{
    protected const FILTER_KEY_PAGE = 'page';

    protected const FILTER_KEY_PAGE_SIZE = 'pageSize';

    protected const FILTER_KEY_ITEMS_PER_PAGE = 'itemsPerPage';

    protected const FILTER_KEY_SEARCH = 'search';

    protected const FILTER_KEY_FILTER = 'filter';

    protected const FILTER_KEY_FILTERS = 'filters';

    protected const RANGE_KEY_FROM = 'from';

    protected const RANGE_KEY_TO = 'to';

    protected const CONFIG_KEY_FIELDS = 'fields';

    protected const CONFIG_KEY_VIEW = 'view';

    protected const CONFIG_KEY_COMPONENTS = 'components';

    protected const CONFIG_KEY_API = 'api';

    protected const CONFIG_KEY_ENTITIES = 'entities';

    protected const FIELD_PREFIX = 'field.';

    protected ?Operation $currentOperation = null;

    protected ?array $entityConfiguration = null;

    public function __construct(
        protected ?KernelFeatureFacadeInterface $kernelFeatureFacade = null,
    ) {
    }

    /**
     * @param \ApiPlatform\Metadata\Operation $operation
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<object>|object|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|iterable|null
    {
        $this->currentOperation = $operation;
        $identifier = $this->getIdentifierFromUriVariables($uriVariables);

        if ($identifier === null) {
            return $this->provideCollection($context);
        }

        return $this->provideItem($identifier);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return \ApiPlatform\State\Pagination\TraversablePaginator<T>
     */
    protected function provideCollection(array $context): TraversablePaginator
    {
        $filters = $context[static::FILTER_KEY_FILTERS] ?? [];
        $page = (int)($filters[static::FILTER_KEY_PAGE] ?? 1);
        $itemsPerPage = (int)($filters[static::FILTER_KEY_PAGE_SIZE] ?? $filters[static::FILTER_KEY_ITEMS_PER_PAGE] ?? 10);

        $items = $this->fetchAllItems();

        if (isset($filters[static::FILTER_KEY_SEARCH]) && $filters[static::FILTER_KEY_SEARCH] !== '') {
            $items = $this->applySearch($items, (string)$filters[static::FILTER_KEY_SEARCH]);
        }

        if (isset($filters[static::FILTER_KEY_FILTER])) {
            $filterData = is_string($filters[static::FILTER_KEY_FILTER]) ? json_decode($filters[static::FILTER_KEY_FILTER], true) : $filters[static::FILTER_KEY_FILTER];

            if (is_array($filterData)) {
                $items = $this->applyFilters($items, $filterData);
            }
        }

        $totalItems = count($items);

        $maxPage = max(1, (int)ceil($totalItems / $itemsPerPage));
        if ($page > $maxPage) {
            $page = $maxPage;
        }

        $offset = ($page - 1) * $itemsPerPage;
        $paginatedItems = array_slice($items, $offset, $itemsPerPage);

        $resources = [];
        foreach ($paginatedItems as $item) {
            $resources[] = $this->mapTransferToResource($item);
        }

        return new TraversablePaginator(
            new ArrayIterator($resources),
            (float)$page,
            (float)$itemsPerPage,
            (float)$totalItems,
        );
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    abstract protected function getIdentifierFromUriVariables(array $uriVariables): ?string;

    abstract protected function provideItem(string $identifier): ?object;

    /**
     * @return array<\Spryker\Shared\Kernel\Transfer\AbstractTransfer>
     */
    abstract protected function fetchAllItems(): array;

    abstract protected function mapTransferToResource(AbstractTransfer $transfer): object;

    /**
     * @param array<\Spryker\Shared\Kernel\Transfer\AbstractTransfer> $items
     *
     * @return array<\Spryker\Shared\Kernel\Transfer\AbstractTransfer>
     */
    protected function applySearch(array $items, string $searchTerm): array
    {
        $searchableFields = $this->getSearchableFields();
        if (!$searchableFields) {
            return $items;
        }

        $searchTerm = mb_strtolower($searchTerm);
        $filteredItems = [];

        foreach ($items as $item) {
            $itemData = $item->toArray(true, true);

            foreach ($searchableFields as $field) {
                $value = $itemData[$field] ?? null;
                if ($value !== null && mb_stripos((string)$value, $searchTerm) !== false) {
                    $filteredItems[] = $item;

                    break;
                }
            }
        }

        return $filteredItems;
    }

    /**
     * @param array<\Spryker\Shared\Kernel\Transfer\AbstractTransfer> $items
     * @param array<string, mixed> $filterData
     *
     * @return array<\Spryker\Shared\Kernel\Transfer\AbstractTransfer>
     */
    protected function applyFilters(array $items, array $filterData): array
    {
        $fieldMapping = $this->getFilterFieldMapping();
        $filteredItems = [];

        foreach ($items as $item) {
            $itemData = $item->toArray(true, true);
            $matches = true;

            foreach ($filterData as $field => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                if (is_array($value) && !$value) {
                    continue;
                }

                $transferField = $fieldMapping[$field] ?? $field;
                $itemValue = $itemData[$transferField] ?? null;

                if (is_array($value) && (isset($value[static::RANGE_KEY_FROM]) || isset($value[static::RANGE_KEY_TO]))) {
                    if (empty($value[static::RANGE_KEY_FROM]) && empty($value[static::RANGE_KEY_TO])) {
                        continue;
                    }
                    if (!$this->matchesRangeFilter($itemValue, $value)) {
                        $matches = false;

                        break;
                    }

                    continue;
                }

                if ($itemValue !== $value) {
                    $matches = false;

                    break;
                }
            }

            if ($matches) {
                $filteredItems[] = $item;
            }
        }

        return $filteredItems;
    }

    /**
     * @param array{from?: string, to?: string}|array $range
     */
    protected function matchesRangeFilter(mixed $itemValue, array $range): bool
    {
        if ($itemValue === null) {
            return false;
        }

        $itemTimestamp = strtotime((string)$itemValue);
        if ($itemTimestamp === false) {
            return false;
        }

        if (isset($range[static::RANGE_KEY_FROM])) {
            $fromTimestamp = strtotime($range[static::RANGE_KEY_FROM]);
            if ($fromTimestamp !== false && $itemTimestamp < $fromTimestamp) {
                return false;
            }
        }

        if (isset($range[static::RANGE_KEY_TO])) {
            $toTimestamp = strtotime($range[static::RANGE_KEY_TO]);
            if ($toTimestamp !== false && $itemTimestamp > $toTimestamp) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEntityConfiguration(): array
    {
        if ($this->entityConfiguration !== null) {
            return $this->entityConfiguration;
        }

        $this->entityConfiguration = [];

        if ($this->kernelFeatureFacade === null) {
            return $this->entityConfiguration;
        }

        $entityName = $this->currentOperation?->getShortName();
        $featureName = $this->extractFeatureNameFromNamespace();

        if ($featureName === null || $entityName === null) {
            return $this->entityConfiguration;
        }

        $sprykerFeatureCriteriaTransfer = new SprykerFeatureCriteriaTransfer();
        $sprykerFeatureCriteriaTransfer->setSprykerFeatureName($featureName);

        $featureCollectionTransfer = $this->kernelFeatureFacade->getFeatureCollection($sprykerFeatureCriteriaTransfer);
        $features = $featureCollectionTransfer->getSprykerFeatures();

        if ($features->count() > 0) {
            $feature = $features->getIterator()->current();
            $configuration = $feature->getConfiguration();

            if (isset($configuration[static::CONFIG_KEY_ENTITIES][$entityName])) {
                $this->entityConfiguration = $configuration[static::CONFIG_KEY_ENTITIES][$entityName];
            }
        }

        return $this->entityConfiguration;
    }

    /**
     * Extract feature name from provider class namespace.
     * Supports any namespace pattern: {Prefix}\Glue\{FeatureName}\...
     * Examples: SprykerFeature\Glue\CustomerRelationManagement\...
     *           PyzFeature\Glue\MyCustomFeature\...
     *           Pyz\Glue\AcArManagement\...
     *
     * @return string|null
     */
    protected function extractFeatureNameFromNamespace(): ?string
    {
        $className = static::class;
        if (preg_match('/(\w+)\\\\Glue\\\\(\w+)\\\\/', $className, $matches)) {
            return $matches[2];
        }

        return null;
    }

    /**
     * Get searchable fields from entity configuration.
     * Reads from fields where searchable: true.
     *
     * @return array<string>
     */
    protected function getSearchableFields(): array
    {
        $entityConfig = $this->getEntityConfiguration();

        if (isset($entityConfig[static::CONFIG_KEY_FIELDS])) {
            return array_keys(array_filter(
                $entityConfig[static::CONFIG_KEY_FIELDS],
                fn ($field) => is_array($field) && ($field['searchable'] ?? false),
            ));
        }

        if (isset($entityConfig[static::CONFIG_KEY_VIEW][static::CONFIG_KEY_COMPONENTS])) {
            $searchableFields = [];
            foreach ($entityConfig[static::CONFIG_KEY_VIEW][static::CONFIG_KEY_COMPONENTS] as $key => $component) {
                if (str_starts_with($key, static::FIELD_PREFIX) && is_array($component) && ($component['searchable'] ?? false)) {
                    $fieldName = $component['name'] ?? null;
                    if ($fieldName) {
                        $searchableFields[] = $fieldName;
                    }
                }
            }

            return $searchableFields;
        }

        return [];
    }

    /**
     * Get filter field mapping from entity configuration.
     * Reads from fields where filterable: true.
     * Field names should match Transfer property names (no mapping needed).
     *
     * @return array<string, string>
     */
    protected function getFilterFieldMapping(): array
    {
        $entityConfig = $this->getEntityConfiguration();

        if (isset($entityConfig[static::CONFIG_KEY_FIELDS])) {
            $mapping = [];

            foreach ($entityConfig[static::CONFIG_KEY_FIELDS] as $fieldName => $field) {
                if (is_array($field) && ($field['filterable'] ?? false)) {
                    $mapping[$fieldName] = $fieldName;
                }
            }

            return $mapping;
        }

        if (isset($entityConfig[static::CONFIG_KEY_VIEW][static::CONFIG_KEY_COMPONENTS])) {
            $mapping = [];
            foreach ($entityConfig[static::CONFIG_KEY_VIEW][static::CONFIG_KEY_COMPONENTS] as $key => $component) {
                if (str_starts_with($key, static::FIELD_PREFIX) && is_array($component) && ($component['filterable'] ?? false)) {
                    $fieldName = $component['name'] ?? null;
                    if ($fieldName) {
                        $mapping[$fieldName] = $fieldName;
                    }
                }
            }

            return $mapping;
        }

        return $entityConfig[static::CONFIG_KEY_API][static::FILTER_KEY_FILTERS] ?? [];
    }
}
