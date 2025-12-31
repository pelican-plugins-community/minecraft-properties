<?php

namespace Pelican\MinecraftProperties\Filament\Server\Pages;

use App\Filament\Server\Pages\ServerFormPage;
use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Pelican\MinecraftProperties\Filament\Server\Pages\Concerns\PropertiesMapping;
use Pelican\MinecraftProperties\Filament\Server\Pages\Concerns\ServerPropertiesFields;

/**
 * Filament page for editing a server's `server.properties` file.
 *
 * This page loads the raw `server.properties` from the daemon via the
 * `DaemonFileRepository`, maps recognized properties to a Form schema and
 * allows editing either via form fields or the raw text area. On save the
 * file is reconstructed and written back to the daemon.
 */
final class ServerProperties extends ServerFormPage
{
    use PropertiesMapping, ServerPropertiesFields;

    protected static ?string $navigationLabel = 'Minecraft Properties';

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-device-gamepad';

    protected static ?int $navigationSort = 3;

    protected string $view = 'minecraft-properties::filament.server-properties';

    public static function canAccess(): bool
    {
        /** @var Server|null $server */
        $server = Filament::getTenant();
        if (!$server instanceof Server) {
            return false;
        }
        try {
            $repo = app(DaemonFileRepository::class)->setServer($server);
            $repo->getContent('server.properties');

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // Properties moved to ServerPropertiesFields trait

    // === Mount and Form Setup ===

    public function mount(): void
    {
        parent::mount();

        $this->initializePropertiesService();

        $this->loadProperties();

        $fields = $this->getAllFields();
        $this->data = array_combine($fields, array_map(fn ($field) => $this->{$field}, $fields));
        $this->data['raw'] = $this->raw;

        if (isset($this->form)) {
            $this->form->fill($this->data);
        }

        $this->originalData = $this->data;
        $this->originalRaw = $this->raw ?? '';
    }

    // === Form Building ===

    public function form(Schema $schema): Schema
    {
        if (empty($this->availableProperties)) {
            $this->loadProperties();
        }
        $basicComponents = $this->buildBasicComponents();
        $gameplayComponents = $this->buildGameplayComponents();
        $worldComponents = $this->buildWorldComponents();
        $networkComponents = $this->buildNetworkComponents();
        $advancedComponents = $this->buildAdvancedComponents();

        return parent::form($schema)
            ->components([
                Section::make('Basic')
                    ->icon('tabler-info-circle')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(2)->schema($basicComponents),
                        ]),
                    ]),

                Section::make('Gameplay')
                    ->icon('tabler-sword')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema($gameplayComponents),
                        ]),
                    ]),

                Section::make('World')
                    ->icon('tabler-world')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema($worldComponents),
                        ]),
                    ]),

                Section::make('Network')
                    ->icon('tabler-network')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema($networkComponents),
                        ]),
                    ]),

                Section::make('Advanced & Raw')
                    ->icon('tabler-cog')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema($advancedComponents),
                        ]),
                    ]),
            ]);
    }

    // === Data Loading and Saving ===

    public function loadProperties(): void
    {
        /** @var Server|null $server */
        $server = Filament::getTenant();
        if (!$server instanceof Server) {
            return;
        }
        try {
            $repo = app(DaemonFileRepository::class)->setServer($server);
            $content = $repo->getContent('server.properties');
        } catch (\Throwable $e) {
            // If the daemon file read fails, reset the form to avoid showing
            // stale or partial data in the UI.
            $this->resetForm();

            return;
        }

        $props = $this->propertiesService->parseProperties($content);

        $this->availableProperties = array_keys($props);
        $this->originalProps = $props;

        $formData = $this->propertiesService->mapParsedToFormData($props);
        foreach ($formData as $field => $value) {
            $this->{$field} = $value;
        }

        $this->raw = $content;
    }

    public function save(): void
    {
        /** @var Server|null $server */
        $server = Filament::getTenant();
        if (!$server instanceof Server) {
            Notification::make()
                ->danger()
                ->title('Invalid server.')
                ->send();

            return;
        }

        $currentState = $this->form->getState();
        $props = $this->propertiesService->mapStateToProperties($currentState, $this->originalProps, $this->availableProperties);

        $lines = [];
        $lines[] = '#Minecraft server properties';
        $lines[] = '#' . now()->toDateTimeString();

        foreach ($props as $key => $value) {
            $lines[] = $key . '=' . $value;
        }

        $content = implode("\n", $lines) . "\n";

        try {
            $repo = app(DaemonFileRepository::class)->setServer($server);
            $repo->putContent('server.properties', $content);

            $this->raw = $content;
            $this->originalRaw = $content;
            $this->originalData = $currentState;

            Notification::make()
                ->success()
                ->title('Saved Properties successfully.')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                // Surface a helpful message in the UI. Keep the exception
                // message short to avoid leaking sensitive details.
                ->title('Failed to save server.properties. Please check your configuration and try again.')
                ->send();
        }
    }

    // parseProperties moved to Services\PropertiesService

    // syncFromRaw moved to Services\PropertiesService — use applyParsedRaw() below

    private function applyParsedRaw(string $rawContent): void
    {
        $parsed = $this->propertiesService->parseProperties($rawContent);
        $formData = $this->propertiesService->mapParsedToFormData($parsed);
        $currentState = $this->form->getState();
        $merged = array_merge($currentState, $formData);
        $this->form->fill($merged);
    }

    // === Helper Methods ===

    private function isPropertyAvailable(string $field): bool
    {
        $property = $this->propertyMapping[$field] ?? $field;

        return in_array($property, $this->availableProperties);
    }

    private function createComponent(string $field)
    {
        if (!isset($this->componentMapping[$field])) {
            throw new \InvalidArgumentException("Unknown field: $field");
        }

        [$class, $options] = $this->componentMapping[$field];

        $component = $class::make($field);
        foreach ($options as $key => $value) {
            if ($key === 'options' && is_array($value)) {
                // Keep options as is for now
                if (method_exists($component, 'options')) {
                    $component->options($value);
                }

                continue;
            }

            if (method_exists($component, $key)) {
                $component->$key($value);
            }
        }

        return $component;
    }

    private function buildBasicComponents(): array
    {
        return array_map(fn ($field) => $this->createComponent($field), array_filter(self::BASIC_FIELDS, fn ($field) => $this->isPropertyAvailable($field)));
    }

    private function buildGameplayComponents(): array
    {
        return array_map(fn ($field) => $this->createComponent($field), array_filter(self::GAMEPLAY_FIELDS, fn ($field) => $this->isPropertyAvailable($field)));
    }

    private function buildWorldComponents(): array
    {
        return array_map(fn ($field) => $this->createComponent($field), array_filter(self::WORLD_FIELDS, fn ($field) => $this->isPropertyAvailable($field)));
    }

    private function buildNetworkComponents(): array
    {
        return array_map(fn ($field) => $this->createComponent($field), array_filter(self::NETWORK_FIELDS, fn ($field) => $this->isPropertyAvailable($field)));
    }

    private function buildAdvancedComponents(): array
    {
        $components = array_map(fn ($field) => $this->createComponent($field), array_filter(self::ADVANCED_FIELDS, fn ($field) => $this->isPropertyAvailable($field)));

        $components[] = Textarea::make('raw')
            ->label('Raw server.properties')
            ->rows(12)
            ->helperText('Advanced: edit the raw file directly')
            ->columnSpanFull()
            ->reactive()
            ->debounce(500)
            ->afterStateUpdated(fn ($state) => $this->applyParsedRaw($state));

        return $components;
    }
}
