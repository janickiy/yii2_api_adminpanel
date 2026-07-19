<?php

declare(strict_types=1);

namespace frontend\modules\api\handlers;

use application\services\AuthService;
use domain\exceptions\AuthenticationException;
use domain\exceptions\ConflictException;
use domain\exceptions\PersistenceException;
use frontend\modules\api\http\ApiExceptionMapper;
use frontend\modules\api\http\ApiRequestContext;
use frontend\modules\api\http\ApiResponder;
use frontend\modules\api\http\input\LoginInput;
use frontend\modules\api\http\input\RegisterInput;
use frontend\modules\api\http\RequestInputFactory;

final readonly class AuthRequestHandler
{
    public function __construct(
        private AuthService $authService,
        private RequestInputFactory $requests,
        private ApiRequestContext $context,
        private ApiResponder $responder,
        private ApiExceptionMapper $exceptions,
    ) {
    }

    /** @return array{data: array<string, mixed>} */
    public function register(): array
    {
        $dto = $this->requests->fromBody(RegisterInput::class)->toDto();

        try {
            $user = $this->authService->register($dto);
        } catch (ConflictException $exception) {
            $this->exceptions->conflict($exception);
        } catch (PersistenceException $exception) {
            $this->exceptions->persistence($exception, 'Unable to create the user.');
        }

        return $this->responder->user($user, 201);
    }

    /** @return array{data: array{token: string, token_type: string, user: array<string, mixed>}} */
    public function login(): array
    {
        $dto = $this->requests->fromBody(LoginInput::class)->toDto();

        try {
            $result = $this->authService->login($dto);
        } catch (AuthenticationException $exception) {
            $this->exceptions->authentication($exception);
        }

        return $this->responder->authentication($result);
    }

    public function logout(): null
    {
        try {
            $this->authService->logout($this->context->bearerToken());
        } catch (AuthenticationException $exception) {
            $this->exceptions->authentication($exception);
        }

        return $this->responder->noContent();
    }
}
