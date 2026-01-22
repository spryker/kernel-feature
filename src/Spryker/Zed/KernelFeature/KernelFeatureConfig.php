<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Zed\KernelFeature;

use Spryker\Shared\KernelFeature\KernelFeatureConstants;
use Spryker\Zed\Kernel\AbstractBundleConfig;

class KernelFeatureConfig extends AbstractBundleConfig
{
    /**
     * @api
     */
    public function getCacheDirectory(): string
    {
        return APPLICATION_ROOT_DIR . '/data/cache/spryker_features';
    }

    /**
     * @api
     */
    public function isCacheEnabled(): bool
    {
        return $this->get(KernelFeatureConstants::SPRYKER_FEATURE_CACHE_ENABLED, false);
    }
}
