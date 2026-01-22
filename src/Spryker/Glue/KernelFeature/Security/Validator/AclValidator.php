<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\KernelFeature\Security\Validator;

use Spryker\Glue\KernelFeature\Security\AclAccessCheckerInterface;
use Spryker\Glue\KernelFeature\Security\Context\SecurityContext;
use Symfony\Component\HttpFoundation\Request;

class AclValidator implements SecurityValidatorInterface
{
    public function __construct(
        protected readonly AclAccessCheckerInterface $aclAccessChecker,
    ) {
    }

    public function validate(Request $request, SecurityContext $context): ValidationResult
    {
        $userId = $context->getUserId();
        if ($userId === null) {
            return ValidationResult::success();
        }

        $resourceName = $context->getResourceName();
        if ($resourceName === null) {
            return ValidationResult::success();
        }

        if (!$this->aclAccessChecker->hasAccess($userId, $resourceName)) {
            return ValidationResult::error(403, 'Access denied by ACL rules');
        }

        return ValidationResult::success();
    }
}
