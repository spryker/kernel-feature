<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Zed\KernelFeature\Communication;

use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\KernelFeature\Communication\Router\SprykerFeatureRouter;
use Symfony\Component\Routing\RouterInterface;

/**
 * @method \Spryker\Zed\KernelFeature\Business\KernelFeatureFacadeInterface getFacade()
 * @method \Spryker\Zed\KernelFeature\KernelFeatureConfig getConfig()
 */
class KernelFeatureCommunicationFactory extends AbstractCommunicationFactory
{
    /**
     * @return \Symfony\Component\Routing\RouterInterface
     */
    public function createSprykerFeatureRouter(): RouterInterface
    {
        return new SprykerFeatureRouter(
            $this->getFacade(),
        );
    }
}
