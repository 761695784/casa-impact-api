<?php

use App\Services\ApplicationReferenceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it("génère une référence au format CI-{année}-{séquence sur 6 chiffres}", function () {
    $reference = app(ApplicationReferenceGenerator::class)->generate();

    expect($reference)->toMatch('/^CI-'.date('Y').'-\d{6}$/');
});

it("génère des références séquentielles et uniques", function () {
    $generator = app(ApplicationReferenceGenerator::class);

    $first = $generator->generate();
    $second = $generator->generate();
    $third = $generator->generate();

    expect([$first, $second, $third])->toEqual(array_unique([$first, $second, $third]));

    preg_match('/(\d{6})$/', $first, $m1);
    preg_match('/(\d{6})$/', $second, $m2);
    preg_match('/(\d{6})$/', $third, $m3);

    expect((int) $m2[1])->toBe((int) $m1[1] + 1);
    expect((int) $m3[1])->toBe((int) $m2[1] + 1);
});
