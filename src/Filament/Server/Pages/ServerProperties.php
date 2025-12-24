<?php

namespace Pelican\MinecraftProperties\Filament\Server\Pages;

use Filament\Actions\Action;
use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use App\Filament\Server\Pages\ServerFormPage;
use Filament\Notifications\Notification;

final class ServerProperties extends ServerFormPage
{
    protected static ?string $navigationLabel = 'Minecraft Properties';
    protected static string|\BackedEnum|null $navigationIcon = 'tabler-device-gamepad';
    protected static ?int $navigationSort = 3;
    protected string $view = 'minecraft-properties::filament.server-properties';

    public static function canAccess(): bool
    {
        /** @var Server|null $server */
        $server = Filament::getTenant();
        if (! $server instanceof Server) {
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

    public $motd;
    public $max_players;
    public $online_mode;
    public $pvp;
    public $difficulty;
    public $gamemode;
    public $view_distance;
    public $spawn_protection;
    public $accepts_transfers;
    public $broadcast_console_to_ops;
    public $debug;
    public $op_permission_level;
    public $simulation_distance;
    public $sync_chunk_writes;
    public $whitelist;
    public $allow_nether;
    public $enable_command_block;
    public $enable_query;
    public $enable_rcon;
    public $force_gamemode;
    public $hardcore;
    public $level_name;
    public $level_seed;
    public $level_type;
    public $max_tick_time;
    public $network_compression_threshold;
    public $rcon_password;
    public $server_port;
    public $spawn_monsters;
    public $query_port;
    public $enable_jmx_monitoring;
    public $enable_status;
    public $enforce_secure_profile;
    public $enforce_whitelist;
    public $entity_broadcast_range_percentage;
    public $function_permission_level;
    public $generate_structures;
    public $generator_settings;
    public $hide_online_players;
    public $initial_disabled_packs;
    public $initial_enabled_packs;
    public $log_ips;
    public $max_chained_neighbor_updates;
    public $max_world_size;
    public $player_idle_timeout;
    public $prevent_proxy_connections;
    public $rate_limit;
    public $rcon_port;
    public $resource_pack;
    public $resource_pack_id;
    public $resource_pack_prompt;
    public $resource_pack_sha1;
    public $server_ip;
    public $spawn_animals;
    public $spawn_npcs;
    public $text_filtering_config;
    public $use_native_transport;

    /** @var array<string,mixed> */
    private array $originalData = [];
    private string $originalRaw = '';
    private array $availableProperties = [];
    private array $originalProps = [];

    private const BASIC_FIELDS = ['motd', 'max_players', 'online_mode', 'enable_query', 'enable_rcon', 'enable_status'];
    private const GAMEPLAY_FIELDS = ['difficulty', 'gamemode', 'force_gamemode', 'hardcore', 'pvp', 'spawn_monsters', 'spawn_animals', 'spawn_npcs'];
    private const WORLD_FIELDS = ['level_name', 'level_seed', 'level_type', 'view_distance', 'spawn_protection', 'generate_structures', 'generator_settings'];
    private const NETWORK_FIELDS = ['server_port', 'query_port', 'rcon_password', 'rcon_port', 'server_ip'];
    private const ADVANCED_FIELDS = ['network_compression_threshold', 'max_tick_time', 'enable_command_block', 'allow_flight', 'allow_nether', 'accepts_transfers', 'broadcast_console_to_ops', 'debug', 'op_permission_level', 'simulation_distance', 'sync_chunk_writes', 'whitelist', 'enable_jmx_monitoring', 'enforce_secure_profile', 'enforce_whitelist', 'entity_broadcast_range_percentage', 'function_permission_level', 'hide_online_players', 'initial_disabled_packs', 'initial_enabled_packs', 'log_ips', 'max_chained_neighbor_updates', 'max_world_size', 'player_idle_timeout', 'prevent_proxy_connections', 'rate_limit', 'resource_pack', 'resource_pack_id', 'resource_pack_prompt', 'resource_pack_sha1', 'text_filtering_config', 'use_native_transport'];
    private const ALL_FIELDS = [
        'motd', 'max_players', 'online_mode', 'pvp', 'difficulty', 'gamemode', 'view_distance', 'spawn_protection',
        'accepts_transfers', 'allow_flight', 'broadcast_console_to_ops', 'debug', 'allow_nether', 'enable_command_block',
        'enable_query', 'enable_rcon', 'force_gamemode', 'hardcore', 'level_name', 'level_seed', 'level_type',
        'max_tick_time', 'network_compression_threshold', 'op_permission_level', 'rcon_password', 'server_port',
        'simulation_distance', 'spawn_monsters', 'sync_chunk_writes', 'query_port', 'whitelist',
        'enable_jmx_monitoring', 'enable_status', 'enforce_secure_profile', 'enforce_whitelist',
        'entity_broadcast_range_percentage', 'function_permission_level', 'generate_structures', 'generator_settings',
        'hide_online_players', 'initial_disabled_packs', 'initial_enabled_packs', 'log_ips', 'max_chained_neighbor_updates',
        'max_world_size', 'player_idle_timeout', 'prevent_proxy_connections', 'rate_limit', 'rcon_port',
        'resource_pack', 'resource_pack_id', 'resource_pack_prompt', 'resource_pack_sha1', 'server_ip',
        'spawn_animals', 'spawn_npcs', 'text_filtering_config', 'use_native_transport'
    ];

    private array $fieldTypes = [
        'motd' => 'string',
        'max_players' => 'string',
        'online_mode' => 'bool',
        'pvp' => 'bool',
        'difficulty' => 'string',
        'gamemode' => 'string',
        'view_distance' => 'string',
        'spawn_protection' => 'string',
        'accepts_transfers' => 'bool',
        'allow_flight' => 'bool',
        'broadcast_console_to_ops' => 'bool',
        'debug' => 'bool',
        'allow_nether' => 'bool',
        'enable_command_block' => 'bool',
        'enable_query' => 'bool',
        'enable_rcon' => 'bool',
        'force_gamemode' => 'bool',
        'hardcore' => 'bool',
        'level_name' => 'string',
        'level_seed' => 'string',
        'level_type' => 'string',
        'max_tick_time' => 'string',
        'network_compression_threshold' => 'string',
        'op_permission_level' => 'string',
        'rcon_password' => 'string',
        'server_port' => 'string',
        'simulation_distance' => 'string',
        'spawn_monsters' => 'bool',
        'sync_chunk_writes' => 'bool',
        'query_port' => 'string',
        'whitelist' => 'bool',
        'enable_jmx_monitoring' => 'bool',
        'enable_status' => 'bool',
        'enforce_secure_profile' => 'bool',
        'enforce_whitelist' => 'bool',
        'entity_broadcast_range_percentage' => 'string',
        'function_permission_level' => 'string',
        'generate_structures' => 'bool',
        'generator_settings' => 'string',
        'hide_online_players' => 'bool',
        'initial_disabled_packs' => 'string',
        'initial_enabled_packs' => 'string',
        'log_ips' => 'bool',
        'max_chained_neighbor_updates' => 'string',
        'max_world_size' => 'string',
        'player_idle_timeout' => 'string',
        'prevent_proxy_connections' => 'bool',
        'rate_limit' => 'string',
        'rcon_port' => 'string',
        'resource_pack' => 'string',
        'resource_pack_id' => 'string',
        'resource_pack_prompt' => 'string',
        'resource_pack_sha1' => 'string',
        'server_ip' => 'string',
        'spawn_animals' => 'bool',
        'spawn_npcs' => 'bool',
        'text_filtering_config' => 'string',
        'use_native_transport' => 'bool',
    ];

    private array $defaultValues = [
        'motd' => 'A Minecraft Server',
        'max-players' => 20,
        'gamemode' => 'survival',
        'online-mode' => 'true',
        'pvp' => 'true',
        'difficulty' => 'normal',
        'view-distance' => 10,
        'spawn-protection' => 0,
        'network-compression-threshold' => 256,
        'max-tick-time' => 60000,
        'op-permission-level' => 4,
        'simulation-distance' => 10,
        'query.port' => 25565,
        'rcon.password' => '',
        'server-port' => 25565,
        'generate-structures' => 'true',
        'max-world-size' => 29999984,
        'player-idle-timeout' => 0,
        'rate-limit' => 0,
        'rcon.port' => 25575,
        'resource-pack' => '',
        'resource-pack-id' => '',
        'resource-pack-prompt' => '',
        'resource-pack-sha1' => '',
        'server-ip' => '',
        'text-filtering-config' => '',
        'entity-broadcast-range-percentage' => 100,
        'function-permission-level' => 2,
        'generator-settings' => '',
        'initial-disabled-packs' => '',
        'initial-enabled-packs' => '',
        'max-chained-neighbor-updates' => 1000000,
    ];

    private array $componentMapping = [
        'motd' => [TextInput::class, ['label' => 'Server Message (motd)', 'prefixIcon' => 'tabler-chat', 'helperText' => 'Shown in the server list.']],
        'max_players' => [TextInput::class, ['label' => 'Max Players', 'numeric' => true, 'minValue' => 0, 'prefixIcon' => 'tabler-users']],
        'online_mode' => [Toggle::class, ['label' => 'Online Mode']],
        'enable_query' => [Toggle::class, ['label' => 'Enable Query', 'helperText' => 'Allow Game Query (server list stats).']],
        'enable_rcon' => [Toggle::class, ['label' => 'Enable RCON']],
        'difficulty' => [Select::class, ['label' => 'Difficulty', 'options' => [
            'peaceful' => 'Peaceful',
            'easy' => 'Easy',
            'normal' => 'Normal',
            'hard' => 'Hard',
        ], 'default' => 'normal']],
        'gamemode' => [Select::class, ['label' => 'Default Gamemode', 'options' => [
            'survival' => 'Survival',
            'creative' => 'Creative',
            'adventure' => 'Adventure',
            'spectator' => 'Spectator',
        ], 'default' => 'survival']],
        'force_gamemode' => [Toggle::class, ['label' => 'Force Gamemode']],
        'hardcore' => [Toggle::class, ['label' => 'Hardcore']],
        'pvp' => [Toggle::class, ['label' => 'PVP']],
        'spawn_monsters' => [Toggle::class, ['label' => 'Spawn Monsters']],
        'level_name' => [TextInput::class, ['label' => 'Level Name', 'prefixIcon' => 'tabler-file-text']],
        'level_seed' => [TextInput::class, ['label' => 'Level Seed', 'prefixIcon' => 'tabler-hash']],
        'level_type' => [TextInput::class, ['label' => 'Level Type', 'prefixIcon' => 'tabler-cube']],
        'view_distance' => [TextInput::class, ['label' => 'View Distance', 'numeric' => true, 'minValue' => 2, 'prefixIcon' => 'tabler-eye']],
        'spawn_protection' => [TextInput::class, ['label' => 'Spawn Protection', 'numeric' => true, 'minValue' => 0, 'prefixIcon' => 'tabler-shield-star']],
        'server_port' => [TextInput::class, ['label' => 'Server Port', 'numeric' => true, 'minValue' => 0, 'maxValue' => 65535, 'prefixIcon' => 'tabler-network']],
        'query_port' => [TextInput::class, ['label' => 'Query Port', 'numeric' => true, 'minValue' => 0, 'maxValue' => 65535, 'prefixIcon' => 'tabler-network']],
        'rcon_password' => [TextInput::class, ['label' => 'RCON Password', 'prefixIcon' => 'tabler-key']],
        'rcon_port' => [TextInput::class, ['label' => 'RCON Port', 'numeric' => true, 'minValue' => 0, 'maxValue' => 65535]],
        'server_ip' => [TextInput::class, ['label' => 'Server IP']],
        'network_compression_threshold' => [TextInput::class, ['label' => 'Network Compression Threshold', 'numeric' => true, 'prefixIcon' => 'tabler-arrows-merge']],
        'max_tick_time' => [TextInput::class, ['label' => 'Max Tick Time', 'numeric' => true, 'prefixIcon' => 'tabler-clock']],
        'enable_command_block' => [Toggle::class, ['label' => 'Enable Command Block']],
        'allow_flight' => [Toggle::class, ['label' => 'Allow Flight']],
        'allow_nether' => [Toggle::class, ['label' => 'Allow Nether']],
        'accepts_transfers' => [Toggle::class, ['label' => 'Accepts Transfers']],
        'broadcast_console_to_ops' => [Toggle::class, ['label' => 'Broadcast Console to Ops']],
        'debug' => [Toggle::class, ['label' => 'Debug']],
        'op_permission_level' => [TextInput::class, ['label' => 'OP Permission Level', 'numeric' => true]],
        'simulation_distance' => [TextInput::class, ['label' => 'Simulation Distance', 'numeric' => true]],
        'sync_chunk_writes' => [Toggle::class, ['label' => 'Sync Chunk Writes']],
        'whitelist' => [Toggle::class, ['label' => 'Whitelist']],
        'enable_jmx_monitoring' => [Toggle::class, ['label' => 'Enable JMX Monitoring']],
        'enable_status' => [Toggle::class, ['label' => 'Enable Status']],
        'enforce_secure_profile' => [Toggle::class, ['label' => 'Enforce Secure Profile']],
        'enforce_whitelist' => [Toggle::class, ['label' => 'Enforce Whitelist']],
        'entity_broadcast_range_percentage' => [TextInput::class, ['label' => 'Entity Broadcast Range Percentage', 'numeric' => true]],
        'function_permission_level' => [TextInput::class, ['label' => 'Function Permission Level', 'numeric' => true]],
        'generate_structures' => [Toggle::class, ['label' => 'Generate Structures']],
        'generator_settings' => [TextInput::class, ['label' => 'Generator Settings']],
        'hide_online_players' => [Toggle::class, ['label' => 'Hide Online Players']],
        'initial_disabled_packs' => [TextInput::class, ['label' => 'Initial Disabled Packs']],
        'initial_enabled_packs' => [TextInput::class, ['label' => 'Initial Enabled Packs']],
        'log_ips' => [Toggle::class, ['label' => 'Log IPs']],
        'max_chained_neighbor_updates' => [TextInput::class, ['label' => 'Max Chained Neighbor Updates', 'numeric' => true]],
        'max_world_size' => [TextInput::class, ['label' => 'Max World Size', 'numeric' => true]],
        'player_idle_timeout' => [TextInput::class, ['label' => 'Player Idle Timeout', 'numeric' => true]],
        'prevent_proxy_connections' => [Toggle::class, ['label' => 'Prevent Proxy Connections']],
        'rate_limit' => [TextInput::class, ['label' => 'Rate Limit', 'numeric' => true]],
        'resource_pack' => [TextInput::class, ['label' => 'Resource Pack']],
        'resource_pack_id' => [TextInput::class, ['label' => 'Resource Pack ID']],
        'resource_pack_prompt' => [TextInput::class, ['label' => 'Resource Pack Prompt']],
        'resource_pack_sha1' => [TextInput::class, ['label' => 'Resource Pack SHA1']],
        'spawn_animals' => [Toggle::class, ['label' => 'Spawn Animals']],
        'spawn_npcs' => [Toggle::class, ['label' => 'Spawn NPCs']],
        'text_filtering_config' => [TextInput::class, ['label' => 'Text Filtering Config']],
        'use_native_transport' => [Toggle::class, ['label' => 'Use Native Transport']],
    ];

    private array $propertyMapping = [
        'motd' => 'motd',
        'max_players' => 'max-players',
        'online_mode' => 'online-mode',
        'pvp' => 'pvp',
        'difficulty' => 'difficulty',
        'gamemode' => 'gamemode',
        'view_distance' => 'view-distance',
        'spawn_protection' => 'spawn-protection',
        'accepts_transfers' => 'accepts-transfers',
        'allow_flight' => 'allow-flight',
        'broadcast_console_to_ops' => 'broadcast-console-to-ops',
        'debug' => 'debug',
        'allow_nether' => 'allow-nether',
        'enable_command_block' => 'enable-command-block',
        'enable_query' => 'enable-query',
        'enable_rcon' => 'enable-rcon',
        'force_gamemode' => 'force-gamemode',
        'hardcore' => 'hardcore',
        'level_name' => 'level-name',
        'level_seed' => 'level-seed',
        'level_type' => 'level-type',
        'max_tick_time' => 'max-tick-time',
        'network_compression_threshold' => 'network-compression-threshold',
        'op_permission_level' => 'op-permission-level',
        'rcon_password' => 'rcon.password',
        'server_port' => 'server-port',
        'simulation_distance' => 'simulation-distance',
        'spawn_monsters' => 'spawn-monsters',
        'sync_chunk_writes' => 'sync-chunk-writes',
        'query_port' => 'query.port',
        'whitelist' => 'white-list',
        'enable_jmx_monitoring' => 'enable-jmx-monitoring',
        'enable_status' => 'enable-status',
        'enforce_secure_profile' => 'enforce-secure-profile',
        'enforce_whitelist' => 'enforce-whitelist',
        'entity_broadcast_range_percentage' => 'entity-broadcast-range-percentage',
        'function_permission_level' => 'function-permission-level',
        'generate_structures' => 'generate-structures',
        'generator_settings' => 'generator-settings',
        'hide_online_players' => 'hide-online-players',
        'initial_disabled_packs' => 'initial-disabled-packs',
        'initial_enabled_packs' => 'initial-enabled-packs',
        'log_ips' => 'log-ips',
        'max_chained_neighbor_updates' => 'max-chained-neighbor-updates',
        'max_world_size' => 'max-world-size',
        'player_idle_timeout' => 'player-idle-timeout',
        'prevent_proxy_connections' => 'prevent-proxy-connections',
        'rate_limit' => 'rate-limit',
        'rcon_port' => 'rcon.port',
        'resource_pack' => 'resource-pack',
        'resource_pack_id' => 'resource-pack-id',
        'resource_pack_prompt' => 'resource-pack-prompt',
        'resource_pack_sha1' => 'resource-pack-sha1',
        'server_ip' => 'server-ip',
        'spawn_animals' => 'spawn-animals',
        'spawn_npcs' => 'spawn-npcs',
        'text_filtering_config' => 'text-filtering-config',
        'use_native_transport' => 'use-native-transport',
    ];

    private function toBool(?string $value, bool $default = false): bool
    {
        return is_null($value) ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function createComponent(string $field)
    {
        if (!isset($this->componentMapping[$field])) {
            throw new \InvalidArgumentException("Unknown field: $field");
        }

        [$class, $options] = $this->componentMapping[$field];

        $component = $class::make($field);
        foreach ($options as $key => $value) {
            if (method_exists($component, $key)) {
                $component->$key($value);
            }
        }

        return $component;
    }

    private function mapStateToProperties(array $state): array
    {
        $props = $this->originalProps;

        foreach ($this->propertyMapping as $field => $property) {
            if (!$this->isPropertyAvailable($field)) continue;

            $value = $state[$field] ?? $this->defaultValues[$property] ?? null;

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

        $this->data = array_combine(self::ALL_FIELDS, array_map(fn($field) => $this->{$field}, self::ALL_FIELDS));
        $this->data['raw'] = $this->raw;

        if (isset($this->form)) {
            $this->form->fill($this->data);
        }

        $this->originalData = $this->data;
        $this->originalRaw = $this->raw ?? '';
    }

    private function isPropertyAvailable(string $field): bool
    {
        $property = $this->propertyMapping[$field] ?? $field;
        return in_array($property, $this->availableProperties);
    }

    public function form(Schema $schema): Schema
    {
        if (empty($this->availableProperties)) {
            $this->loadProperties();
        }

        $basicComponents = array_map(fn($field) => $this->createComponent($field), array_filter(self::BASIC_FIELDS, fn($field) => $this->isPropertyAvailable($field)));

        $gameplayComponents = array_map(fn($field) => $this->createComponent($field), array_filter(self::GAMEPLAY_FIELDS, fn($field) => $this->isPropertyAvailable($field)));

        $worldComponents = array_map(fn($field) => $this->createComponent($field), array_filter(self::WORLD_FIELDS, fn($field) => $this->isPropertyAvailable($field)));

        $networkComponents = array_map(fn($field) => $this->createComponent($field), array_filter(self::NETWORK_FIELDS, fn($field) => $this->isPropertyAvailable($field)));

        $advancedComponents = array_map(fn($field) => $this->createComponent($field), array_filter(self::ADVANCED_FIELDS, fn($field) => $this->isPropertyAvailable($field)));
        $advancedComponents[] = Textarea::make('raw')->label('Raw server.properties')->rows(12)->helperText('Advanced: edit the raw file directly')->columnSpanFull()->reactive()->debounce(500)->afterStateUpdated(function ($state) {
            $this->syncFromRaw($state);
        });

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

    public function getHeading(): ?string
    {
        return 'Minecraft Server Properties';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
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
        if (! $server instanceof Server) {
            return;
        }
        try {
            $repo = app(DaemonFileRepository::class)->setServer($server);
            $content = $repo->getContent('server.properties');
        } catch (\Throwable $e) {
            $this->resetForm();
            return;
        }

        $props = $this->parseProperties($content);

        $this->availableProperties = array_keys($props);
        $this->originalProps = $props;

        foreach (self::ALL_FIELDS as $field) {
            $property = $this->propertyMapping[$field] ?? $field;
            $value = $props[$property] ?? null;

            if ($this->fieldTypes[$field] === 'bool') {
                $default = in_array($field, ['online_mode', 'pvp']) ? true : false;
                $this->{$field} = $this->toBool($value, $default);
            } else {
                $this->{$field} = $value;
            }
        }

        $this->raw = $content;
    }

    public function save(): void
    {
        /** @var Server|null $server */
        $server = Filament::getTenant();
        if (! $server instanceof Server) {
            Notification::make()
                ->danger()
                ->title('Invalid server.')
                ->send();
            return;
        }

        $currentState = $this->form->getState();
        $props = $this->mapStateToProperties($currentState);

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
        $reverseMapping = array_flip($this->propertyMapping);
        $formData = [];
        foreach ($parsed as $prop => $value) {
            if (isset($reverseMapping[$prop])) {
                $field = $reverseMapping[$prop];
                $type = $this->fieldTypes[$field] ?? 'string';
                $formData[$field] = $type === 'bool' ? $this->toBool($value) : $value;
            }
        }
        $currentState = $this->form->getState();
        $merged = array_merge($currentState, $formData);
        $this->form->fill($merged);
    }
}
