<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\KernelFeature\Api\Backend\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Generated\Shared\Transfer\SprykerFeatureCriteriaTransfer;
use Spryker\Zed\KernelFeature\Business\KernelFeatureFacadeInterface;

/**
 * @implements \ApiPlatform\State\ProviderInterface<object>
 */
class SprykerFeaturesBackendProvider implements ProviderInterface
{
    public function __construct(
        protected readonly KernelFeatureFacadeInterface $kernelFeatureFacade,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|iterable|null
    {
        $identifier = $uriVariables['sprykerFeatureName'] ?? null;

        if ($identifier === null) {
            return $this->provideCollection();
        }

        return $this->provideItem($identifier);
    }

    protected function provideItem(string $identifier): ?object
    {
        $criteriaTransfer = new SprykerFeatureCriteriaTransfer();
        $criteriaTransfer->setSprykerFeatureName($identifier);

        $collection = $this->kernelFeatureFacade->getFeatureCollection($criteriaTransfer);
        $features = $collection->getSprykerFeatures();

        if ($features->count() === 0) {
            return null;
        }

        return $this->mapTransferToObject($features->getIterator()->current());
    }

    protected function provideCollection(): array
    {
        $criteriaTransfer = new SprykerFeatureCriteriaTransfer();
        $collection = $this->kernelFeatureFacade->getFeatureCollection($criteriaTransfer);

        $features = [];
        foreach ($collection->getSprykerFeatures() as $featureTransfer) {
            $features[] = $this->mapTransferToObject($featureTransfer);
        }

        return $features;
    }

    /**
     * @param \Spryker\Shared\Kernel\Transfer\AbstractTransfer $transfer
     *
     * @return object
     */
    protected function mapTransferToObject($transfer): object
    {
        $data = $transfer->toArray();

        return (object)[
            'sprykerFeatureName' => $data['spryker_feature_name'] ?? $data['sprykerFeatureName'] ?? null,
            'configuration' => $data['configuration'] ?? [],
        ];
    }
}
