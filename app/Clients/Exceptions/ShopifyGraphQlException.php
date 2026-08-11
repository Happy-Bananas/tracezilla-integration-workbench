<?php

namespace App\Clients\Exceptions;

use RuntimeException;

class ShopifyGraphQlException extends RuntimeException
{
    public function __construct(
        string $message,
        protected array $errors = [],
    ) {
        parent::__construct($message);
    }

    public static function fromErrors(array $errors): self
    {
        $messages = collect($errors)
            ->map(function ($error): string {
                if (is_array($error) && isset($error['message'])) {
                    return (string) $error['message'];
                }

                return is_scalar($error)
                    ? (string) $error
                    : 'Unknown GraphQL error';
            })
            ->take(3)
            ->implode('; ');

        return new self(
            "Shopify GraphQL request failed: {$messages}",
            $errors,
        );
    }

    public static function invalidResponse(): self
    {
        return new self('Shopify GraphQL returned an invalid JSON response.');
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
