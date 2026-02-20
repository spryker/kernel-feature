<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace Spryker\Glue\KernelFeature\Security\Validator;

use Generated\Shared\Transfer\OauthAccessTokenValidationRequestTransfer;
use Spryker\Glue\KernelFeature\Security\Context\SecurityContext;
use Spryker\Zed\Oauth\Business\OauthFacadeInterface;
use Symfony\Component\HttpFoundation\Request;

class BearerTokenValidator implements SecurityValidatorInterface
{
    protected const string HEADER_AUTHORIZATION = 'Authorization';

    protected const string TOKEN_TYPE_BEARER = 'Bearer';

    public function __construct(
        protected readonly OauthFacadeInterface $oauthFacade,
    ) {
    }

    public function validate(Request $request, SecurityContext $context): ValidationResult
    {
        $authorizationHeader = $request->headers->get(static::HEADER_AUTHORIZATION);
        if ($authorizationHeader === null) {
            return ValidationResult::error(401, 'Authorization header is required');
        }

        $tokenParts = explode(' ', $authorizationHeader, 2);
        if (count($tokenParts) !== 2 || $tokenParts[0] !== static::TOKEN_TYPE_BEARER) {
            return ValidationResult::error(401, 'Invalid Authorization header format');
        }

        $accessToken = $tokenParts[1];

        $validationRequest = (new OauthAccessTokenValidationRequestTransfer())
            ->setType(static::TOKEN_TYPE_BEARER)
            ->setAccessToken($accessToken);

        $validationResponse = $this->oauthFacade->validateAccessToken($validationRequest);

        if (!$validationResponse->getIsValid()) {
            return ValidationResult::error(401, 'Invalid access token');
        }

        $oauthUserId = $validationResponse->getOauthUserId();
        if ($oauthUserId !== null) {
            $userData = json_decode($oauthUserId, true);
            if (is_array($userData) && isset($userData['id_user'])) {
                $context->setUserId($userData['id_user']);
            }
        }

        return ValidationResult::success();
    }
}
