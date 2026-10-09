<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Extension;

use SilverShop\Page\Product;
use SilverShop\Page\ProductController;
use SilverShop\Wishlist\Control\WishlistController;
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
        $member = Security::getCurrentUser();
        if (!$member) {
            return false;
        }

        $product = $this->owner->data();

        return $product instanceof Product ? $member->hasInWishlist($product) : false;
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
