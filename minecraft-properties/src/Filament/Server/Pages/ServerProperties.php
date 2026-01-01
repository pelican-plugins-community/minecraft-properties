<?php

namespace Pelican\MinecraftProperties\Filament\Server\Pages;

use App\Filament\Components\Forms\Fields\MonacoEditor;
use Filament\Actions\Action;
use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Pelican\MinecraftProperties\Enums\PropertyCategory;
use Pelican\MinecraftProperties\Helpers\FieldCategoryMap;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use App\Filament\Server\Pages\ServerFormPage;
use Filament\Notifications\Notification;
use Throwable;

final class ServerProperties extends ServerFormPage
{
    protected static ?string $navigationLabel = 'Minecraft Properties';
    protected static string|\BackedEnum|null $navigationIcon = 'tabler-device-gamepad';
    protected static ?int $navigationSort = 3;
    protected string $view = 'minecraft-properties::filament.server-properties';

    public static function shouldRegisterNavigation(): bool
    {
        /** @var Server|null $server */
        $server = Filament::getTenant();
        try {
            $repo = app(DaemonFileRepository::class)->setServer($server);
            $repo->getContent('server.properties');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
    public $raw = '';
    public $editor = '';



    /** @var array<string,mixed> */
    private array $originalData = [];
    private string $originalRaw = '';
    private array $availableProperties = [];
    private array $originalProps = [];
    private array $allFields = [];

    private array $fieldTypes = [];


    private array $componentMapping = [];

    /** @var float|null */
    private ?float $editorEditedAt = null;

    /** @var float|null */
    private ?float $originalEditorEditedAt = null;

    /** @var array<string,float> */
    private array $fieldEditedAt = [];

    /** @var array<string,float> */
    private array $originalFieldEditedAt = [];


    private function fieldToProperty(string $field): string
    {
        $special = [
            'rcon_password' => 'rcon.password',
            'rcon_port' => 'rcon.port',
            'query_port' => 'query.port',
            'whitelist' => 'white-list',
        ];

        if (isset($special[$field])) return $special[$field];

        return str_replace('_', '-', $field);
    }

    private function propertyToFieldName(string $property): string
    {
        return str_replace(['.', '-'], '_', $property);
    }

    private function detectTypeFromValue(?string $value): string
    {
        if (is_null($value)) return 'string';
        $lower = strtolower(trim($value));
        if ($lower === 'true' || $lower === 'false') return 'bool';
        return 'string';
    }

    private function getFieldType(string $field, ?string $value = null): string
    {
        if (isset($this->fieldTypes[$field])) {
            return $this->fieldTypes[$field];
        }
        return $this->detectTypeFromValue($value);
    }

    private function toBool(?string $value, bool $default = false): bool
    {
        return is_null($value) ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }


    private function createComponent(string $field)
    {
            if (!isset($this->componentMapping[$field])) {
            $property = $this->fieldToProperty($field);
            $value = $this->originalProps[$property] ?? null;
            $type = $this->getFieldType($field, $value);
            if ($type === 'bool') {
                $component = Toggle::make($field);
            } else {
                $component = TextInput::make($field);
            }
            $label = trans("minecraft-properties::{$field}_label");
            if ($label === "minecraft-properties::{$field}_label") {
                $label = ucwords(str_replace('_', ' ', $field));
            }
            if (method_exists($component, 'label')) $component->label($label);
            if (method_exists($component, 'afterStateUpdated')) {
                $component->afterStateUpdated(function ($state) use ($field) {
                    $this->fieldEditedAt[$field] = microtime(true);
                });
            }
            return $component;
        }

        [$class, $options] = $this->componentMapping[$field];

        $component = $class::make($field);
        foreach ($options as $key => $value) {
            // Translate labels and helper texts where appropriate
            if ($key === 'label') {
                $label = trans("minecraft-properties::{$field}_label");
                if ($label === "minecraft-properties::{$field}_label") {
                    $label = ucwords(str_replace('_', ' ', $field));
                }
                if (method_exists($component, 'label')) {
                    $component->label($label);
                }
                continue;
            }

            if ($key === 'helperText') {
                $helper = trans("minecraft-properties::{$field}_helper");
                if ($helper === "minecraft-properties::{$field}_helper") {
                    $helper = null;
                }
                if (method_exists($component, 'helperText') && $helper) {
                    $component->helperText($helper);
                }
                continue;
            }

            if ($key === 'options' && is_array($value)) {
                $translatedOptions = [];
                foreach ($value as $optKey => $optLabel) {
                    $t = trans("minecraft-properties::{$field}_{$optKey}");
                    $translatedOptions[$optKey] = $t === "minecraft-properties::{$field}_{$optKey}" ? $optLabel : $t;
                }
                if (method_exists($component, 'options')) {
                    $component->options($translatedOptions);
                }
                continue;
            }

            if (method_exists($component, $key)) {
                $component->$key($value);
            }
        }
        if (method_exists($component, 'afterStateUpdated')) {
            $component->afterStateUpdated(function ($state) use ($field) {
                $this->fieldEditedAt[$field] = microtime(true);
            });
        }
        return $component;
    }

    private function mapStateToProperties(array $state): array
    {
        $props = $this->originalProps;

        foreach ($state as $field => $value) {
            if ($field === 'editor') continue;


            $property = $this->fieldToProperty($field);

            if (!array_key_exists($property, $props)) {
                foreach ($this->availableProperties as $avail) {
                    if ($this->propertyToFieldName($avail) === $field) {
                        $property = $avail;
                        break;
                    }
                }
            }

            if (is_bool($value)) {
                $props[$property] = $value ? 'true' : 'false';
            } elseif (!is_null($value)) {
                $props[$property] = (string) $value;
            }
        }

        return $props;
    }

    public function mount(): void
    {
        parent::mount();

        $this->loadProperties();

            $fields = $this->getAllFields();
            $built = array_combine($fields, array_map(fn($field) => $this->{$field} ?? null, $fields));

            $this->data = array_merge($this->data ?? [], $built);

            $this->originalRaw = $this->raw;

            $now = microtime(true);
            $this->originalEditorEditedAt = $now;
            $this->editorEditedAt = $now;
            foreach ($fields as $f) {
                $this->originalFieldEditedAt[$f] = $now;
                $this->fieldEditedAt[$f] = $now;
            }

    }

    private function isPropertyAvailable(string $field): bool
    {
        $property = $this->fieldToProperty($field);
        if (in_array($property, $this->availableProperties)) return true;

        foreach ($this->availableProperties as $avail) {
            if ($this->propertyToFieldName($avail) === $field) return true;
        }

        return false;
    }

    private function getAllFields(): array
    {
        if (!empty($this->allFields)) {
            return $this->allFields;
        }
        $dynamic = array_map(fn($p) => $this->propertyToFieldName($p), $this->availableProperties);
        return array_values(array_unique(array_merge(FieldCategoryMap::all()->all(), $dynamic)));
    }

    public function form(Schema $schema): Schema
    {
        if (empty($this->availableProperties)) {
            $this->loadProperties();
        }
        $basicFields = array_filter(FieldCategoryMap::fields(PropertyCategory::BASIC), fn($field) => $this->isPropertyAvailable($field));
        $basicComponents = array_map(fn($field) => $this->createComponent($field), $basicFields);

        $gameplayFields = array_filter(FieldCategoryMap::fields(PropertyCategory::GAMEPLAY), fn($field) => $this->isPropertyAvailable($field));
        $gameplayComponents = array_map(fn($field) => $this->createComponent($field), $gameplayFields);

        $worldFields = array_filter(FieldCategoryMap::fields(PropertyCategory::WORLD), fn($field) => $this->isPropertyAvailable($field));
        $worldComponents = array_map(fn($field) => $this->createComponent($field), $worldFields);

        $networkFields = array_filter(FieldCategoryMap::fields(PropertyCategory::NETWORK), fn($field) => $this->isPropertyAvailable($field));
        $networkComponents = array_map(fn($field) => $this->createComponent($field), $networkFields);

        $advancedFields = array_filter(FieldCategoryMap::fields(PropertyCategory::ADVANCED), fn($field) => $this->isPropertyAvailable($field));
        $advancedComponents = array_map(fn($field) => $this->createComponent($field), $advancedFields);

        $otherProps = array_filter($this->availableProperties, fn($p) => ! in_array($this->propertyToFieldName($p), FieldCategoryMap::all()->all()));
        foreach ($otherProps as $prop) {
            $field = $this->propertyToFieldName($prop);
            if (! isset($this->fieldTypes[$field])) {
                $this->fieldTypes[$field] = $this->detectTypeFromValue($this->originalProps[$prop] ?? null);
            }
            $advancedComponents[] = $this->createComponent($field);
        }
        $advancedComponents[] = MonacoEditor::make('editor')
            ->label(trans('minecraft-properties::raw_label'))
            ->helperText(trans('minecraft-properties::raw_helper'))
            ->columnSpanFull()
            ->afterStateUpdated(function ($state) {
                // Sync parsed properties into the form state first.
                $this->syncFromRaw($state);

                $this->editorEditedAt = microtime(true) + 0.001;
            });

        return parent::form($schema)
            ->components([
                Section::make(trans('minecraft-properties::section_basic'))
                    ->icon('tabler-info-circle')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(2)->schema($basicComponents),
                        ]),
                    ]),

                Section::make(trans('minecraft-properties::section_gameplay'))
                    ->icon('tabler-sword')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema($gameplayComponents),
                        ]),
                    ]),

                Section::make(trans('minecraft-properties::section_world'))
                    ->icon('tabler-world')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema($worldComponents),
                        ]),
                    ]),

                Section::make(trans('minecraft-properties::section_network'))
                    ->icon('tabler-network')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema($networkComponents),
                        ]),
                    ]),

                Section::make(trans('minecraft-properties::section_advanced_raw'))
                    ->icon('tabler-cog')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema($advancedComponents),
                        ]),
                    ]),
            ]);
    }

    public function getHeading(): ?string
    {
        return trans('minecraft-properties::heading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(trans('minecraft-properties::action_save'))
                ->color('primary')
                ->icon('tabler-device-floppy')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function loadProperties(): void
    {
        /** @var Server|null $server */
        $server = Filament::getTenant();

        try {
            $repo = app(DaemonFileRepository::class)->setServer($server);
            $content = $repo->getContent('server.properties');
            $this->data['editor'] = $content;
        } catch (Throwable $e) {
            return;
        }

        $props = $this->parseProperties($content);


        $this->availableProperties = array_keys($props);
        $this->originalProps = $props;

        $this->allFields = array_map(fn($p) => $this->propertyToFieldName($p), $this->availableProperties);


        foreach ($this->availableProperties as $property) {
            $field = $this->propertyToFieldName($property);
            $value = $props[$property] ?? null;

            if (! isset($this->fieldTypes[$field])) {
                $this->fieldTypes[$field] = $this->detectTypeFromValue($value);
            }

            if (! isset($this->componentMapping[$field])) {
                if ($this->fieldTypes[$field] === 'bool') {
                    $this->componentMapping[$field] = [Toggle::class, ['label' => ucfirst(str_replace('_', ' ', $field))]];
                } else {
                    $this->componentMapping[$field] = [TextInput::class, ['label' => ucfirst(str_replace('_', ' ', $field))]];
                }
            }
        }

        foreach ($this->allFields as $field) {
            $property = $this->fieldToProperty($field);
            $value = $props[$property] ?? null;

            if (($this->fieldTypes[$field] ?? 'string') === 'bool') {
                $default = in_array($field, ['online_mode', 'pvp'], true) ? true : false;
                $this->{$field} = $this->toBool($value, $default);
            } else {
                $this->{$field} = $value;
            }
        }

        $now = microtime(true);
        $this->originalEditorEditedAt = $now;
        $this->editorEditedAt = $now;
        foreach ($this->allFields as $f) {
            $this->originalFieldEditedAt[$f] = $now;
            $this->fieldEditedAt[$f] = $now;
        }

    }

    public function save(): void
    {
        /** @var Server|null $server */
        $server = Filament::getTenant();
        $currentState = $this->form->getState();
        $editorTime = $this->editorEditedAt ?? 0;
        $fieldsTime = 0;
        if (!empty($this->fieldEditedAt)) {
            $fieldsTime = max($this->fieldEditedAt);
        }

        if ($editorTime >= $fieldsTime) {
            $content = $currentState['editor'] ?? $this->data['editor'] ?? '';
            if ($content !== '' && !str_ends_with($content, "\n")) {
                $content .= "\n";
            }
        } else {
            $props = $this->mapStateToProperties($currentState);
            $lines = [];
            foreach ($props as $key => $value) {
                $lines[] = $key . '=' . $value;
            }
            $content = implode("\n", $lines);
            if ($content !== '') {
                $content .= "\n";
            }
        }

        try {
            $repo = app(DaemonFileRepository::class)->setServer($server);
            $repo->putContent('server.properties', $content);

            $this->originalRaw = $content;
            $this->originalData = $currentState;

            Notification::make()
                ->success()
                ->title('Saved server.properties successfully.')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Failed to save server.properties: ' . $e->getMessage())
                ->send();
        }
    }

    private function parseProperties(string $content): array
    {
        return array_reduce(preg_split('/\r\n|\r|\n/', $content) ?? [], function($carry, $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) return $carry;
            [$key, $value] = array_map('trim', explode('=', $line, 2) + [null, null]);
            if ($key && $value !== null) $carry[$key] = $value;
            return $carry;
        }, []);
    }

    private function syncFromRaw(string $rawContent): void
    {
        $parsed = $this->parseProperties($rawContent);
        $formData = [];
        foreach ($parsed as $prop => $value) {
            $field = $this->propertyToFieldName($prop);
            $type = $this->fieldTypes[$field] ?? $this->detectTypeFromValue($value);
            $formData[$field] = $type === 'bool' ? $this->toBool($value) : $value;
        }
        $currentState = $this->form->getState();
        $merged = array_merge($currentState, $formData);
        $this->form->fill($merged);
    }
}
