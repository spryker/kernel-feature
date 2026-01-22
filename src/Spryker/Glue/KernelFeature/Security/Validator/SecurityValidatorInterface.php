<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\KernelFeature\Security\Validator;

use Spryker\Glue\KernelFeature\Security\Context\SecurityContext;
use Symfony\Component\HttpFoundation\Request;

interface SecurityValidatorInterface
{
    public function validate(Request $request, SecurityContext $context): ValidationResult;
}
