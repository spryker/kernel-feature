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
class FeatureValidateConsole extends Console
{
    protected const COMMAND_NAME = 'feature:validate';

    protected const DESCRIPTION = 'Validates a feature configuration structure and references';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME);
        $this->setDescription(static::DESCRIPTION);
        $this->addArgument('featureName', InputArgument::REQUIRED, 'The name of the feature to validate');
        $this->addArgument('entityName', InputArgument::OPTIONAL, 'Optional: specific entity name to validate');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $featureName = $input->getArgument('featureName');
        $entityName = $input->getArgument('entityName');

        if ($entityName) {
            $output->writeln(sprintf('<info>Validating feature: %s, entity: %s</info>', $featureName, $entityName));
        } else {
            $output->writeln(sprintf('<info>Validating feature: %s</info>', $featureName));
        }

        $criteriaTransfer = new SprykerFeatureCriteriaTransfer();
        $criteriaTransfer->setSprykerFeatureName($featureName);

        if ($entityName) {
            $criteriaTransfer->setEntityName($entityName);
        }

        $result = $this->getFacade()->validateFeature($criteriaTransfer);

        if ($result->getIsValid()) {
            if ($entityName) {
                $output->writeln(sprintf('<info>Entity "%s" in feature "%s" is valid.</info>', $entityName, $featureName));
            } else {
                $output->writeln(sprintf('<info>Feature "%s" is valid.</info>', $featureName));
            }

            return static::CODE_SUCCESS;
        }

        $output->writeln(sprintf('<error>Feature "%s" validation failed:</error>', $featureName));

        foreach ($result->getErrors() as $error) {
            $output->writeln(sprintf('  <error>- %s</error>', $error));
        }

        return static::CODE_ERROR;
    }
}
