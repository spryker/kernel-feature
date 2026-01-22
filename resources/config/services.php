<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Spryker\Zed\ComposableBackofficeUi\Business\ComposableBackofficeUiFacade;
use Spryker\Zed\ComposableBackofficeUi\Business\ComposableBackofficeUiFacadeInterface;
use Spryker\Zed\KernelFeature\Business\Cache\SprykerFeatureCache;
use Spryker\Zed\KernelFeature\Business\KernelFeatureFacade;
use Spryker\Zed\KernelFeature\Business\KernelFeatureFacadeInterface;
use Spryker\Zed\KernelFeature\Business\SprykerFeature\SprykerFeatureReader;
use Spryker\Zed\KernelFeature\Business\Transformer\SprykerFeatureEntityConfigTransformer;
use Spryker\Zed\KernelFeature\Business\Validator\FeatureValidator;
use Spryker\Zed\KernelFeature\KernelFeatureConfig;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Config
    $services->set(KernelFeatureConfig::class, KernelFeatureConfig::class)
        ->public();

    // KernelFeature Facade
    $services->set(KernelFeatureFacadeInterface::class, KernelFeatureFacade::class)
        ->public();

    // ComposableBackofficeUi Facade dependency
    $services->set(ComposableBackofficeUiFacadeInterface::class, ComposableBackofficeUiFacade::class)
        ->public();

    // Transformer
    $services->set(SprykerFeatureEntityConfigTransformer::class, SprykerFeatureEntityConfigTransformer::class);

    // Cache
    $services->set(SprykerFeatureCache::class, SprykerFeatureCache::class);

    // Reader
    $services->set(SprykerFeatureReader::class, SprykerFeatureReader::class)
        ->public();

    // Validator
    $services->set(FeatureValidator::class, FeatureValidator::class)
        ->public();
};
