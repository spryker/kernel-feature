<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\KernelFeature\Security\Validator;

use Spryker\Glue\KernelFeature\Security\Context\SecurityContext;
use Symfony\Component\HttpFoundation\Request;

class SecurityValidatorChain
{
    /**
     * @param iterable<\Spryker\Glue\KernelFeature\Security\Validator\SecurityValidatorInterface> $validators
     */
    public function __construct(protected readonly iterable $validators)
    {
    }

    public function validate(Request $request, ?string $resourceName): ValidationResult
    {
        $context = new SecurityContext();
        $context->setResourceName($resourceName);

        foreach ($this->validators as $validator) {
            $result = $validator->validate($request, $context);

            if (!$result->isValid()) {
                return $result;
            }
        }

        return ValidationResult::success();
    }
}
