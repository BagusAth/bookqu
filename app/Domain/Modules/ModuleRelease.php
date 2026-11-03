<?php

namespace App\Domain\Modules;

final class ModuleRelease
{
    public static function state(string $module): string
    {
        $state = config('modules.'.$module, 'hidden');

        return in_array($state, ['released', 'locked', 'hidden'], true) ? $state : 'hidden';
    }
}
