<x-filament-panels::page id="form" :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()" wire:submit="save">
    <div class="fi-page-header-main-ctn">
        <div class="fi-page-header-main">
            <div>
                <h1 class="text-2xl font-semibold">Minecraft Server Properties</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Edit the server.properties file via a friendly form or the raw editor.</p>
            </div>
            
        </div>
    </div>

    <div class="mt-6">
        {{ $this->form }}
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
