<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\KernelFeature\Security;

use Generated\Shared\Transfer\SprykerFeatureCriteriaTransfer;
use Generated\Shared\Transfer\UserTransfer;
use Spryker\Zed\Acl\Business\AclFacadeInterface;
use Spryker\Zed\KernelFeature\Business\KernelFeatureFacadeInterface;
use Throwable;

class AclAccessChecker implements AclAccessCheckerInterface
{
    /**
     * @var array<string, array<string, string>>|null
     */
    protected ?array $aclMappings = null;

    protected bool $mappingsLoadFailed = false;

    public function __construct(
        protected readonly AclFacadeInterface $aclFacade,
        protected readonly KernelFeatureFacadeInterface $kernelFeatureFacade,
    ) {
    }

    public function hasAccess(int $idUser, string $resourceName): bool
    {
        if ($this->mappingsLoadFailed) {
            return false;
        }

        $aclMapping = $this->getAclMapping($resourceName);
        if ($aclMapping === null) {
            return true;
        }

        try {
            $userTransfer = (new UserTransfer())->setIdUser($idUser);

            return $this->aclFacade->checkAccess(
                $userTransfer,
                $aclMapping['module'],
                $aclMapping['controller'],
                $aclMapping['action'],
            );
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @return array<string, string>|null
     */
    protected function getAclMapping(string $resourceName): ?array
    {
        if ($this->aclMappings === null) {
            $this->aclMappings = $this->loadAclMappingsFromRouter();
        }

        return $this->aclMappings[$resourceName] ?? null;
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function loadAclMappingsFromRouter(): array
    {
        $mappings = [];

        try {
            $criteriaTransfer = new SprykerFeatureCriteriaTransfer();
            $featureCollection = $this->kernelFeatureFacade->getFeatureCollection($criteriaTransfer);

            foreach ($featureCollection->getSprykerFeatures() as $feature) {
                $config = $feature->getConfiguration();
                if (!$config) {
                    continue;
                }

                /** @var string $featureName */
                $featureName = $feature->getSprykerFeatureName();

                $featurePath = $this->toKebabCase($featureName);
                $entities = $config['entities'] ?? [];

                foreach ($entities as $entityName => $entityConfig) {
                    if (!is_string($entityName)) {
                        $entityName = is_string($entityConfig) ? $entityConfig : ($entityConfig['entity'] ?? null);
                    }

                    if ($entityName === null) {
                        continue;
                    }

                    $entityPath = $this->toKebabCase($entityName);

                    $mappings[$entityPath] = [
                        'module' => $featurePath,
                        'controller' => $entityPath,
                        'action' => 'index',
                    ];
                }
            }
        } catch (Throwable $e) {
            $this->mappingsLoadFailed = true;
        }

        return $mappings;
    }

    protected function toKebabCase(string $string): string
    {
        return strtolower((string)preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $string));
    }
}
