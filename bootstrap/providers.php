<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Liar88828\LaravelSchemaAttributes\SchemaServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    SchemaServiceProvider::class,
];
