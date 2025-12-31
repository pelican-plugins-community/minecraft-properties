<?php

namespace Pelican\MinecraftProperties\Filament\Server\Pages\Concerns;

use Pelican\MinecraftProperties\Services\PropertiesService;

/**
 * Trait providing the public properties used by the ServerProperties form.
 * These are the individual fields that Filament binds to for form state.
 */
trait ServerPropertiesFields
{
    use PropertiesMapping;

    // Basic fields
    public $motd;
    public $max_players;
    public $online_mode;
    public $enable_query;
    public $enable_rcon;
    public $enable_status;

    // Gameplay fields
    public $difficulty;
    public $gamemode;
    public $force_gamemode;
    public $hardcore;
    public $pvp;
    public $spawn_monsters;
    public $spawn_animals;
    public $spawn_npcs;

    // World fields
    public $level_name;
    public $level_seed;
    public $level_type;
    public $view_distance;
    public $spawn_protection;
    public $generate_structures;
    public $generator_settings;

    // Network fields
    public $server_port;
    public $query_port;
    public $rcon_password;
    public $rcon_port;
    public $server_ip;

    // Advanced fields
    public $network_compression_threshold;
    public $max_tick_time;
    public $enable_command_block;
    public $allow_flight;
    public $allow_nether;
    public $accepts_transfers;
    public $broadcast_console_to_ops;
    public $debug;
    public $op_permission_level;
    public $simulation_distance;
    public $sync_chunk_writes;
    public $whitelist;
    public $enable_jmx_monitoring;
    public $enforce_secure_profile;
    public $enforce_whitelist;
    public $entity_broadcast_range_percentage;
    public $function_permission_level;
    public $hide_online_players;
    public $initial_disabled_packs;
    public $initial_enabled_packs;
    public $log_ips;
    public $max_chained_neighbor_updates;
    public $max_world_size;
    public $player_idle_timeout;
    public $prevent_proxy_connections;
    public $rate_limit;
    public $resource_pack;
    public $resource_pack_id;
    public $resource_pack_prompt;
    public $resource_pack_sha1;
    public $text_filtering_config;
    public $use_native_transport;

    // Raw content for the textarea
    public $raw;

    /** @var array<string,mixed> */
    private array $originalData = [];
    private string $originalRaw = '';

    /**
     * List of property keys that actually exist in the loaded server.properties
     * (e.g. ['motd', 'max-players', ...]). Used to limit which form components
     * are shown/processed.
     *
     * @var string[]
     */
    private array $availableProperties = [];

    /**
     * Parsed original properties (key => value) from the server.properties
     * file as it was loaded; used as a base when mapping form state back to
     * file content.
     *
     * @var array<string,string>
     */
    private array $originalProps = [];

    private PropertiesService $propertiesService;

    protected function initializePropertiesService(): void
    {
        $this->propertiesService = new PropertiesService(
            $this->propertyMapping,
            $this->fieldTypes,
            $this->defaultValues
        );
    }
}