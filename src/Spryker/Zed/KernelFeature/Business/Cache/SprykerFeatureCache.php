<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Zed\KernelFeature\Business\Cache;

use Generated\Shared\Transfer\SprykerFeatureCollectionTransfer;
use Generated\Shared\Transfer\SprykerFeatureTransfer;
use Spryker\Zed\KernelFeature\KernelFeatureConfig;

class SprykerFeatureCache
{
    public function __construct(private KernelFeatureConfig $config)
    {
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function load(string $cacheKey): ?array
    {
        if (!$this->config->isCacheEnabled()) {
            return null;
        }

        $cacheFile = $this->getCacheFilePath($cacheKey);

        if (!file_exists($cacheFile)) {
            return null;
        }

        return include $cacheFile;
    }

    public function save(string $cacheKey, SprykerFeatureCollectionTransfer $collection): void
    {
        if (!$this->config->isCacheEnabled()) {
            return;
        }

        $cacheFile = $this->getCacheFilePath($cacheKey);
        $cacheDir = dirname($cacheFile);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $data = [];
        foreach ($collection->getSprykerFeatures() as $feature) {
            $data[] = [
                'name' => $feature->getSprykerFeatureName(),
                'configuration' => $feature->getConfiguration(),
            ];
        }

        $content = '<?php return ' . var_export($data, true) . ';';
        file_put_contents($cacheFile, $content);
    }

    /**
     * @param array<int, array<string, mixed>> $cachedData
     */
    public function hydrate(array $cachedData): SprykerFeatureCollectionTransfer
    {
        $collection = new SprykerFeatureCollectionTransfer();

        foreach ($cachedData as $featureData) {
            $transfer = new SprykerFeatureTransfer();
            $transfer
                ->setSprykerFeatureName($featureData['name'])
                ->setConfiguration($featureData['configuration']);

            $collection->addSprykerFeature($transfer);
        }

        return $collection;
    }

    protected function getCacheFilePath(string $cacheKey): string
    {
        return $this->config->getCacheDirectory() . '/spryker_features_' . md5($cacheKey) . '.php';
    }
}
