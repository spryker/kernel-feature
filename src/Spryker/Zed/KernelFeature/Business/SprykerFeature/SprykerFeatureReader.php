<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Zed\KernelFeature\Business\SprykerFeature;

use Generated\Shared\Transfer\SprykerFeatureCollectionTransfer;
use Generated\Shared\Transfer\SprykerFeatureCriteriaTransfer;
use Generated\Shared\Transfer\SprykerFeatureTransfer;
use Laminas\Filter\FilterChain;
use Laminas\Filter\StringToLower;
use Laminas\Filter\Word\CamelCaseToUnderscore;
use Spryker\Zed\ComposableBackofficeUi\Business\ComposableBackofficeUiFacadeInterface;
use Spryker\Zed\KernelFeature\Business\Cache\SprykerFeatureCache;
use Spryker\Zed\KernelFeature\Business\Exception\EntityConfigurationNotFoundException;
use Spryker\Zed\KernelFeature\Business\Exception\FeatureConfigurationNotFoundException;
use Spryker\Zed\KernelFeature\Business\Transformer\SprykerFeatureEntityConfigTransformer;
use Symfony\Component\Yaml\Yaml;

class SprykerFeatureReader
{
    public function __construct(
        protected SprykerFeatureEntityConfigTransformer $featureConfigTransformer,
        protected SprykerFeatureCache $cache,
        protected ComposableBackofficeUiFacadeInterface $composableBackofficeUiFacade,
    ) {
    }

    public function findFeatures(SprykerFeatureCriteriaTransfer $sprykerFeatureCriteriaTransfer): SprykerFeatureCollectionTransfer
    {
        $requestedSprykerFeatureName = $sprykerFeatureCriteriaTransfer->getSprykerFeatureName();
        $cacheKey = $requestedSprykerFeatureName ?: 'all';

        $cachedData = $this->cache->load($cacheKey);
        if ($cachedData !== null) {
            return $this->cache->hydrate($cachedData);
        }

        $sprykerFeatureCollectionTransfer = $this->buildFeatureCollection($sprykerFeatureCriteriaTransfer);

        $this->cache->save($cacheKey, $sprykerFeatureCollectionTransfer);

        return $sprykerFeatureCollectionTransfer;
    }

    protected function buildFeatureCollection(SprykerFeatureCriteriaTransfer $sprykerFeatureCriteriaTransfer): SprykerFeatureCollectionTransfer
    {
        $sprykerFeaturesRootFilePath = APPLICATION_ROOT_DIR . '/.spryker/features.yml';

        if (file_exists($sprykerFeaturesRootFilePath) === false) {
            return new SprykerFeatureCollectionTransfer();
        }

        $ymlReader = new Yaml();
        $ymlContent = $ymlReader->parseFile($sprykerFeaturesRootFilePath);
        $sprykerFeatureCollectionTransfer = new SprykerFeatureCollectionTransfer();

        $requestedSprykerFeatureName = $sprykerFeatureCriteriaTransfer->getSprykerFeatureName();

        $entityNameFilter = new FilterChain();
        $entityNameFilter
            ->attachByName(CamelCaseToUnderscore::class)
            ->attachByName(StringToLower::class);

        foreach ($ymlContent as $sprykerFeatureName => $sprykerFeatureConfiguration) {
            if ($requestedSprykerFeatureName && $requestedSprykerFeatureName !== $sprykerFeatureName) {
                continue;
            }

            $pathToSprykerFeatureConfiguration = APPLICATION_ROOT_DIR . '/' . $sprykerFeatureConfiguration['url'];

            if (!file_exists($pathToSprykerFeatureConfiguration)) {
                throw new FeatureConfigurationNotFoundException(sprintf(
                    'Feature configuration file not found: "%s" for feature "%s".',
                    $pathToSprykerFeatureConfiguration,
                    $sprykerFeatureName,
                ));
            }

            $pathToSprykerFeatureResources = dirname($pathToSprykerFeatureConfiguration);
            $sprykerFeatureRootConfiguration = $ymlReader->parseFile($pathToSprykerFeatureConfiguration);

            $sprykerFeatureEntities = $sprykerFeatureRootConfiguration['entities'];
            unset($sprykerFeatureRootConfiguration['entities']);

            foreach ($sprykerFeatureEntities as $entityName) {
                $pathToSprykerFeatureEntityConfiguration = sprintf(
                    '%s/entity/%s.yml',
                    $pathToSprykerFeatureResources,
                    $entityNameFilter->filter($entityName),
                );

                if (!file_exists($pathToSprykerFeatureEntityConfiguration)) {
                    throw new EntityConfigurationNotFoundException(sprintf(
                        'Entity configuration file not found: "%s" for entity "%s" in feature "%s".',
                        $pathToSprykerFeatureEntityConfiguration,
                        $entityName,
                        $sprykerFeatureName,
                    ));
                }

                $sprykerFeatureEntityConfiguration = $ymlReader->parseFile($pathToSprykerFeatureEntityConfiguration);

                // Transform entity config and apply UI transformation
                $transformedConfig = $this->featureConfigTransformer->transform($sprykerFeatureEntityConfiguration);
                $sprykerFeatureRootConfiguration['entities'][$entityName] = $this->composableBackofficeUiFacade->mapEntityConfig($transformedConfig);
            }

            $sprykerFeatureTransfer = new SprykerFeatureTransfer();
            $sprykerFeatureTransfer
                ->setSprykerFeatureName($sprykerFeatureName)
                ->setConfiguration($sprykerFeatureRootConfiguration);

            $sprykerFeatureCollectionTransfer->addSprykerFeature($sprykerFeatureTransfer);
        }

        return $sprykerFeatureCollectionTransfer;
    }
}
