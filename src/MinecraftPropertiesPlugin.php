<?php

namespace Pelican\MinecraftProperties;

use Filament\Contracts\Plugin;
use Filament\Panel;

class MinecraftPropertiesPlugin implements Plugin
{
    public function getId(): string
    {
        return 'minecraft-properties';
    }

    public function register(Panel $panel): void
    {
        $id = str($panel->getId())->title();
        $pagesPath = plugin_path($this->getId(), "src/Filament/$id/Pages");

        if (is_dir($pagesPath)) {
            $panel->discoverPages($pagesPath, "Pelican\\MinecraftProperties\\Filament\\$id\\Pages");
        }
    }

    public function boot(Panel $panel): void {}
}
