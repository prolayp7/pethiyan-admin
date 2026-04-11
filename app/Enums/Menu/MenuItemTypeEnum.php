<?php

namespace App\Enums\Menu;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum MenuItemTypeEnum: string
{
    use InvokableCases, Values, Names;

    case LINK          = 'link';
    case SHOP_DROPDOWN = 'shop_dropdown';
    case MEGA_MENU     = 'mega_menu';
    case HEADING       = 'heading';
    case DIVIDER       = 'divider';

    public function label(): string
    {
        return match ($this) {
            self::LINK          => 'Link',
            self::SHOP_DROPDOWN => 'Shop Dropdown',
            self::MEGA_MENU     => 'Mega Menu',
            self::HEADING       => 'Heading',
            self::DIVIDER       => 'Divider',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::LINK          => 'bg-blue-lt',
            self::SHOP_DROPDOWN => 'bg-purple-lt',
            self::MEGA_MENU     => 'bg-orange-lt',
            self::HEADING       => 'bg-gray-lt',
            self::DIVIDER       => 'bg-secondary-lt',
        };
    }
}
