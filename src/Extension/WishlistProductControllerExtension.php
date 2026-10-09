<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Extension;

use SilverShop\Page\AccountPage;
use SilverShop\Page\Product;
use SilverShop\Page\ProductController;
use SilverShop\Wishlist\Control\WishlistController;
use SilverShop\Wishlist\Model\SessionWishlist;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;

/**
 * Exposes the wishlist state + toggle link on the product page (for the WishlistButton include).
 *
 * @extends Extension<ProductController>
 */
class WishlistProductControllerExtension extends Extension
{
    public function InWishlist(): bool
    {
        $product = $this->owner->data();
        if (!$product instanceof Product) {
            return false;
        }

        $member = Security::getCurrentUser();
        if ($member) {
            return $member->hasInWishlist($product);
        }

        return WishlistController::config()->get('allow_guest')
            ? SessionWishlist::create()->HasBuyable($product)
            : false;
    }

    /**
     * Whether the wishlist button should show at all (a member, or guests allowed).
     */
    public function WishlistEnabled(): bool
    {
        return Security::getCurrentUser() !== null
            || (bool) WishlistController::config()->get('allow_guest');
    }

    /**
     * Link to the member's wishlist in the account area (empty for guests — they have no wishlist page).
     */
    public function WishlistPageLink(): string
    {
        if (!Security::getCurrentUser()) {
            return '';
        }

        $account = AccountPage::get()->first();

        return $account ? (string) $account->Link('wishlist') : '';
    }

    public function WishlistLink(string $action = 'toggle'): string
    {
        return WishlistController::singleton()->Link($action);
    }

    /**
     * Hidden CSRF field for the no-JS wishlist POST form.
     */
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
