<?php

namespace Omnilex\Model;

/** An access token and when it dies. */
final readonly class Token
{
    public function __construct(
        public string $accessToken,
        public ?\DateTimeImmutable $expiresAt = null,
        public string $type = 'Bearer',
        public ?string $scope = null,
    ) {
    }

    /** True once the token is dead, or will be within $leeway seconds. */
    public function isExpired(int $leeway = 0, ?\DateTimeImmutable $now = null): bool
    {
        return null !== $this->expiresAt && $this->expiresAt <= ($now ?? new \DateTimeImmutable())->modify("+$leeway seconds");
    }

    public function toArray(): array
    {
        return array_filter([
            'access_token' => $this->accessToken,
            'expires_at' => $this->expiresAt?->format(\DateTimeInterface::ATOM),
            'type' => $this->type,
            'scope' => $this->scope,
        ], static fn ($v) => null !== $v);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['access_token'],
            isset($data['expires_at']) ? new \DateTimeImmutable($data['expires_at']) : null,
            (string) ($data['type'] ?? 'Bearer'),
            $data['scope'] ?? null,
        );
    }
}
