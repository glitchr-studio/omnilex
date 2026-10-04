<?php

namespace Omnilex\Exception;

/** The source's service answered with an error. */
class ProviderException extends \RuntimeException implements OmnilexException
{
    public function __construct(
        public readonly string $source,
        string $message,
        public readonly ?int $status = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(\sprintf('[%s] %s', $source, $message), 0, $previous);
    }
}
