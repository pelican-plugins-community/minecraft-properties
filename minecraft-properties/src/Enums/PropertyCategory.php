<?php

namespace Pelican\MinecraftProperties\Enums;

enum PropertyCategory: string
{
    case BASIC = 'basic';
    case GAMEPLAY = 'gameplay';
    case WORLD = 'world';
    case NETWORK = 'network';
    case ADVANCED = 'advanced';
    case OTHER = 'other';
    case ALL = 'all';
}
