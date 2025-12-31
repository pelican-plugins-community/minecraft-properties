<?php

namespace Pelican\MinecraftProperties;

use Filament\Contracts\Plugin;
use Filament\Panel;

class MinecraftPropertiesPlugin implements Plugin
{
    /**
     * Plugin identifier used by Pelican to discover resources and translations.
     *
     * Keeping this method simple ensures consistent plugin path resolution
     * across the panel registration and boot steps.
     *
     * @return string plugin id
     */
    public function getId(): string
    {
        return 'minecraft-properties';
    }

    public function register(Panel $panel): void
    {
        // Filament panels provide an id like "Server" or similar. Convert it
        // to the title-cased namespace segment used by this plugin.
        $id = str($panel->getId())->title();


        // Build discovery path for Pages relative to the plugin installation.
        $pagesPath = plugin_path($this->getId(), "src/Filament/$id/Pages");

        // Discover Filament pages if the plugin provides them for this panel.
        if (is_dir($pagesPath)) {
            $panel->discoverPages($pagesPath, "Pelican\\MinecraftProperties\\Filament\\$id\\Pages");
        }
    }

    public function boot(Panel $panel): void
    {
        try {
            $pluginLangPath = plugin_path($this->getId(), 'resources/lang');
            if (is_dir($pluginLangPath)) {
                // Register a translation namespace so the plugin can provide
                // its own translations under resources/lang/*.php
                app('translator')->addNamespace('minecraft-properties', $pluginLangPath);
            }
        } catch (\InvalidArgumentException $e) {
            // Namespace already registered
        }
    }
}
