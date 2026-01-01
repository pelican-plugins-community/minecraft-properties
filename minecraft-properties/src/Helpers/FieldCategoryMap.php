<?php

namespace Pelican\MinecraftProperties\Helpers;

use Illuminate\Support\Collection;
use Pelican\MinecraftProperties\Enums\PropertyCategory;

class FieldCategoryMap
{
    public static function fields(PropertyCategory $category): array
    {
        return match ($category) {
            PropertyCategory::BASIC => [
                'motd', 'max_players', 'online_mode', 'enable_query', 'enable_rcon', 'enable_status'
            ],
            PropertyCategory::GAMEPLAY => [
                'difficulty', 'gamemode', 'force_gamemode', 'hardcore', 'pvp', 'spawn_monsters', 'spawn_animals', 'spawn_npcs'
            ],
            PropertyCategory::WORLD => [
                'level_name', 'level_seed', 'level_type', 'view_distance', 'spawn_protection', 'generate_structures', 'generator_settings'
            ],
            PropertyCategory::NETWORK => [
                'server_port', 'query_port', 'rcon_password', 'rcon_port', 'server_ip'
            ],
            PropertyCategory::ADVANCED => [
                'network_compression_threshold', 'max_tick_time', 'enable_command_block', 'allow_flight', 'allow_nether', 'accepts_transfers', 'broadcast_console_to_ops', 'debug', 'op_permission_level', 'simulation_distance', 'sync_chunk_writes', 'whitelist', 'enable_jmx_monitoring', 'enforce_secure_profile', 'enforce_whitelist', 'entity_broadcast_range_percentage', 'function_permission_level', 'hide_online_players', 'initial_disabled_packs', 'initial_enabled_packs', 'log_ips', 'max_chained_neighbor_updates', 'max_world_size', 'player_idle_timeout', 'prevent_proxy_connections', 'rate_limit', 'resource_pack', 'resource_pack_id', 'resource_pack_prompt', 'resource_pack_sha1', 'text_filtering_config', 'use_native_transport'
            ],
            PropertyCategory::ALL => [

                'motd', 'max_players', 'online_mode', 'pvp', 'difficulty', 'gamemode', 'view_distance', 'spawn_protection', 'accepts_transfers', 'allow_flight', 'broadcast_console_to_ops', 'debug', 'allow_nether', 'enable_command_block', 'enable_query', 'enable_rcon', 'force_gamemode', 'hardcore', 'level_name', 'level_seed', 'level_type', 'max_tick_time', 'network_compression_threshold', 'op_permission_level', 'rcon_password', 'server_port', 'simulation_distance', 'spawn_monsters', 'sync_chunk_writes', 'query_port', 'whitelist', 'enable_jmx_monitoring', 'enable_status', 'enforce_secure_profile', 'enforce_whitelist', 'entity_broadcast_range_percentage', 'function_permission_level', 'generate_structures', 'generator_settings', 'hide_online_players', 'initial_disabled_packs', 'initial_enabled_packs', 'log_ips', 'max_chained_neighbor_updates', 'max_world_size', 'player_idle_timeout', 'prevent_proxy_connections', 'rate_limit', 'rcon_port', 'resource_pack', 'resource_pack_id', 'resource_pack_prompt', 'resource_pack_sha1', 'server_ip', 'spawn_animals', 'spawn_npcs', 'text_filtering_config', 'use_native_transport'
            ],
            PropertyCategory::OTHER => []
        };
    }

    public static function all(): Collection
    {
        return collect(self::fields(PropertyCategory::ALL));
    }
}
