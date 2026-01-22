<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\KernelFeature;

use Spryker\KernelFeature\DependencyInjection\Compiler\KernelFeatureServicesCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * KernelFeature Bundle
 *
 * Provides feature management and dynamic routing from YAML configuration.
 */
class SprykerKernelFeatureBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(
            new KernelFeatureServicesCompilerPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            50,
        );
    }
}
