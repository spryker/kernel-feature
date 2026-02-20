<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\KernelFeature\Security\Listener;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Spryker\Glue\KernelFeature\Security\Validator\SecurityValidatorChain;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

/**
 * Validates authentication for API Platform requests before they reach the controller.
 */
class AuthenticationRequestListener
{
    protected const string ROUTE_API_RESOURCE_CLASS = '_api_resource_class';

    public function __construct(
        protected readonly SecurityValidatorChain $securityValidatorChain,
        protected readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $resourceClass = $request->attributes->get(static::ROUTE_API_RESOURCE_CLASS);

        if ($resourceClass === null) {
            return;
        }

        $security = $this->getSecurityFromResource($resourceClass);

        if ($security === null || $security === '') {
            return;
        }

        $resourceShortName = $this->getShortNameFromResource($resourceClass);
        $validationResult = $this->securityValidatorChain->validate($request, $resourceShortName);

        if ($validationResult->isValid()) {
            return;
        }

        $errorCode = $validationResult->getErrorCode() ?? 401;
        $errorMessage = $validationResult->getErrorMessage() ?? 'Unauthorized';

        if ($errorCode === 403) {
            throw new AccessDeniedHttpException($errorMessage);
        }

        throw new UnauthorizedHttpException('Bearer', $errorMessage);
    }

    protected function getSecurityFromResource(string $resourceClass): ?string
    {
        try {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);

            foreach ($resourceMetadataCollection as $resourceMetadata) {
                $security = $resourceMetadata->getSecurity();
                if ($security !== null && $security !== '') {
                    return $security;
                }
            }
        } catch (Throwable) {
        }

        return null;
    }

    protected function getShortNameFromResource(string $resourceClass): ?string
    {
        try {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);

            foreach ($resourceMetadataCollection as $resourceMetadata) {
                return $resourceMetadata->getShortName();
            }
        } catch (Throwable) {
        }

        return null;
    }
}
