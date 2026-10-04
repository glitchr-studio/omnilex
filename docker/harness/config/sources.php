<?php

/**
 * The sources, their options from the environment (.env): a source is
 * configured only when every key under "needs" is set.
 *
 * @return array<string, array{factory: string, needs: list<string>, options: array<string, mixed>}>
 */
$env = static fn (string $key, mixed $default = null): mixed => (false !== ($v = getenv($key)) && '' !== $v) ? $v : $default;
$piste = ['client_id' => $env('PISTE_CLIENT_ID'), 'client_secret' => $env('PISTE_CLIENT_SECRET'), 'sandbox' => (bool) $env('PISTE_SANDBOX', false)];

return [
    'legifrance' => ['factory' => 'legifrance', 'needs' => ['PISTE_CLIENT_ID', 'PISTE_CLIENT_SECRET'], 'options' => $piste],
    'judilibre' => ['factory' => 'judilibre', 'needs' => ['PISTE_CLIENT_ID', 'PISTE_CLIENT_SECRET'], 'options' => $piste],
    'eurlex' => ['factory' => 'eurlex', 'needs' => [], 'options' => ['language' => $env('EURLEX_LANGUAGE', 'fr')]],
    'administratif' => ['factory' => 'justice-administrative', 'needs' => [], 'options' => []],
];
