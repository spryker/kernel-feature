<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\KernelFeature\Business;

use Generated\Shared\Transfer\SprykerFeatureCollectionTransfer;
use Generated\Shared\Transfer\SprykerFeatureCriteriaTransfer;
use Generated\Shared\Transfer\SprykerFeatureValidationResultTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;
use Spryker\Zed\KernelFeature\Business\SprykerFeature\SprykerFeatureReader;
use Spryker\Zed\KernelFeature\Business\Validator\FeatureValidator;

class KernelFeatureFacade extends AbstractFacade implements KernelFeatureFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getFeatureCollection(SprykerFeatureCriteriaTransfer $sprykerFeatureCriteriaTransfer): SprykerFeatureCollectionTransfer
    {
        /** @var \Spryker\Zed\KernelFeature\Business\SprykerFeature\SprykerFeatureReader $sprykerFeatureReader */
        $sprykerFeatureReader = $this->getService(SprykerFeatureReader::class);

        return $sprykerFeatureReader->findFeatures($sprykerFeatureCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function validateFeature(SprykerFeatureCriteriaTransfer $sprykerFeatureCriteriaTransfer): SprykerFeatureValidationResultTransfer
    {
        /** @var \Spryker\Zed\KernelFeature\Business\Validator\FeatureValidator $featureValidator */
        $featureValidator = $this->getService(FeatureValidator::class);

        return $featureValidator->validate($sprykerFeatureCriteriaTransfer);
    }
}
