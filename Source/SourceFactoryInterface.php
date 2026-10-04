<?php

namespace Omnilex\Source;

/** Builds a source from its options (credentials, a language, a throttle...). */
interface SourceFactoryInterface
{
    /** The name sources are configured with: "legifrance", "eurlex"... */
    public function getName(): string;

    /** @param array<string, mixed> $options */
    public function create(array $options = []): SourceInterface;
}
