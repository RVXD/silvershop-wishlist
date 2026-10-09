<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Control;

use SilverShop\Cart\ShoppingCart;
use SilverShop\Model\Buyable;
use SilverShop\Model\Variation\Variation;
use SilverShop\Page\Product;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;

/**
 * Front-end endpoints for a member's wishlist: add / remove / toggle a buyable, and move items to the cart.
 * Every action is POST-only and CSRF-checked (mirroring the hardened review-vote controller), then
 * redirects back. Guests are sent to the login screen.
 */
class WishlistController extends Controller
{
    use Configurable;

    private static string $url_segment = 'wishlist';

    /**
     * Remove an item from the wishlist once it has been moved to the cart.
     */
    private static bool $remove_on_add_to_cart = false;

    /**
     * @var array<string>
     */
    private static array $allowed_actions = [
        'add',
        'remove',
        'toggle',
        'movetocart',
        'moveall',
    ];

    public function Link($action = null): string
    {
        return Controller::join_links(self::config()->get('url_segment'), $action);
    }

    public function add(HTTPRequest $request)
    {
        return $this->mutate($request, 'add');
    }

    public function remove(HTTPRequest $request)
    {
        return $this->mutate($request, 'remove');
    }

    public function toggle(HTTPRequest $request)
    {
        return $this->mutate($request, 'toggle');
    }

    public function movetocart(HTTPRequest $request)
    {
        return $this->mutate($request, 'movetocart');
    }

    public function moveall(HTTPRequest $request)
    {
        return $this->mutate($request, 'moveall');
    }

    protected function mutate(HTTPRequest $request, string $op)
    {
        if (!$request->isPOST() || !SecurityToken::inst()->checkRequest($request)) {
            return $this->httpError(400);
        }

        $member = Security::getCurrentUser();
        if (!$member) {
            $backURL = urlencode((string) $request->getHeader('Referer'));

            return $this->redirect(Controller::join_links(Security::login_url(), '?BackURL=' . $backURL));
        }

        $wishlist = $member->Wishlist();

        if ($op === 'moveall') {
            foreach ($wishlist->Items() as $item) {
                $buyable = $item->Buyable();
                if ($buyable) {
                    ShoppingCart::singleton()->add($buyable, (int) $item->Quantity);
                    if (self::config()->get('remove_on_add_to_cart')) {
                        $item->delete();
                    }
                }
            }

            return $this->back($request);
        }

        $buyable = $this->buyableFromRequest($request);
        if (!$buyable) {
            return $this->httpError(404);
        }

        switch ($op) {
            case 'add':
                $wishlist->AddBuyable($buyable, $this->requestedQuantity($request));
                break;
            case 'remove':
                $wishlist->RemoveBuyable($buyable);
                break;
            case 'toggle':
                $wishlist->ToggleBuyable($buyable, $this->requestedQuantity($request));
                break;
            case 'movetocart':
                ShoppingCart::singleton()->add($buyable, $this->requestedQuantity($request));
                if (self::config()->get('remove_on_add_to_cart')) {
                    $wishlist->RemoveBuyable($buyable);
                }
                break;
        }

        return $this->back($request);
    }

    protected function buyableFromRequest(HTTPRequest $request): ?Buyable
    {
        $variationID = (int) $request->postVar('VariationID');
        if ($variationID) {
            return Variation::get()->byID($variationID);
        }

        $productID = (int) $request->postVar('ProductID');
        if ($productID) {
            return Product::get()->byID($productID);
        }

        return null;
    }

    protected function requestedQuantity(HTTPRequest $request): int
    {
        return max(1, (int) $request->postVar('Quantity'));
    }

    protected function back(HTTPRequest $request)
    {
        $this->extend('updateWishlistResponse', $request);

        return $this->redirectBack();
    }
}
