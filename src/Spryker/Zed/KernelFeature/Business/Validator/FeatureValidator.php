<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Zed\KernelFeature\Business\Validator;

use Generated\Shared\Transfer\SprykerFeatureCriteriaTransfer;
use Generated\Shared\Transfer\SprykerFeatureValidationResultTransfer;
use JsonSchema\Validator;
use Laminas\Filter\FilterChain;
use Laminas\Filter\StringToLower;
use Laminas\Filter\Word\CamelCaseToUnderscore;
use Spryker\Zed\KernelFeature\Business\SprykerFeature\SprykerFeatureReader;
use Symfony\Component\Yaml\Yaml;

class FeatureValidator
{
    protected const SCHEMA_PATH = APPLICATION_ROOT_DIR . '/src/Spryker/KernelFeature/resources/schema/entity.schema.json';

    /**
     * @var array<string>
     */
    protected const REQUIRED_ROOT_KEYS = ['feature', 'entities', 'navigation'];

    /**
     * @var array<string>
     */
    protected const REQUIRED_NAVIGATION_KEYS = ['label'];

    public function __construct(
        protected readonly SprykerFeatureReader $featureReader,
    ) {
    }

    /**
     * Validates feature configuration structure.
     *
     * @param \Generated\Shared\Transfer\SprykerFeatureCriteriaTransfer $sprykerFeatureCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\SprykerFeatureValidationResultTransfer
     */
    public function validate(SprykerFeatureCriteriaTransfer $sprykerFeatureCriteriaTransfer): SprykerFeatureValidationResultTransfer
    {
        $featureName = $sprykerFeatureCriteriaTransfer->getSprykerFeatureName() ?? 'unknown';
        $sprykerFeatureCollectionTransfer = $this->featureReader->findFeatures($sprykerFeatureCriteriaTransfer);

        if ($sprykerFeatureCollectionTransfer->getSprykerFeatures()->count() === 0) {
            $result = new SprykerFeatureValidationResultTransfer();
            $result->setIsValid(false);
            $result->setFeatureName($featureName);
            $result->addError(sprintf('Feature "%s" not found in configuration.', $featureName));

            return $result;
        }

        $feature = $sprykerFeatureCollectionTransfer->getSprykerFeatures()->getIterator()->current();

        return $this->validateConfiguration($featureName, $feature->getConfiguration() ?? [], $sprykerFeatureCriteriaTransfer);
    }

    /**
     * @param string $featureName
     * @param array<string, array<string, mixed>> $configuration
     * @param \Generated\Shared\Transfer\SprykerFeatureCriteriaTransfer $sprykerFeatureCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\SprykerFeatureValidationResultTransfer
     */
    protected function validateConfiguration(
        string $featureName,
        array $configuration,
        SprykerFeatureCriteriaTransfer $sprykerFeatureCriteriaTransfer
    ): SprykerFeatureValidationResultTransfer {
        $errors = [];
        $errors = array_merge($errors, $this->validateRootConfiguration($configuration));
        $errors = array_merge($errors, $this->validateNavigationConfiguration($configuration));
        $errors = array_merge($errors, $this->validateEntities($featureName, $configuration, $sprykerFeatureCriteriaTransfer));

        return $this->createValidationResult($featureName, $errors);
    }

    protected function validateRootConfiguration(array $configuration): array
    {
        return $this->validateRequiredKeys(
            $configuration,
            static::REQUIRED_ROOT_KEYS,
            'Root configuration',
        );
    }

    protected function validateNavigationConfiguration(array $configuration): array
    {
        if (!isset($configuration['navigation'])) {
            return [];
        }

        return $this->validateRequiredKeys(
            $configuration['navigation'],
            static::REQUIRED_NAVIGATION_KEYS,
            'Navigation',
        );
    }

    protected function validateEntities(
        string $featureName,
        array $configuration,
        SprykerFeatureCriteriaTransfer $sprykerFeatureCriteriaTransfer
    ): array {
        if (!isset($configuration['entities'])) {
            return [];
        }

        $featureResourcePath = $this->resolveFeatureResourcePath($featureName);
        if (!$featureResourcePath) {
            return [];
        }

        return $this->validateEntityCollection(
            $configuration['entities'],
            $featureResourcePath,
            $sprykerFeatureCriteriaTransfer->getEntityName(),
        );
    }

    protected function resolveFeatureResourcePath(string $featureName): ?string
    {
        $featuresYmlPath = APPLICATION_ROOT_DIR . '/.spryker/features.yml';
        $featuresYml = (new Yaml())->parseFile($featuresYmlPath);
        $featureConfig = $featuresYml[$featureName] ?? null;

        if (!$featureConfig) {
            return null;
        }

        $featureFilePath = APPLICATION_ROOT_DIR . '/' . $featureConfig['url'];

        return dirname($featureFilePath);
    }

    protected function validateEntityCollection(array $entities, string $featureResourcePath, ?string $filterEntityName): array
    {
        $errors = [];
        $entityNameFilter = $this->createEntityNameFilter();

        foreach ($entities as $entityName => $entityConfig) {
            if ($this->shouldSkipEntity($entityName, $filterEntityName)) {
                continue;
            }

            $errors = array_merge($errors, $this->validateEntityFromFile(
                $entityName,
                $featureResourcePath,
                $entityNameFilter,
            ));
        }

        return $errors;
    }

    protected function createEntityNameFilter(): FilterChain
    {
        $filter = new FilterChain();
        $filter
            ->attachByName(CamelCaseToUnderscore::class)
            ->attachByName(StringToLower::class);

        return $filter;
    }

    protected function shouldSkipEntity(string $entityName, ?string $filterEntityName): bool
    {
        return $filterEntityName !== null && $filterEntityName !== $entityName;
    }

    protected function createValidationResult(string $featureName, array $errors): SprykerFeatureValidationResultTransfer
    {
        $result = new SprykerFeatureValidationResultTransfer();
        $result->setFeatureName($featureName);
        $result->setIsValid(count($errors) === 0);

        foreach ($errors as $error) {
            $result->addError($error);
        }

        return $result;
    }

    /**
     * Validates entity by reading original YAML file.
     *
     * @param string $entityName
     * @param string $featureResourcePath
     * @param \Laminas\Filter\FilterChain $entityNameFilter
     *
     * @return array<string>
     */
    protected function validateEntityFromFile(
        string $entityName,
        string $featureResourcePath,
        FilterChain $entityNameFilter
    ): array {
        $pathToEntityYaml = $this->buildEntityYamlPath($featureResourcePath, $entityName, $entityNameFilter);

        if (!file_exists($pathToEntityYaml)) {
            return [sprintf('Entity "%s" YAML file not found at: %s', $entityName, $pathToEntityYaml)];
        }

        $entityConfig = (new Yaml())->parseFile($pathToEntityYaml);

        return $this->validateEntity($entityName, $entityConfig);
    }

    protected function buildEntityYamlPath(string $featureResourcePath, string $entityName, FilterChain $filter): string
    {
        return sprintf(
            '%s/entity/%s.yml',
            $featureResourcePath,
            $filter->filter($entityName),
        );
    }

    protected function validateEntity(string $entityName, array $entityConfig): array
    {
        $errors = [];
        $hasFields = isset($entityConfig['fields']);
        $hasView = isset($entityConfig['view']) && is_array($entityConfig['view']);

        if ($hasFields) {
            $errors = array_merge($errors, $this->validateWithSchema($entityName, $entityConfig));
            $errors = array_merge($errors, $this->validateCustomPresetRequirements($entityName, $entityConfig, $hasView));
        }

        if (!$hasFields && !$hasView) {
            $errors[] = sprintf('Entity "%s": Configuration without "fields" requires "view" section', $entityName);
        }

        if ($hasView) {
            $errors = array_merge($errors, $this->validateView($entityName, $entityConfig['view']));
        }

        return $errors;
    }

    protected function validateCustomPresetRequirements(string $entityName, array $entityConfig, bool $hasView): array
    {
        $uiPreset = $entityConfig['ui']['preset'] ?? null;

        if ($uiPreset === 'custom' && !$hasView) {
            return [sprintf('Entity "%s": ui.preset "custom" requires "view" section', $entityName)];
        }

        return [];
    }

    /**
     * Validates entity configuration against JSON Schema.
     *
     * @param string $entityName
     * @param array<string, mixed> $entityConfig
     *
     * @return array<string>
     */
    protected function validateWithSchema(string $entityName, array $entityConfig): array
    {
        if (!file_exists(static::SCHEMA_PATH)) {
            return [sprintf('Entity "%s": JSON Schema file not found at %s', $entityName, static::SCHEMA_PATH)];
        }

        $schema = $this->loadJsonSchema();
        if (!$schema) {
            return [sprintf('Entity "%s": Failed to parse JSON Schema', $entityName)];
        }

        return $this->performSchemaValidation($entityName, $entityConfig, $schema);
    }

    protected function loadJsonSchema(): ?object
    {
        $schemaContent = file_get_contents(static::SCHEMA_PATH);
        if ($schemaContent === false) {
            return null;
        }

        return json_decode($schemaContent);
    }

    protected function performSchemaValidation(string $entityName, array $entityConfig, object $schema): array
    {
        $validator = new Validator();
        $jsonString = json_encode($entityConfig);
        if ($jsonString === false) {
            return [sprintf('Entity "%s": Failed to encode config to JSON', $entityName)];
        }
        $data = json_decode($jsonString);
        $validator->validate($data, $schema);

        if ($validator->isValid()) {
            return [];
        }

        return $this->formatSchemaErrors($entityName, $validator->getErrors());
    }

    protected function formatSchemaErrors(string $entityName, array $validationErrors): array
    {
        $errors = [];

        foreach ($validationErrors as $error) {
            $property = $error['property'] ?: 'root';
            $errors[] = sprintf(
                'Entity "%s" [%s]: %s',
                $entityName,
                $property,
                $error['message'],
            );
        }

        return $errors;
    }

    protected function validateView(string $entityName, array $view): array
    {
        $errors = [];

        // Must have either 'root' (legacy) or 'layout'+'components' (new format)
        $hasLegacyFormat = isset($view['root']);
        $hasNewFormat = isset($view['layout']) || isset($view['components']);

        if (!$hasLegacyFormat && !$hasNewFormat) {
            $errors[] = sprintf(
                'Entity "%s" view must have either "root" (legacy) or "layout"/"components" (new format).',
                $entityName,
            );
        }

        // Validate component references in new format
        if (isset($view['components']) && is_array($view['components'])) {
            $componentIds = array_keys($view['components']);

            // Check for undefined component references
            $errors = array_merge($errors, $this->validateComponentReferences(
                $entityName,
                $view,
                $componentIds,
            ));
        }

        return $errors;
    }

    protected function validateComponentReferences(string $entityName, array $view, array $definedComponents): array
    {
        $errors = [];
        $referencedComponents = $this->findAllUseReferences($view);

        foreach ($referencedComponents as $ref) {
            if (!in_array($ref, $definedComponents, true)) {
                $errors[] = sprintf(
                    'Entity "%s" references undefined component "%s".',
                    $entityName,
                    $ref,
                );
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string>
     */
    protected function findAllUseReferences(array $config): array
    {
        $references = [];

        foreach ($config as $key => $value) {
            if ($key === 'use' && is_string($value)) {
                $references[] = $value;
            } elseif (is_array($value)) {
                $references = array_merge($references, $this->findAllUseReferences($value));
            }
        }

        return array_unique($references);
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string> $requiredKeys
     * @param string $context
     *
     * @return array<string>
     */
    protected function validateRequiredKeys(array $config, array $requiredKeys, string $context): array
    {
        $errors = [];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $config)) {
                $errors[] = sprintf('%s is missing required key "%s".', $context, $key);
            }
        }

        return $errors;
    }
}
