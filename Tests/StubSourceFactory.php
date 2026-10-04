<?php

namespace Omnilex\Tests;

use Omnilex\Auth\Piste;
use Omnilex\Config;
use Omnilex\Source\SourceFactory;
use Omnilex\Source\SourceInterface;
use Symfony\Component\HttpClient\MockHttpClient;

/** Builds StubSources: options "name" (required), "client_id" and "client_secret" (then it signs in at PISTE's sandbox). */
final class StubSourceFactory extends SourceFactory
{
    protected function populate(Config $c): void
    {
        $c->defaults(['omnilex.factory_name' => 'stub', 'omnilex.required_options' => ['name']]);
    }

    protected function build(Config $c): SourceInterface
    {
        $http = $this->http ?? new MockHttpClient();
        $auth = null !== $c->get('client_id') ? Piste::credentials($http, (string) $c['client_id'], (string) $c->get('client_secret', ''), true, $this->tokens, (string) $c['name']) : null;

        return new StubSource((string) $c['name'], $http, $auth, (float) $c['throttle']);
    }
}
