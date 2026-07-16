<?php

declare(strict_types=1);

namespace BC\Controller\System;

use ApiPlatform\DTO\ApiResponseDTO;
use Runway\Request\IResponse;
use Runway\Request\Parameters\DTO\CookieDTO;

class APIResponseController extends \ApiPlatform\Controller\System\APIResponseController {
    public function getResponse(ApiResponseDTO $response): IResponse {
        $result = parent::getResponse($response);

        if ($this->isSuccessFulResponse($response)) {
            if ($this->isAuthRequest()) {
                if ($cookie = $this->getTokenCookie($this->getResponseData($response))) {
                    $result->addCookie($cookie);
                }
            } elseif ($this->isLogoffRequest()) {
                $result->addCookie($this->getExpiredTokenCookie());
            }
        }

        return $result;
    }

    protected function isAuthRequest(): bool {
        return $this->request->getMethod() === 'POST'
               && $this->isRequestPathEndingWith('/auth');
    }

    protected function isLogoffRequest(): bool {
        return $this->request->getMethod() === 'GET'
               && $this->isRequestPathEndingWith('/logoff');
    }

    protected function isRequestPathEndingWith(string $suffix): bool {
        return str_ends_with(rtrim($this->request->getPath(), '/'), $suffix);
    }

    protected function getTokenCookie(array $responseData): ?CookieDTO {
        $token = (string)($responseData['token'] ?? '');
        $validUntil = (int)strtotime((string)($responseData['valid_until'] ?? ''));

        if ($token === '' || $validUntil <= 0) {
            return null;
        }

        return $this->getCookie($token, $validUntil);
    }

    protected function getExpiredTokenCookie(): CookieDTO {
        return $this->getCookie('', time() - 3600);
    }

    protected function getCookie(string $token, int $expires): CookieDTO {
        return new CookieDTO(
            name: 'token',
            value: $token,
            expires: $expires,
            path: '/',
            domain: '',
            isSecure: $this->request->getProtocol() === 'https',
            isHttponly: true
        );
    }
}
