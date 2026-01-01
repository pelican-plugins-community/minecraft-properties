<?php

namespace Pelican\MinecraftProperties;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Pelican\MinecraftProperties\Filament\Server\Pages\ServerProperties;

class MinecraftPropertiesPlugin implements Plugin
{
    public function getId(): string
    {
        return 'minecraft-properties';
    }

    public function register(Panel $panel): void
    {

        if ($panel->getId() === 'server') {
            $panel->pages([
                ServerProperties::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {}
}
