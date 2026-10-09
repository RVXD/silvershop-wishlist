<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Model;

use SilverShop\Cart\ShoppingCart;
use SilverShop\Model\Buyable;
use SilverShop\Model\Variation\Variation;
use SilverShop\Page\Product;
use SilverShop\ShopTools;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;

/**
 * A guest wishlist held in the session. Same operations as the member {@link Wishlist}; on login its
 * contents are merged into the member's list (see WishlistMemberExtension) and the session is cleared.
 *
 * Rows are keyed "ProductID:VariationID" and store {ProductID, VariationID, Quantity}.
 */
class SessionWishlist implements WishlistHandler
{
    use Injectable;

    public const SESSION_KEY = 'SilverShopWishlist';

    /**
     * @return array<string, array{ProductID: int, VariationID: int, Quantity: int}>
     */
    private function read(): array
    {
        $data = ShopTools::getSession()->get(self::SESSION_KEY);

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, array{ProductID: int, VariationID: int, Quantity: int}> $rows
     */
    private function save(array $rows): void
    {
        ShopTools::getSession()->set(self::SESSION_KEY, $rows);
    }

    private static function keyFor(Buyable $buyable): string
    {
        $row = self::rowFor($buyable, 1);

        return $row['ProductID'] . ':' . $row['VariationID'];
    }

    /**
     * @return array{ProductID: int, VariationID: int, Quantity: int}
     */
    private static function rowFor(Buyable $buyable, int $quantity): array
    {
        if ($buyable instanceof Variation) {
            return [
                'ProductID' => (int) $buyable->ProductID,
                'VariationID' => (int) $buyable->ID,
                'Quantity' => max(1, $quantity),
            ];
        }

        $id = $buyable instanceof DataObject ? (int) $buyable->ID : 0;

        return ['ProductID' => $id, 'VariationID' => 0, 'Quantity' => max(1, $quantity)];
    }

    /**
     * @param array{ProductID?: int, VariationID?: int, Quantity?: int} $row
     */
    public static function resolveBuyable(array $row): ?Buyable
    {
        if (!empty($row['VariationID'])) {
            return Variation::get()->byID((int) $row['VariationID']);
        }
        if (!empty($row['ProductID'])) {
            return Product::get()->byID((int) $row['ProductID']);
        }

        return null;
    }

    public function HasBuyable(Buyable $buyable): bool
    {
        return isset($this->read()[self::keyFor($buyable)]);
    }

    public function AddBuyable(Buyable $buyable, int $quantity = 1)
    {
        $rows = $this->read();
        $key = self::keyFor($buyable);
        if (!isset($rows[$key])) {
            $rows[$key] = self::rowFor($buyable, $quantity);
            $this->save($rows);
        }
    }

    public function RemoveBuyable(Buyable $buyable): void
    {
        $rows = $this->read();
        unset($rows[self::keyFor($buyable)]);
        $this->save($rows);
    }

    public function ToggleBuyable(Buyable $buyable, int $quantity = 1): bool
    {
        if ($this->HasBuyable($buyable)) {
            $this->RemoveBuyable($buyable);

            return false;
        }

        $this->AddBuyable($buyable, $quantity);

        return true;
    }

    public function MoveAllToCart(bool $removeAfter = false): void
    {
        foreach ($this->read() as $row) {
            $buyable = self::resolveBuyable($row);
            if ($buyable) {
                ShoppingCart::singleton()->add($buyable, (int) $row['Quantity']);
            }
        }

        if ($removeAfter) {
            $this->clear();
        }
    }

    /**
     * @return array<string, array{ProductID: int, VariationID: int, Quantity: int}>
     */
    public function rows(): array
    {
        return $this->read();
    }

    public function count(): int
    {
        return count($this->read());
    }

    public function clear(): void
    {
        $this->save([]);
    }
}
