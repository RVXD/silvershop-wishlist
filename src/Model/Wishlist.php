<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Model;

use SilverShop\Cart\ShoppingCart;
use SilverShop\Model\Buyable;
use SilverShop\Model\Variation\Variation;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * A member's wishlist — a container of saved buyables. v1 uses one default list per member; the container
 * is modelled so later tiers (multiple named lists, sharing) attach here without reshaping items.
 *
 * @property string $Title
 * @property int $MemberID
 * @method Member Member()
 * @method HasManyList<WishlistItem> Items()
 */
class Wishlist extends DataObject implements WishlistHandler
{
    private static string $table_name = 'SilverShop_Wishlist';

    /**
     * @var array<string, string>
     */
    private static array $db = [
        'Title' => 'Varchar(255)',
    ];

    /**
     * @var array<string, string>
     */
    private static array $has_one = [
        'Member' => Member::class,
    ];

    /**
     * @var array<string, string>
     */
    private static array $has_many = [
        'Items' => WishlistItem::class,
    ];

    /**
     * @var array<string>
     */
    private static array $cascade_deletes = [
        'Items',
    ];

    /**
     * @var array<string, string>
     */
    private static array $defaults = [
        'Title' => 'My wishlist',
    ];

    private static string $default_sort = 'Created DESC';

    /**
     * The (ProductID, VariationID) that identify a buyable on the list.
     *
     * @return array{ProductID: int, VariationID: int}
     */
    private static function keysFor(Buyable $buyable): array
    {
        if ($buyable instanceof Variation) {
            return ['ProductID' => (int) $buyable->ProductID, 'VariationID' => (int) $buyable->ID];
        }

        $id = $buyable instanceof DataObject ? (int) $buyable->ID : 0;

        return ['ProductID' => $id, 'VariationID' => 0];
    }

    public function ItemFor(Buyable $buyable): ?WishlistItem
    {
        return $this->Items()->filter(self::keysFor($buyable))->first();
    }

    public function HasBuyable(Buyable $buyable): bool
    {
        return (bool) $this->ItemFor($buyable);
    }

    /**
     * Add a buyable to the list (idempotent — returns the existing item if already present).
     */
    public function AddBuyable(Buyable $buyable, int $quantity = 1): WishlistItem
    {
        if (!$this->isInDB()) {
            $this->write();
        }

        $item = $this->ItemFor($buyable);
        if ($item) {
            return $item;
        }

        $keys = self::keysFor($buyable);
        $item = WishlistItem::create();
        $item->WishlistID = $this->ID;
        $item->ProductID = $keys['ProductID'];
        $item->VariationID = $keys['VariationID'];
        $item->Quantity = max(1, $quantity);
        $item->AddedPrice = $buyable->sellingPrice();
        $item->write();

        $this->extend('onAddToWishlist', $item, $buyable);

        return $item;
    }

    public function RemoveBuyable(Buyable $buyable): void
    {
        $item = $this->ItemFor($buyable);
        if ($item) {
            $this->extend('onRemoveFromWishlist', $item, $buyable);
            $item->delete();
        }
    }

    /**
     * Remove the buyable if present, add it otherwise. Returns true when it is now on the list.
     */
    public function ToggleBuyable(Buyable $buyable, int $quantity = 1): bool
    {
        if ($this->HasBuyable($buyable)) {
            $this->RemoveBuyable($buyable);

            return false;
        }

        $this->AddBuyable($buyable, $quantity);

        return true;
    }

    /**
     * Add every item to the cart, optionally removing it from the list afterwards.
     */
    public function MoveAllToCart(bool $removeAfter = false): void
    {
        foreach ($this->Items() as $item) {
            $buyable = $item->Buyable();
            if ($buyable) {
                ShoppingCart::singleton()->add($buyable, (int) $item->Quantity);
                if ($removeAfter) {
                    $item->delete();
                }
            }
        }
    }

    public function canView($member = null): bool
    {
        return $this->ownedBy($member);
    }

    public function canEdit($member = null): bool
    {
        return $this->ownedBy($member);
    }

    public function canDelete($member = null): bool
    {
        return $this->ownedBy($member);
    }

    public function canCreate($member = null, $context = []): bool
    {
        return (bool) ($member ?? Security::getCurrentUser());
    }

    private function ownedBy(?Member $member): bool
    {
        $member = $member ?? Security::getCurrentUser();
        if (!$member) {
            return false;
        }
        if (Permission::check('ADMIN', 'any', $member)) {
            return true;
        }

        return $this->MemberID && (int) $this->MemberID === (int) $member->ID;
    }
}
