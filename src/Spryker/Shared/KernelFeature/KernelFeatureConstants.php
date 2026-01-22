<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\KernelFeature;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface KernelFeatureConstants
{
    /**
     * Specification:
     * - Enables or disables caching of Spryker Feature definitions.
     * - Set to true to enable caching (recommended for production).
     * - Set to false to disable caching (recommended for development).
     *
     * @api
     *
     * @var string
     */
    public const SPRYKER_FEATURE_CACHE_ENABLED = 'KERNEL_FEATURE:SPRYKER_FEATURE_CACHE_ENABLED';
}
