<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\KernelFeature\Security\Validator;

class ValidationResult
{
    protected function __construct(
        protected readonly bool $isValid,
        protected readonly ?int $errorCode = null,
        protected readonly ?string $errorMessage = null,
    ) {
    }

    public static function success(): self
    {
        return new self(true);
    }

    public static function error(int $code, string $message): self
    {
        return new self(false, $code, $message);
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
