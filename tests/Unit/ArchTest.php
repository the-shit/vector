<?php

declare(strict_types=1);

arch('data objects are readonly')
    ->expect('TheShit\Vector\Data')
    ->toBeReadonly();

arch('contracts are interfaces')
    ->expect('TheShit\Vector\Contracts')
    ->toBeInterfaces();

arch('requests extend Saloon Request')
    ->expect('TheShit\Vector\Requests')
    ->toExtend('Saloon\Http\Request');

arch('no debugging statements')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('strict types everywhere')
    ->expect('TheShit\Vector')
    ->toUseStrictTypes();
