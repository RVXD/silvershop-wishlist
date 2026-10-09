<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Model;

use SilverShop\Model\Buyable;

/**
 * The operations a wishlist supports, shared by the member-owned {@link Wishlist} (database) and the
 * {@link SessionWishlist} (guest, session-backed) so the controller can treat them the same.
 */
interface WishlistHandler
{
    public function HasBuyable(Buyable $buyable): bool;

    public function AddBuyable(Buyable $buyable, int $quantity = 1);

    public function RemoveBuyable(Buyable $buyable): void;

    public function ToggleBuyable(Buyable $buyable, int $quantity = 1): bool;

    public function MoveAllToCart(bool $removeAfter = false): void;
}
