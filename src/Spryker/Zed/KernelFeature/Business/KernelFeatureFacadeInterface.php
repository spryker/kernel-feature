<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\KernelFeature\Business;

use Generated\Shared\Transfer\SprykerFeatureCollectionTransfer;
use Generated\Shared\Transfer\SprykerFeatureCriteriaTransfer;
use Generated\Shared\Transfer\SprykerFeatureValidationResultTransfer;

interface KernelFeatureFacadeInterface
{
    /**
     * Specification:
     * - Returns a collection of features based on the provided criteria.
     *
     * @api
     */
    public function getFeatureCollection(SprykerFeatureCriteriaTransfer $sprykerFeatureCriteriaTransfer): SprykerFeatureCollectionTransfer;

    /**
     * Specification:
     * - Validates feature configuration structure and references.
     *
     * @api
     */
    public function validateFeature(SprykerFeatureCriteriaTransfer $sprykerFeatureCriteriaTransfer): SprykerFeatureValidationResultTransfer;
}
