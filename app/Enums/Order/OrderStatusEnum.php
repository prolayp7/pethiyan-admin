<?php

namespace App\Enums\Order;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static PENDING()
 * @method static AWAITING_STORE_RESPONSE()
 * @method static PREPARING()
 * @method static COLLECTED()
 * @method static DELIVERED()
 * @method static CANCELLED()
 * @method static FAILED()
 * @method static PARTIALLY_ACCEPTED()
 * @method static REJECTED_BY_SELLER()
 * @method static ACCEPTED_BY_SELLER()
 * @method static READY_FOR_PICKUP()
 * @method static ASSIGNED()
 * @method static OUT_FOR_DELIVERY()
 */
enum OrderStatusEnum: string
{
    use InvokableCases, Names, Values;

    case PENDING = 'pending';
    case AWAITING_STORE_RESPONSE = "awaiting_store_response";
    case PARTIALLY_ACCEPTED = "partially_accepted";
    case REJECTED_BY_SELLER = "rejected_by_seller";
    case ACCEPTED_BY_SELLER = "accepted_by_seller";
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case ASSIGNED = 'assigned';
    case PREPARING = 'preparing';
    case COLLECTED = 'collected';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::ACCEPTED_BY_SELLER  => 'Order Accepted',
            self::PREPARING           => 'Order Start Packing',
            self::READY_FOR_PICKUP    => 'Order Packing Done',
            self::ASSIGNED            => 'Order Ready for Pickup',
            self::COLLECTED           => 'Order Collected',
            self::CANCELLED           => 'Order Cancelled',
            self::FAILED              => 'Order Failed',
            self::DELIVERED           => 'Order Dispatched',
            default                   => \Illuminate\Support\Str::headline($this->value),
        };
    }
}
