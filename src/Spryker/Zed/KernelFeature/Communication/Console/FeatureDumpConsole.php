<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Zed\KernelFeature\Communication\Console;

use Generated\Shared\Transfer\SprykerFeatureCriteriaTransfer;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Spryker\Zed\KernelFeature\Business\KernelFeatureFacadeInterface getFacade()
 */
class FeatureDumpConsole extends Console
{
    protected const COMMAND_NAME = 'feature:dump';

    protected const DESCRIPTION = 'Dumps SprykerFeature YAML configuration as JSON. Reads from .spryker/features.yml registry and outputs the parsed configuration for debugging and inspection.';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME);
        $this->setDescription(static::DESCRIPTION);
        $this->addArgument('featureName', InputArgument::REQUIRED, 'The name of the feature to dump');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $featureName = $input->getArgument('featureName');
        $output->writeln(sprintf('<info>Dumping feature: %s</info>', $featureName));

        $criteriaTransfer = new SprykerFeatureCriteriaTransfer();
        $criteriaTransfer->setSprykerFeatureName($featureName);

        $collection = $this->getFacade()->getFeatureCollection($criteriaTransfer);

        if ($collection->getSprykerFeatures()->count() === 0) {
            $output->writeln(sprintf('<error>Feature "%s" not found.</error>', $featureName));

            return static::CODE_ERROR;
        }

        $featureTransfer = $collection->getSprykerFeatures()->getIterator()->current();

        $output->writeln((string)json_encode([
            'feature' => $featureTransfer->getSprykerFeatureName(),
            'configuration' => $featureTransfer->getConfiguration(),
        ], JSON_PRETTY_PRINT));

        return static::CODE_SUCCESS;
    }
}
