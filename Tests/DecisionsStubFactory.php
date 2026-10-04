<?php

namespace Omnilex\Tests;

use Omnilex\Config;
use Omnilex\Source\SourceFactory;
use Omnilex\Source\SourceInterface;

final class DecisionsStubFactory extends SourceFactory
{
    protected function populate(Config $c): void
    {
        $c->defaults(['omnilex.factory_name' => 'decisions', 'omnilex.required_options' => []]);
    }

    protected function build(Config $c): SourceInterface
    {
        return new DecisionsStub();
    }
}
