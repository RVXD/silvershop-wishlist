<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Model;

use SilverShop\Model\Buyable;
use SilverShop\Model\Variation\Variation;
use SilverShop\Page\Product;
use SilverStripe\ORM\DataObject;

/**
 * One saved buyable on a {@link Wishlist} — a whole {@link Product}, or a specific {@link Variation} of it.
 *
 * @property int $Quantity
 * @property int $SortOrder
 * @property string $Note
 * @property float $AddedPrice
 * @property int $WishlistID
 * @property int $ProductID
 * @property int $VariationID
 * @method Wishlist Wishlist()
 * @method Product Product()
 * @method Variation Variation()
 */
class WishlistItem extends DataObject
{
    private static string $table_name = 'SilverShop_WishlistItem';

    /**
     * @var array<string, string>
     */
    private static array $db = [
        'Quantity' => 'Int',
        'SortOrder' => 'Int',
        'Note' => 'Varchar(255)',
        // The buyable's price when it was added — powers the "price dropped" badge.
        'AddedPrice' => 'Currency',
    ];

    /**
     * @var array<string, string>
     */
    private static array $has_one = [
        'Wishlist' => Wishlist::class,
        'Product' => Product::class,
        'Variation' => Variation::class,
    ];

    /**
     * @var array<string, int>
     */
    private static array $defaults = [
        'Quantity' => 1,
    ];

    private static string $default_sort = 'SortOrder ASC, Created DESC';

    /**
     * @var array<string, string>
     */
    private static array $casting = [
        'Price' => 'Currency',
        'BuyableTitle' => 'Varchar',
    ];

    /**
     * The buyable this line resolves to: the saved {@link Variation} when set, else the whole {@link Product}.
     */
    public function Buyable(): ?Buyable
    {
        if ($this->VariationID) {
            $variation = $this->Variation();
            if ($variation && $variation->exists()) {
                return $variation;
            }
        }

        $product = $this->Product();

        return ($product && $product->exists()) ? $product : null;
    }

    /**
     * The buyable's current selling price (cast to Currency for templates).
     */
    public function getPrice(): float
    {
        $buyable = $this->Buyable();

        return $buyable ? $buyable->sellingPrice() : 0.0;
    }

    public function getAvailable(): bool
    {
        $buyable = $this->Buyable();

        return $buyable ? $buyable->canPurchase() : false;
    }

    /**
     * True when the buyable now costs less than it did when it was added.
     */
    public function getPriceDropped(): bool
    {
        return (float) $this->AddedPrice > 0 && $this->getPrice() < (float) $this->AddedPrice;
    }

    /**
     * Display title — the variation's title when set, otherwise the product's.
     */
    public function getBuyableTitle(): string
    {
        if ($this->VariationID) {
            $variation = $this->Variation();
            if ($variation && $variation->exists()) {
                return (string) $variation->getTitle();
            }
        }

        $product = $this->Product();

        return ($product && $product->exists()) ? (string) $product->getTitle() : '';
    }

    public function canView($member = null): bool
    {
        $wishlist = $this->Wishlist();

        return ($wishlist && $wishlist->exists()) ? $wishlist->canView($member) : false;
    }

    public function canEdit($member = null): bool
    {
        $wishlist = $this->Wishlist();

        return ($wishlist && $wishlist->exists()) ? $wishlist->canEdit($member) : false;
    }

    public function canDelete($member = null): bool
    {
        return $this->canEdit($member);
    }

    public function canCreate($member = null, $context = []): bool
    {
        $wishlist = $this->Wishlist();

        return ($wishlist && $wishlist->exists()) ? $wishlist->canEdit($member) : false;
    }
}
