<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\KernelFeature\DependencyInjection\Compiler;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Registers KernelFeature services from services.php configuration.
 * Conditionally loads security services for GlueBackend (API Platform) context.
 */
class KernelFeatureServicesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../../../../resources/config'));
        $loader->load('services.php');

        // Load security services only if API Platform is available (GlueBackend context)
        if ($container->has('api_platform.state_provider')) {
            $loader->load('services_glue.php');
        }
    }
}
