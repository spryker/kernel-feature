<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Zed\KernelFeature\Business\Transformer;

class SprykerFeatureEntityConfigTransformer
{
    /**
     * Transforms DSL with "view.components"/"view.layout" into legacy structure with "view.root".
     * If view is already in legacy format or missing, returns DSL unchanged.
     */
    public function transform(array $dsl): array
    {
        if (!isset($dsl['view']) || !is_array($dsl['view'])) {
            return $dsl;
        }

        $view = $dsl['view'];

        $hasNewFormat = isset($view['components']) || isset($view['layout']);
        $hasLegacyFormat = isset($view['root']);

        if (!$hasNewFormat || $hasLegacyFormat) {
            return $dsl;
        }

        $components = isset($view['components']) && is_array($view['components']) ? $view['components'] : [];

        $layoutDefinition = isset($view['layout']) && is_array($view['layout']) ? $view['layout'] : [];

        $dsl['view'] = $view;

        $layouts = $this->normalizeLayouts($layoutDefinition);

        foreach ($layouts as $layoutConfig) {
            $componentId = isset($layoutConfig['use']) && is_string($layoutConfig['use']) ? $layoutConfig['use'] : null;
            $routeKey = $this->resolveRouteKey($componentId, $components);

            $dsl['view'][$routeKey] = $this->buildNode($layoutConfig, $components);
        }

        unset($dsl['view']['layout'], $dsl['view']['components']);

        return $dsl;
    }

    /**
     * Normalizes layout definition to a list of layout configs.
     *
     * Supports:
     * - layout: { use: 'layout.customer.page' }
     * - layout: { use: ['layout.customer.page', 'layout.customer.edit.page'] }
     * - layout: { ... } (legacy single-layout definition)
     */
    protected function normalizeLayouts(array $layoutDefinition): array
    {
        if (isset($layoutDefinition['use']) && is_string($layoutDefinition['use'])) {
            return [['use' => $layoutDefinition['use']]];
        }

        if (isset($layoutDefinition['use']) && is_array($layoutDefinition['use']) && !$this->isAssoc($layoutDefinition['use'])) {
            $normalized = [];

            foreach ($layoutDefinition['use'] as $layoutRef) {
                if (is_string($layoutRef)) {
                    $normalized[] = ['use' => $layoutRef];

                    continue;
                }

                if (is_array($layoutRef)) {
                    $normalized[] = $layoutRef;
                }
            }

            return $normalized;
        }

        if ($layoutDefinition === []) {
            return [];
        }

        return [$layoutDefinition];
    }

    /**
     * Resolves the final view key for a layout based on component virtualRoute.
     * Defaults to "root" when no virtualRoute is provided.
     */
    protected function resolveRouteKey(?string $componentId, array $components): string
    {
        if ($componentId !== null && isset($components[$componentId]) && is_array($components[$componentId])) {
            $virtualRoute = $components[$componentId]['virtualRoute'] ?? null;

            if (is_string($virtualRoute) && $virtualRoute !== '') {
                return $virtualRoute;
            }
        }

        return 'root';
    }

    /**
     * Builds a node from a layout or component definition.
     * Supports keys: "raw", "use", "overrides", "slots".
     */
    protected function buildNode(array $definition, array $components): array
    {
        // raw: pass through as-is
        if (array_key_exists('raw', $definition)) {
            $raw = $definition['raw'];

            return is_array($raw) ? $raw : $definition;
        }

        // use + overrides: resolve component by id, merge and build again
        if (isset($definition['use'])) {
            $componentId = $definition['use'];

            if (!is_string($componentId) || !isset($components[$componentId]) || !is_array($components[$componentId])) {
                // Unknown component id – return definition as-is to avoid hard failures.
                $definitionWithoutUse = $definition;
                unset($definitionWithoutUse['use'], $definitionWithoutUse['overrides']);

                return $this->buildNode($definitionWithoutUse, $components);
            }

            $baseDefinition = $components[$componentId];
            $overrides = isset($definition['overrides']) && is_array($definition['overrides']) ? $definition['overrides'] : [];

            $mergedDefinition = $this->deepMerge($baseDefinition, $overrides);

            return $this->buildNode($mergedDefinition, $components);
        }

        $node = $definition;

        if (isset($node['slots']) && is_array($node['slots'])) {
            $node['slots'] = $this->processSlots($node['slots'], $components);
        }

        foreach ($node as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if ($this->isAssoc($value)) {
                $node[$key] = $this->buildNode($value, $components);

                continue;
            }

            foreach ($value as $index => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $value[$index] = $this->buildNode($item, $components);
            }

            $node[$key] = $value;
        }

        return $node;
    }

    /**
     * Processes slots in both supported formats:
     * - map: slotName => [items...]
     * - list: [ { ...slotItem... } ]
     */
    protected function processSlots(array $slots, array $components): array
    {
        if ($this->isAssoc($slots)) {
            return $this->buildSlots($slots, $components);
        }

        $processedSlots = [];

        foreach ($slots as $slotItem) {
            if (!is_array($slotItem)) {
                $processedSlots[] = $slotItem;

                continue;
            }

            $processedSlots[] = $this->buildNode($slotItem, $components);
        }

        return $processedSlots;
    }

    /**
     * Transforms slots from map form into legacy list form and builds children.
     *
     * New format:
     *  slots:
     *      actions:
     *          - use: action.customer.create
     *      content:
     *          - component: TableComponent
     *
     * Legacy format:
     *  slots:
     *      [
     *          { slot: 'actions', ... },
     *          { slot: 'content', ... },
     *      ]
     */
    protected function buildSlots(array $slotsDefinition, array $components): array
    {
        $result = [];

        foreach ($slotsDefinition as $slotName => $items) {
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $itemDefinition) {
                if (!is_array($itemDefinition)) {
                    continue;
                }

                $childNode = $this->buildNode($itemDefinition, $components);

                if (!is_array($childNode)) {
                    continue;
                }

                $childNode['slot'] = $slotName;
                $result[] = $childNode;
            }
        }

        return $result;
    }

    /**
     * Recursively merges two arrays without hardcoding specific keys.
     *
     * - For associative arrays: merge per key, with values from $overrides taking precedence.
     * - For numeric-indexed arrays (lists): $overrides replaces $base.
     */
    protected function deepMerge(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $overrideValue) {
            if (!array_key_exists($key, $base)) {
                $base[$key] = $overrideValue;

                continue;
            }

            $baseValue = $base[$key];

            if (is_array($baseValue) && is_array($overrideValue)) {
                if ($this->isAssoc($baseValue) && $this->isAssoc($overrideValue)) {
                    $base[$key] = $this->deepMerge($baseValue, $overrideValue);
                } else {
                    // For numeric arrays or mixed, overrides replace base entirely.
                    $base[$key] = $overrideValue;
                }

                continue;
            }

            $base[$key] = $overrideValue;
        }

        return $base;
    }

    /**
     * Detects if an array is associative.
     */
    protected function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
