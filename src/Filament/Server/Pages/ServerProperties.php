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
    protected static string | \UnitEnum | null $navigationGroup = null;
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationParentItem = null;
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
    public $whitelist;
    public $raw;

    public $accepts_transfers;
    public $allow_flight;
    public $allow_nether;
    public $broadcast_console_to_ops;
    public $debug;
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
    public $op_permission_level;
    public $rcon_password;
    public $server_port;
    public $simulation_distance;
    public $spawn_monsters;
    public $sync_chunk_writes;
    public $query_port;

    /** @var array<string,mixed> */
    private array $originalData = [];
    private string $originalRaw = '';
    private array $availableProperties = [];
    private array $originalProps = [];

    private array $propertyMapping = [
        'motd' => 'motd',
        'max_players' => 'max-players',
        'online_mode' => 'online-mode',
        'pvp' => 'pvp',
        'difficulty' => 'difficulty',
        'gamemode' => 'gamemode',
        'view_distance' => 'view-distance',
        'spawn_protection' => 'spawn-protection',
        'whitelist' => 'white-list',
        'accepts_transfers' => 'accepts-transfers',
        'allow_flight' => 'allow-flight',
        'allow_nether' => 'allow-nether',
        'broadcast_console_to_ops' => 'broadcast-console-to-ops',
        'debug' => 'debug',
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
    ];

    /**
     * Initialize the page state from the current server properties and populate the form.
     *
     * Loads server properties, maps them into the page's form data structure, fills the Filament form (if present),
     * and stores snapshots in `originalData` and `originalRaw` for later comparison.
     */
    public function mount(): void
    {
        parent::mount();

        $this->loadProperties();

        $this->data = [
            'motd' => $this->motd,
            'max_players' => $this->max_players,
            'online_mode' => $this->online_mode,
            'pvp' => $this->pvp,
            'difficulty' => $this->difficulty,
            'gamemode' => $this->gamemode,
            'view_distance' => $this->view_distance,
            'spawn_protection' => $this->spawn_protection,
            'whitelist' => $this->whitelist,
            'accepts_transfers' => $this->accepts_transfers,
            'allow_flight' => $this->allow_flight,
            'allow_nether' => $this->allow_nether,
            'broadcast_console_to_ops' => $this->broadcast_console_to_ops,
            'debug' => $this->debug,
            'enable_command_block' => $this->enable_command_block,
            'enable_query' => $this->enable_query,
            'enable_rcon' => $this->enable_rcon,
            'force_gamemode' => $this->force_gamemode,
            'hardcore' => $this->hardcore,
            'level_name' => $this->level_name,
            'level_seed' => $this->level_seed,
            'level_type' => $this->level_type,
            'max_tick_time' => $this->max_tick_time,
            'network_compression_threshold' => $this->network_compression_threshold,
            'op_permission_level' => $this->op_permission_level,
            'rcon_password' => $this->rcon_password,
            'server_port' => $this->server_port,
            'simulation_distance' => $this->simulation_distance,
            'spawn_monsters' => $this->spawn_monsters,
            'sync_chunk_writes' => $this->sync_chunk_writes,
            'query_port' => $this->query_port,
            'raw' => $this->raw,
        ];

        if (isset($this->form)) {
            $this->form->fill($this->data);
        }

        $this->originalData = $this->data;
        $this->originalRaw = $this->raw ?? '';
    }

    /**
     * Check if the server.properties key mapped from a form field is available.
     *
     * @param string $field The form field name to check (will be mapped to the actual server.properties key).
     * @return bool `true` if the mapped property exists in the available properties, `false` otherwise.
     */
    private function isPropertyAvailable(string $field): bool
    {
        $property = $this->propertyMapping[$field] ?? $field;
        return in_array($property, $this->availableProperties);
    }

    /**
     * Build the Filament form schema for the server properties page using available server.properties fields.
     *
     * @param Schema $schema The base form schema to extend.
     * @return Schema The composed schema containing sections and components for each available server property (including a raw editor).
     */
    public function form(Schema $schema): Schema
    {
        $basicComponents = [];
        if ($this->isPropertyAvailable('motd')) {
            $basicComponents[] = TextInput::make('motd')->label('Server Message (motd)')->helperText('Shown in the server list.')->prefixIcon('tabler-chat');
        }
        if ($this->isPropertyAvailable('max_players')) {
            $basicComponents[] = TextInput::make('max_players')->label('Max Players')->numeric()->minValue(0)->prefixIcon('tabler-users');
        }
        if ($this->isPropertyAvailable('online_mode')) {
            $basicComponents[] = Toggle::make('online_mode')->label('Online Mode');
        }
        if ($this->isPropertyAvailable('enable_query')) {
            $basicComponents[] = Toggle::make('enable_query')->label('Enable Query')->helperText('Allow Game Query (server list stats).');
        }
        if ($this->isPropertyAvailable('enable_rcon')) {
            $basicComponents[] = Toggle::make('enable_rcon')->label('Enable RCON');
        }

        $gameplayComponents = [];
        if ($this->isPropertyAvailable('difficulty')) {
            $gameplayComponents[] = Select::make('difficulty')->label('Difficulty')->options([
                'peaceful' => 'Peaceful',
                'easy' => 'Easy',
                'normal' => 'Normal',
                'hard' => 'Hard',
            ])->default('normal');
        }
        if ($this->isPropertyAvailable('gamemode')) {
            $gameplayComponents[] = Select::make('gamemode')->label('Default Gamemode')->options([
                'survival' => 'Survival',
                'creative' => 'Creative',
                'adventure' => 'Adventure',
                'spectator' => 'Spectator',
            ])->default('survival');
        }
        if ($this->isPropertyAvailable('force_gamemode')) {
            $gameplayComponents[] = Toggle::make('force_gamemode')->label('Force Gamemode');
        }
        if ($this->isPropertyAvailable('hardcore')) {
            $gameplayComponents[] = Toggle::make('hardcore')->label('Hardcore');
        }
        if ($this->isPropertyAvailable('pvp')) {
            $gameplayComponents[] = Toggle::make('pvp')->label('PVP');
        }
        if ($this->isPropertyAvailable('spawn_monsters')) {
            $gameplayComponents[] = Toggle::make('spawn_monsters')->label('Spawn Monsters');
        }

        $worldComponents = [];
        if ($this->isPropertyAvailable('level_name')) {
            $worldComponents[] = TextInput::make('level_name')->label('Level Name')->prefixIcon('tabler-file-text');
        }
        if ($this->isPropertyAvailable('level_seed')) {
            $worldComponents[] = TextInput::make('level_seed')->label('Level Seed')->prefixIcon('tabler-hash');
        }
        if ($this->isPropertyAvailable('level_type')) {
            $worldComponents[] = TextInput::make('level_type')->label('Level Type')->prefixIcon('tabler-cube');
        }
        if ($this->isPropertyAvailable('view_distance')) {
            $worldComponents[] = TextInput::make('view_distance')->label('View Distance')->numeric()->minValue(2)->prefixIcon('tabler-eye');
        }
        if ($this->isPropertyAvailable('spawn_protection')) {
            $worldComponents[] = TextInput::make('spawn_protection')->label('Spawn Protection')->numeric()->minValue(0)->prefixIcon('tabler-shield-star');
        }

        $networkComponents = [];
        if ($this->isPropertyAvailable('server_port')) {
            $networkComponents[] = TextInput::make('server_port')->label('Server Port')->numeric()->minValue(0)->prefixIcon('tabler-network');
        }
        if ($this->isPropertyAvailable('query_port')) {
            $networkComponents[] = TextInput::make('query_port')->label('Query Port')->numeric()->minValue(0)->prefixIcon('tabler-network');
        }
        if ($this->isPropertyAvailable('rcon_password')) {
            $networkComponents[] = TextInput::make('rcon_password')->label('RCON Password')->prefixIcon('tabler-key');
        }

        $advancedComponents = [];
        if ($this->isPropertyAvailable('network_compression_threshold')) {
            $advancedComponents[] = TextInput::make('network_compression_threshold')->label('Network Compression Threshold')->numeric()->prefixIcon('tabler-arrows-merge');
        }
        if ($this->isPropertyAvailable('max_tick_time')) {
            $advancedComponents[] = TextInput::make('max_tick_time')->label('Max Tick Time')->numeric()->prefixIcon('tabler-clock');
        }
        if ($this->isPropertyAvailable('enable_command_block')) {
            $advancedComponents[] = Toggle::make('enable_command_block')->label('Enable Command Block');
        }
        if ($this->isPropertyAvailable('allow_flight')) {
            $advancedComponents[] = Toggle::make('allow_flight')->label('Allow Flight');
        }
        if ($this->isPropertyAvailable('allow_nether')) {
            $advancedComponents[] = Toggle::make('allow_nether')->label('Allow Nether');
        }
        $advancedComponents[] = Textarea::make('raw')->label('Raw server.properties')->rows(12)->helperText('Advanced: edit the raw file directly')->columnSpanFull();

        return parent::form($schema)
            ->components(array_filter([
                !empty($basicComponents) ? Section::make('Basic')
                    ->icon('tabler-info-circle')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(2)->schema($basicComponents),
                        ]),
                    ]) : null,

                !empty($gameplayComponents) ? Section::make('Gameplay')
                    ->icon('tabler-sword')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema($gameplayComponents),
                        ]),
                    ]) : null,

                !empty($worldComponents) ? Section::make('World')
                    ->icon('tabler-world')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema($worldComponents),
                        ]),
                    ]) : null,

                !empty($networkComponents) ? Section::make('Network')
                    ->icon('tabler-network')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema($networkComponents),
                        ]),
                    ]) : null,

                Section::make('Advanced & Raw')
                    ->icon('tabler-cog')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema($advancedComponents),
                        ]),
                    ]),
            ]));
    }

    /**
     * Provide the page heading for the server properties page.
     *
     * @return string|null The heading text to display, or null if no heading should be shown.
     */
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

    /**
     * Load the server.properties file for the current tenant server and populate the page's property fields.
     *
     * Retrieves the tenant server, attempts to read and parse the server.properties file, sets
     * internal availability and original property maps, and maps parsed values into the page's
     * public properties (including boolean conversions). If no tenant server is available the
     * method returns immediately. If reading the file fails, the form is reset and the method returns.
     *
     * @return void
     */
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

        $this->motd = $props['motd'] ?? '';
        $this->max_players = $props['max-players'] ?? $props['max_players'] ?? null;
        $this->online_mode = isset($props['online-mode']) ? filter_var($props['online-mode'], FILTER_VALIDATE_BOOLEAN) : ($props['online_mode'] ?? true);
        $this->pvp = isset($props['pvp']) ? filter_var($props['pvp'], FILTER_VALIDATE_BOOLEAN) : ($props['pvp'] ?? true);
        $this->difficulty = $props['difficulty'] ?? null;
        $this->gamemode = $props['gamemode'] ?? $props['level-type'] ?? null;
        $this->view_distance = $props['view-distance'] ?? null;
        $this->spawn_protection = $props['spawn-protection'] ?? null;
        $this->whitelist = isset($props['white-list']) ? filter_var($props['white-list'], FILTER_VALIDATE_BOOLEAN) : ($props['white_list'] ?? false);
        $this->accepts_transfers = isset($props['accepts-transfers']) ? filter_var($props['accepts-transfers'], FILTER_VALIDATE_BOOLEAN) : null;
        $this->allow_flight = isset($props['allow-flight']) ? filter_var($props['allow-flight'], FILTER_VALIDATE_BOOLEAN) : null;
        $this->allow_nether = isset($props['allow-nether']) ? filter_var($props['allow-nether'], FILTER_VALIDATE_BOOLEAN) : null;
        $this->enable_query = isset($props['enable-query']) ? filter_var($props['enable-query'], FILTER_VALIDATE_BOOLEAN) : null;
        $this->enable_rcon = isset($props['enable-rcon']) ? filter_var($props['enable-rcon'], FILTER_VALIDATE_BOOLEAN) : null;
        $this->enable_command_block = isset($props['enable-command-block']) ? filter_var($props['enable-command-block'], FILTER_VALIDATE_BOOLEAN) : null;
        $this->force_gamemode = isset($props['force-gamemode']) ? filter_var($props['force-gamemode'], FILTER_VALIDATE_BOOLEAN) : null;
        $this->hardcore = isset($props['hardcore']) ? filter_var($props['hardcore'], FILTER_VALIDATE_BOOLEAN) : null;
        $this->level_name = $props['level-name'] ?? $props['level_name'] ?? null;
        $this->level_seed = $props['level-seed'] ?? null;
        $this->level_type = $props['level-type'] ?? null;
        $this->network_compression_threshold = $props['network-compression-threshold'] ?? null;
        $this->max_tick_time = $props['max-tick-time'] ?? null;
        $this->op_permission_level = $props['op-permission-level'] ?? null;
        $this->rcon_password = $props['rcon.password'] ?? $props['rcon_password'] ?? null;
        $this->server_port = $props['server-port'] ?? $props['server_port'] ?? null;
        $this->query_port = $props['query.port'] ?? $props['query_port'] ?? null;
        $this->simulation_distance = $props['simulation-distance'] ?? null;
        $this->spawn_monsters = isset($props['spawn-monsters']) ? filter_var($props['spawn-monsters'], FILTER_VALIDATE_BOOLEAN) : null;
        $this->sync_chunk_writes = isset($props['sync-chunk-writes']) ? filter_var($props['sync-chunk-writes'], FILTER_VALIDATE_BOOLEAN) : null;
        $this->raw = $content;
    }

    /**
     * Save the current form state into the server's server.properties file.
     *
     * If the current tenant is not a valid Server, a danger notification is shown and the method returns.
     * If the raw editor value was changed, that raw content is written directly; otherwise the method
     * maps available form fields to server.properties keys, serializes them to a key=value file (with a
     * header comment and timestamp), and writes the resulting content to the server's daemon file repository.
     *
     * On successful write the page state is updated ($this->raw, $this->originalRaw, $this->originalData)
     * and a success notification is shown. If writing fails a danger notification is shown containing the error message.
     */
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
        $get = fn($k, $fallback = null) => $currentState[$k] ?? $fallback;

        // If raw content was edited, use it directly
        if (!is_null($get('raw')) && trim($get('raw')) !== trim($this->originalRaw)) {
            $content = $get('raw');
        } else {
            // Build content from form fields
            $props = $this->originalProps;

            // Update known properties
            if ($this->isPropertyAvailable('accepts_transfers')) $props['accepts-transfers'] = $get('accepts_transfers', false) ? 'true' : 'false';
            if ($this->isPropertyAvailable('allow_flight')) $props['allow-flight'] = $get('allow_flight', false) ? 'true' : 'false';
            if ($this->isPropertyAvailable('allow_nether')) $props['allow-nether'] = $get('allow_nether', false) ? 'true' : 'false';
            if ($this->isPropertyAvailable('broadcast_console_to_ops')) $props['broadcast-console-to-ops'] = $get('broadcast_console_to_ops', false) ? 'true' : 'false';
            if ($this->isPropertyAvailable('debug')) $props['debug'] = $get('debug', false) ? 'true' : 'false';
            if ($this->isPropertyAvailable('difficulty') && !is_null($get('difficulty'))) $props['difficulty'] = $get('difficulty');
            if ($this->isPropertyAvailable('enable_command_block')) $props['enable-command-block'] = $get('enable_command_block', false) ? 'true' : 'false';
            if ($this->isPropertyAvailable('enable_query')) $props['enable-query'] = $get('enable_query', false) ? 'true' : 'false';
            if ($this->isPropertyAvailable('enable_rcon')) $props['enable-rcon'] = $get('enable_rcon', false) ? 'true' : 'false';
            $props['enable-status'] = 'true';
            if ($this->isPropertyAvailable('force_gamemode')) $props['force-gamemode'] = $get('force_gamemode', false) ? 'true' : 'false';
            if ($this->isPropertyAvailable('gamemode')) $props['gamemode'] = $get('gamemode') ?? 'survival';
            if ($this->isPropertyAvailable('hardcore')) $props['hardcore'] = $get('hardcore', false) ? 'true' : 'false';
            if ($this->isPropertyAvailable('max_players')) $props['max-players'] = $get('max_players') ?? 20;
            if ($this->isPropertyAvailable('max_tick_time') && !is_null($get('max_tick_time'))) $props['max-tick-time'] = $get('max_tick_time');
            if ($this->isPropertyAvailable('level_name') && !is_null($get('level_name'))) $props['level-name'] = $get('level_name');
            if ($this->isPropertyAvailable('level_seed') && !is_null($get('level_seed'))) $props['level-seed'] = $get('level_seed');
            if ($this->isPropertyAvailable('level_type') && !is_null($get('level_type'))) $props['level-type'] = $get('level_type');
            if ($this->isPropertyAvailable('motd')) $props['motd'] = $get('motd') ?? 'A Minecraft Server';
            if ($this->isPropertyAvailable('network_compression_threshold')) $props['network-compression-threshold'] = $get('network_compression_threshold') ?? 256;
            if ($this->isPropertyAvailable('online_mode')) $props['online-mode'] = $get('online_mode', true) ? 'true' : 'false';
            if ($this->isPropertyAvailable('op_permission_level') && !is_null($get('op_permission_level'))) $props['op-permission-level'] = $get('op_permission_level');
            if ($this->isPropertyAvailable('pvp')) $props['pvp'] = $get('pvp', true) ? 'true' : 'false';
            if ($this->isPropertyAvailable('rcon_password') && !is_null($get('rcon_password'))) $props['rcon.password'] = $get('rcon_password');
            if ($this->isPropertyAvailable('server_port') && !is_null($get('server_port'))) $props['server-port'] = $get('server_port');
            if ($this->isPropertyAvailable('query_port') && !is_null($get('query_port'))) $props['query.port'] = $get('query_port');
            if ($this->isPropertyAvailable('simulation_distance') && !is_null($get('simulation_distance'))) $props['simulation-distance'] = $get('simulation_distance');
            if ($this->isPropertyAvailable('spawn_monsters')) $props['spawn-monsters'] = $get('spawn_monsters', false) ? 'true' : 'false';
            if ($this->isPropertyAvailable('spawn_protection')) $props['spawn-protection'] = $get('spawn_protection') ?? 0;
            if ($this->isPropertyAvailable('sync_chunk_writes') && !is_null($get('sync_chunk_writes'))) $props['sync-chunk-writes'] = $get('sync_chunk_writes') ? 'true' : 'false';
            if ($this->isPropertyAvailable('view_distance')) $props['view-distance'] = $get('view_distance') ?? 10;
            if ($this->isPropertyAvailable('whitelist')) $props['white-list'] = $get('whitelist', false) ? 'true' : 'false';

            $lines = [];
            $lines[] = '#Minecraft server properties';
            $lines[] = '#' . now()->toDateTimeString();

            foreach ($props as $key => $value) {
                $lines[] = $key . '=' . $value;
            }

            $content = implode("\n", $lines) . "\n";
        }

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
        $out = [];
        $lines = preg_split('/\r\n|\r|\n/', $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $out[$key] = $value;
            }
        }
        return $out;
    }
}