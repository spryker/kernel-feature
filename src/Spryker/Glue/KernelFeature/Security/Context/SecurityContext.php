<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\KernelFeature\Security\Context;

class SecurityContext
{
    protected ?int $userId = null;

    protected ?string $resourceName = null;

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function getResourceName(): ?string
    {
        return $this->resourceName;
    }

    public function setResourceName(?string $resourceName): void
    {
        $this->resourceName = $resourceName;
    }
}
