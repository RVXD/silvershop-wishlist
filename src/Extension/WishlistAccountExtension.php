<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Extension;

use SilverShop\Page\AccountPageController;
use SilverShop\Wishlist\Control\WishlistController;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBHTMLText;
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
