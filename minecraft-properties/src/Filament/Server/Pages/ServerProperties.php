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

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->components([
                Section::make('Basic')
                    ->icon('tabler-info-circle')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(2)->schema([
                                TextInput::make('motd')->label('Server Message (motd)')->helperText('Shown in the server list.')->prefixIcon('tabler-chat'),
                                TextInput::make('max_players')->label('Max Players')->numeric()->minValue(0)->prefixIcon('tabler-users'),
                                Toggle::make('online_mode')->label('Online Mode'),
                                Toggle::make('enable_query')->label('Enable Query')->helperText('Allow Game Query (server list stats).'),
                                Toggle::make('enable_rcon')->label('Enable RCON'),
                            ]),
                        ]),
                    ]),

                Section::make('Gameplay')
                    ->icon('tabler-sword')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema([
                                Select::make('difficulty')->label('Difficulty')->options([
                                    'peaceful' => 'Peaceful',
                                    'easy' => 'Easy',
                                    'normal' => 'Normal',
                                    'hard' => 'Hard',
                                ])->default('normal'),
                                Select::make('gamemode')->label('Default Gamemode')->options([
                                    'survival' => 'Survival',
                                    'creative' => 'Creative',
                                    'adventure' => 'Adventure',
                                    'spectator' => 'Spectator',
                                ])->default('survival'),
                                Toggle::make('force_gamemode')->label('Force Gamemode'),
                                Toggle::make('hardcore')->label('Hardcore'),
                                Toggle::make('pvp')->label('PVP'),
                                Toggle::make('spawn_monsters')->label('Spawn Monsters'),
                            ]),
                        ]),
                    ]),

                Section::make('World')
                    ->icon('tabler-world')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema([
                                TextInput::make('level_name')->label('Level Name')->prefixIcon('tabler-file-text'),
                                TextInput::make('level_seed')->label('Level Seed')->prefixIcon('tabler-hash'),
                                TextInput::make('level_type')->label('Level Type')->prefixIcon('tabler-cube'),
                                TextInput::make('view_distance')->label('View Distance')->numeric()->minValue(2)->prefixIcon('tabler-eye'),
                                TextInput::make('spawn_protection')->label('Spawn Protection')->numeric()->minValue(0)->prefixIcon('tabler-shield-star'),
                            ]),
                        ]),
                    ]),

                Section::make('Network')
                    ->icon('tabler-network')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema([
                                TextInput::make('server_port')->label('Server Port')->numeric()->minValue(0)->prefixIcon('tabler-network'),
                                TextInput::make('query_port')->label('Query Port')->numeric()->minValue(0)->prefixIcon('tabler-network'),
                                TextInput::make('rcon_password')->label('RCON Password')->prefixIcon('tabler-key'),
                            ]),
                        ]),
                    ]),

                Section::make('Advanced & Raw')
                    ->icon('tabler-cog')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make()->columnSpanFull()->schema([
                            Grid::make()->columns(3)->schema([
                                TextInput::make('network_compression_threshold')->label('Network Compression Threshold')->numeric()->prefixIcon('tabler-arrows-merge'),
                                TextInput::make('max_tick_time')->label('Max Tick Time')->numeric()->prefixIcon('tabler-clock'),
                                Toggle::make('enable_command_block')->label('Enable Command Block'),
                                Toggle::make('allow_flight')->label('Allow Flight'),
                                Toggle::make('allow_nether')->label('Allow Nether'),
                                Textarea::make('raw')->label('Raw server.properties')->rows(12)->helperText('Advanced: edit the raw file directly')->columnSpanFull(),
                            ]),
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

        $lines = [];
        $lines[] = '#Minecraft server properties';
        $lines[] = '#' . now()->toDateTimeString();

        $lines[] = 'accepts-transfers=' . ($get('accepts_transfers', false) ? 'true' : 'false');
        $lines[] = 'allow-flight=' . ($get('allow_flight', false) ? 'true' : 'false');
        $lines[] = 'allow-nether=' . ($get('allow_nether', false) ? 'true' : 'false');
        $lines[] = 'broadcast-console-to-ops=' . ($get('broadcast_console_to_ops', false) ? 'true' : 'false');
        $lines[] = 'debug=' . ($get('debug', false) ? 'true' : 'false');
        if (! is_null($get('difficulty'))) $lines[] = 'difficulty=' . $get('difficulty');
        $lines[] = 'enable-command-block=' . ($get('enable_command_block', false) ? 'true' : 'false');
        $lines[] = 'enable-query=' . ($get('enable_query', false) ? 'true' : 'false');
        $lines[] = 'enable-rcon=' . ($get('enable_rcon', false) ? 'true' : 'false');
        $lines[] = 'enable-status=true';
        $lines[] = 'force-gamemode=' . ($get('force_gamemode', false) ? 'true' : 'false');
        $lines[] = 'gamemode=' . ($get('gamemode') ?? 'survival');
        $lines[] = 'hardcore=' . ($get('hardcore', false) ? 'true' : 'false');
        $lines[] = 'max-players=' . ($get('max_players') ?? 20);
        if (! is_null($get('max_tick_time'))) $lines[] = 'max-tick-time=' . $get('max_tick_time');
        if (! is_null($get('level_name'))) $lines[] = 'level-name=' . $get('level_name');
        if (! is_null($get('level_seed'))) $lines[] = 'level-seed=' . $get('level_seed');
        if (! is_null($get('level_type'))) $lines[] = 'level-type=' . $get('level_type');
        $lines[] = 'motd=' . ($get('motd') ?? 'A Minecraft Server');
        $lines[] = 'network-compression-threshold=' . ($get('network_compression_threshold') ?? 256);
        $lines[] = 'online-mode=' . ($get('online_mode', true) ? 'true' : 'false');
        if (! is_null($get('op_permission_level'))) $lines[] = 'op-permission-level=' . $get('op_permission_level');
        $lines[] = 'pvp=' . ($get('pvp', true) ? 'true' : 'false');
        if (! is_null($get('rcon_password'))) $lines[] = 'rcon.password=' . $get('rcon_password');
        if (! is_null($get('server_port'))) $lines[] = 'server-port=' . $get('server_port');
        if (! is_null($get('query_port'))) $lines[] = 'query.port=' . $get('query_port');
        if (! is_null($get('simulation_distance'))) $lines[] = 'simulation-distance=' . $get('simulation_distance');
        $lines[] = 'spawn-monsters=' . ($get('spawn_monsters', false) ? 'true' : 'false');
        $lines[] = 'spawn-protection=' . ($get('spawn_protection') ?? 0);
        if (! is_null($get('sync_chunk_writes'))) $lines[] = 'sync-chunk-writes=' . ($get('sync_chunk_writes') ? 'true' : 'false');
        $lines[] = 'view-distance=' . ($get('view_distance') ?? 10);
        $lines[] = 'white-list=' . ($get('whitelist', false) ? 'true' : 'false');

        $content = implode("\n", $lines) . "\n";

        try {
            $repo = app(DaemonFileRepository::class)->setServer($server);
            try {
                $existing = $repo->getContent('server.properties');
            } catch (\Throwable $e) {
                $existing = null;
            }

            if (! is_null($existing)) {
                $ts = now()->format('Ymd_His');
                $repo->putContent("server.properties.bak.$ts", $existing);
            }

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
