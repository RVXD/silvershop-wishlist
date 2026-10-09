<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Extension;

use SilverShop\Model\Buyable;
use SilverShop\Wishlist\Model\SessionWishlist;
use SilverShop\Wishlist\Model\Wishlist;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Member;

/**
 * Gives every {@link Member} a wishlist.
 *
 * @extends Extension<Member>
 */
class WishlistMemberExtension extends Extension
{
    /**
     * @var array<string, string>
     */
    private static array $has_many = [
        'Wishlists' => Wishlist::class . '.Member',
    ];

    /**
     * @var array<string>
     */
    private static array $cascade_deletes = [
        'Wishlists',
    ];

    /**
     * The member's default wishlist — the oldest one — created on first use. Staying with the oldest keeps the
     * default stable as further named lists are added (multiple-lists mode).
     */
    public function Wishlist(): Wishlist
    {
        /** @var Wishlist|null $list */
        $list = $this->owner->Wishlists()->sort('Created ASC, ID ASC')->first();
        if (!$list) {
            $list = Wishlist::create();
            $list->MemberID = $this->owner->ID;
            $list->write();
        }

        return $list;
    }

    /**
     * One of the member's own lists by ID, or null when it is not theirs (or the id is empty).
     */
    public function WishlistByID(int $id): ?Wishlist
    {
        if ($id <= 0) {
            return null;
        }

        /** @var Wishlist|null $list */
        $list = $this->owner->Wishlists()->byID($id);

        return $list;
    }

    /**
     * The member's lists, oldest (the default) first — for the account area and the product-page picker.
     *
     * @return DataList<Wishlist>
     */
    public function OrderedWishlists(): DataList
    {
        /** @var DataList<Wishlist> $lists */
        $lists = Wishlist::get()
            ->filter('MemberID', (int) $this->owner->ID)
            ->sort('Created ASC, ID ASC');

        return $lists;
    }

    /**
     * Whether a buyable is already on the member's default wishlist (does not create an empty list).
     */
    public function hasInWishlist(Buyable $buyable): bool
    {
        /** @var Wishlist|null $list */
        $list = $this->owner->Wishlists()->sort('Created ASC, ID ASC')->first();

        return $list ? $list->HasBuyable($buyable) : false;
    }

    /**
     * On login, fold any guest (session) wishlist into the member's list, then clear the session.
     */
    public function onAfterMemberLoggedIn(): void
    {
        $this->mergeSessionWishlist();
    }

    public function memberAutoLoggedIn(): void
    {
        $this->mergeSessionWishlist();
    }

    private function mergeSessionWishlist(): void
    {
        $session = SessionWishlist::create();
        $rows = $session->rows();
        if ($rows === []) {
            return;
        }

        $wishlist = $this->Wishlist();
        foreach ($rows as $row) {
            $buyable = SessionWishlist::resolveBuyable($row);
            if ($buyable) {
                $wishlist->AddBuyable($buyable, (int) ($row['Quantity'] ?? 1));
            }
        }

        $session->clear();
    }
}
