<?php

use Wafto\Sepomex\Models\Sepomex;

it('runs the import command successfully', function () {
    $this->artisan('sepomex:import', ['--chunk' => '50'])
        ->expectsOutput('Parsing [403] rows from file...')
        ->expectsOutput("Imported [403] rows from [403] file lines in sepomex table.\n")
        ->expectsOutput('Cache cleared.')
        ->assertExitCode(0);

    expect(Sepomex::query()->count())->toBe(403)
        ->and(Sepomex::query()->whereNull('settlement_key')->count())->toBe(0);
});

it('shows error when source file is missing', function () {
    config(['sepomex.source_file' => 'foo.txt']);

    $this->artisan('sepomex:import', ['--chunk' => '50'])
        ->expectsOutput('No source file found on foo.txt, please make sure to download it.')
        ->assertExitCode(1);
});

it('shows error when the header is invalid', function () {
    config(['sepomex.source_file' => __DIR__.'/files/invalid_header.txt']);

    $this->artisan('sepomex:import')
        ->expectsOutput('SEPOMEX source file has an invalid header.')
        ->assertExitCode(1);
});
