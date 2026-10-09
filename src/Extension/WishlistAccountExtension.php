<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Extension;

use SilverShop\Page\AccountPageController;
use SilverShop\Wishlist\Control\WishlistController;
use SilverShop\Wishlist\Model\Wishlist;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;

/**
 * Adds a "wishlist" section to the account area (rendered by AccountPage_wishlist.ss) plus the links and the
 * CSRF field its move-to-cart / remove forms need.
 *
 * @extends Extension<AccountPageController>
 */
class WishlistAccountExtension extends Extension
{
    /**
     * @var array<string>
     */
    private static array $allowed_actions = [
        'wishlist',
    ];

    /**
     * @return array<string, mixed>
     */
    public function wishlist(): array
    {
        // Renders AccountPage_wishlist.ss; the template reads $CurrentMember.Wishlist.Items.
        return [];
    }

    /**
     * Whether members may keep several named lists (mirrors the controller config) — drives the account UI.
     */
    public function AllowMultipleLists(): bool
    {
        return (bool) WishlistController::config()->get('allow_multiple_lists');
    }

    /**
     * The current member's lists, oldest (the default) first, for the account template.
     *
     * @return DataList<Wishlist>|null
     */
    public function WishlistLists(): ?DataList
    {
        $member = Security::getCurrentUser();

        return $member ? $member->OrderedWishlists() : null;
    }

    public function WishlistLink(): string
    {
        return $this->owner->Link('wishlist');
    }

    public function WishlistCartLink(): string
    {
        return WishlistController::singleton()->Link('movetocart');
    }

    public function WishlistRemoveLink(): string
    {
        return WishlistController::singleton()->Link('remove');
    }

    public function WishlistMoveAllLink(): string
    {
        return WishlistController::singleton()->Link('moveall');
    }

    /**
     * Whether members may share a list as a public read-only link (mirrors the controller config).
     */
    public function AllowSharing(): bool
    {
        return (bool) WishlistController::config()->get('allow_sharing');
    }

    public function WishlistShareLink(): string
    {
        return WishlistController::singleton()->Link('share');
    }

    public function WishlistUnshareLink(): string
    {
        return WishlistController::singleton()->Link('unshare');
    }

    public function WishlistCreateLink(): string
    {
        return WishlistController::singleton()->Link('createlist');
    }

    public function WishlistRenameLink(): string
    {
        return WishlistController::singleton()->Link('renamelist');
    }

    public function WishlistDeleteLink(): string
    {
        return WishlistController::singleton()->Link('deletelist');
    }

    public function WishlistSecurityField(): DBHTMLText
    {
        $token = SecurityToken::inst();
        $field = DBHTMLText::create();
        $field->setValue(sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            Convert::raw2att($token->getName()),
            Convert::raw2att($token->getValue())
        ));

        return $field;
    }
}
