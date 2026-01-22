<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\KernelFeature\Security;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Spryker\Glue\KernelFeature\Security\Validator\SecurityValidatorChain;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Decorator that validates security (token, ACL) before delegating to the actual provider.
 *
 * @implements \ApiPlatform\State\ProviderInterface<object>
 */
class AuthenticatedProvider implements ProviderInterface
{
    /**
     * @param \ApiPlatform\State\ProviderInterface<object> $decorated
     */
    public function __construct(
        protected readonly ProviderInterface $decorated,
        protected readonly RequestStack $requestStack,
        protected readonly SecurityValidatorChain $securityValidatorChain,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $security = $operation->getSecurity();
        if ($security === null || $security === '') {
            return $this->decorated->provide($operation, $uriVariables, $context);
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new UnauthorizedHttpException('Bearer', 'No request available');
        }

        $resourceShortName = $operation->getShortName();
        $validationResult = $this->securityValidatorChain->validate($request, $resourceShortName);

        if (!$validationResult->isValid()) {
            $this->throwSecurityException($validationResult->getErrorCode(), $validationResult->getErrorMessage());
        }

        return $this->decorated->provide($operation, $uriVariables, $context);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException|\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    protected function throwSecurityException(?int $code, ?string $message): never
    {
        $message = $message ?? 'Access denied';

        if ($code === 403) {
            throw new AccessDeniedHttpException($message);
        }

        throw new UnauthorizedHttpException('Bearer', $message);
    }
}
