<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Extension;

use SilverShop\Model\Buyable;
use SilverShop\Wishlist\Model\SessionWishlist;
use SilverShop\Wishlist\Model\Wishlist;
use SilverStripe\Core\Extension;
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
     * The member's default wishlist, created on first use.
     */
    public function Wishlist(): Wishlist
    {
        /** @var Wishlist|null $list */
        $list = $this->owner->Wishlists()->first();
        if (!$list) {
            $list = Wishlist::create();
            $list->MemberID = $this->owner->ID;
            $list->write();
        }

        return $list;
    }

    /**
     * Whether a buyable is already on the member's wishlist (does not create an empty list).
     */
    public function hasInWishlist(Buyable $buyable): bool
    {
        /** @var Wishlist|null $list */
        $list = $this->owner->Wishlists()->first();

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
