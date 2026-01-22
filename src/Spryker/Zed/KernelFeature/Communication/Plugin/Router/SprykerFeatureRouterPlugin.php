<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Zed\KernelFeature\Communication\Plugin\Router;

use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\RouterExtension\Dependency\Plugin\RouterPluginInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @method \Spryker\Zed\KernelFeature\Business\KernelFeatureFacadeInterface getFacade()
 * @method \Spryker\Zed\KernelFeature\KernelFeatureConfig getConfig()
 * @method \Spryker\Zed\KernelFeature\Communication\KernelFeatureCommunicationFactory getFactory()
 */
class SprykerFeatureRouterPlugin extends AbstractPlugin implements RouterPluginInterface
{
    /**
     * {@inheritDoc}
     * - Returns a Router that generates routes for SprykerFeature entities.
     *
     * @api
     *
     * @return \Symfony\Component\Routing\RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->getFactory()->createSprykerFeatureRouter();
    }
}
