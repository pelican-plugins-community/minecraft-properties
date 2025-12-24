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

        $resourcesPath = plugin_path($this->getId(), "src/Filament/$id/Resources");
        $pagesPath = plugin_path($this->getId(), "src/Filament/$id/Pages");
        $widgetsPath = plugin_path($this->getId(), "src/Filament/$id/Widgets");

        if (is_dir($resourcesPath)) {
            $panel->discoverResources($resourcesPath, "Pelican\\MinecraftProperties\\Filament\\$id\\Resources");
        }

        if (is_dir($pagesPath)) {
            $panel->discoverPages($pagesPath, "Pelican\\MinecraftProperties\\Filament\\$id\\Pages");
        }

        if (is_dir($widgetsPath)) {
            $panel->discoverWidgets($widgetsPath, "Pelican\\MinecraftProperties\\Filament\\$id\\Widgets");
        }
    }

    public function boot(Panel $panel): void {}
}
