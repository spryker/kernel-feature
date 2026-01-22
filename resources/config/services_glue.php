<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Spryker\Glue\KernelFeature\Security\AclAccessChecker;
use Spryker\Glue\KernelFeature\Security\AclAccessCheckerInterface;
use Spryker\Glue\KernelFeature\Security\AuthenticatedProvider;
use Spryker\Glue\KernelFeature\Security\Validator\AclValidator;
use Spryker\Glue\KernelFeature\Security\Validator\BearerTokenValidator;
use Spryker\Glue\KernelFeature\Security\Validator\SecurityValidatorChain;
use Spryker\Glue\KernelFeature\Security\Validator\SecurityValidatorInterface;
use Spryker\Zed\Acl\Business\AclFacade;
use Spryker\Zed\Acl\Business\AclFacadeInterface;
use Spryker\Zed\Oauth\Business\OauthFacade;
use Spryker\Zed\Oauth\Business\OauthFacadeInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Oauth Facade for token validation
    $services->set(OauthFacadeInterface::class, OauthFacade::class)
        ->public();

    // ACL Facade for access control
    $services->set(AclFacadeInterface::class, AclFacade::class)
        ->public();

    // ACL Access Checker
    $services->set(AclAccessCheckerInterface::class, AclAccessChecker::class);

    // Tag all security validators
    $services->instanceof(SecurityValidatorInterface::class)
        ->tag('kernel_feature.security_validator');

    // Security validators
    $services->set(BearerTokenValidator::class, BearerTokenValidator::class);
    $services->set(AclValidator::class, AclValidator::class);

    // Security validator chain
    $services->set(SecurityValidatorChain::class, SecurityValidatorChain::class)
        ->arg('$validators', tagged_iterator('kernel_feature.security_validator'));

    // AuthenticatedProvider - decorates API Platform state provider
    $services->set(AuthenticatedProvider::class, AuthenticatedProvider::class)
        ->decorate('api_platform.state_provider')
        ->arg('$decorated', service('.inner'))
        ->public();
};
