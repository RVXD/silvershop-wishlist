<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Control;

use SilverShop\Cart\ShoppingCart;
use SilverShop\Model\Buyable;
use SilverShop\Model\Variation\Variation;
use SilverShop\Page\Product;
use SilverShop\Wishlist\Model\SessionWishlist;
use SilverShop\Wishlist\Model\Wishlist;
use SilverShop\Wishlist\Model\WishlistHandler;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;

/**
 * Front-end endpoints for a wishlist: add / remove / toggle a buyable, and move items to the cart. Every
 * action is POST-only and CSRF-checked (mirroring the hardened review-vote controller), then redirects back.
 * Logged-in members use their database list; guests use a session list (when allow_guest is on, the default)
 * that is merged into their account on login — otherwise guests are sent to the login screen.
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
     * Let guests build a session wishlist (merged into their account on login). When false, guests are sent
     * to the login screen instead.
     */
    private static bool $allow_guest = true;

    /**
     * Let members keep several named wishlists (create / rename / delete, and choose which one to add to).
     * When false (the default), every member has a single list and the list-management actions are disabled.
     */
    private static bool $allow_multiple_lists = false;

    /**
     * @var array<string>
     */
    private static array $allowed_actions = [
        'add',
        'remove',
        'toggle',
        'movetocart',
        'moveall',
        'createlist',
        'renamelist',
        'deletelist',
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

    public function createlist(HTTPRequest $request)
    {
        return $this->manageList($request, 'create');
    }

    public function renamelist(HTTPRequest $request)
    {
        return $this->manageList($request, 'rename');
    }

    public function deletelist(HTTPRequest $request)
    {
        return $this->manageList($request, 'delete');
    }

    protected function mutate(HTTPRequest $request, string $op)
    {
        if (!$request->isPOST() || !SecurityToken::inst()->checkRequest($request)) {
            return $this->httpError(400);
        }

        $handler = $this->currentHandler($request);
        if (!$handler) {
            $backURL = urlencode((string) $request->getHeader('Referer'));

            return $this->redirect(Controller::join_links(Security::login_url(), '?BackURL=' . $backURL));
        }

        if ($op === 'moveall') {
            $handler->MoveAllToCart((bool) self::config()->get('remove_on_add_to_cart'));

            return $this->back($request);
        }

        $buyable = $this->buyableFromRequest($request);
        if (!$buyable) {
            return $this->httpError(404);
        }

        switch ($op) {
            case 'add':
                $handler->AddBuyable($buyable, $this->requestedQuantity($request));
                break;
            case 'remove':
                $handler->RemoveBuyable($buyable);
                break;
            case 'toggle':
                $handler->ToggleBuyable($buyable, $this->requestedQuantity($request));
                break;
            case 'movetocart':
                ShoppingCart::singleton()->add($buyable, $this->requestedQuantity($request));
                if (self::config()->get('remove_on_add_to_cart')) {
                    $handler->RemoveBuyable($buyable);
                }
                break;
        }

        return $this->back($request);
    }

    /**
     * The wishlist to operate on: the member's database list (a specific owned list when allow_multiple_lists
     * and a valid WishlistID is posted, otherwise their default list), or a guest session list (when
     * allow_guest), or null — meaning the guest must log in first.
     */
    protected function currentHandler(HTTPRequest $request): ?WishlistHandler
    {
        $member = Security::getCurrentUser();
        if ($member) {
            if (self::config()->get('allow_multiple_lists')) {
                $list = $member->WishlistByID((int) $request->postVar('WishlistID'));
                if ($list) {
                    return $list;
                }
            }

            return $member->Wishlist();
        }

        if (self::config()->get('allow_guest')) {
            return SessionWishlist::create();
        }

        return null;
    }

    /**
     * Create / rename / delete one of the member's named lists. Member-only, POST + CSRF, and only when
     * allow_multiple_lists is on.
     */
    protected function manageList(HTTPRequest $request, string $op)
    {
        if (!$request->isPOST() || !SecurityToken::inst()->checkRequest($request)) {
            return $this->httpError(400);
        }

        if (!self::config()->get('allow_multiple_lists')) {
            return $this->httpError(400);
        }

        $member = Security::getCurrentUser();
        if (!$member) {
            $backURL = urlencode((string) $request->getHeader('Referer'));

            return $this->redirect(Controller::join_links(Security::login_url(), '?BackURL=' . $backURL));
        }

        $title = trim((string) $request->postVar('Title'));

        if ($op === 'create') {
            $list = Wishlist::create();
            $list->MemberID = $member->ID;
            if ($title !== '') {
                $list->Title = $title;
            }
            $list->write();
            $this->extend('onCreateWishlist', $list);

            return $this->back($request);
        }

        $list = $member->WishlistByID((int) $request->postVar('WishlistID'));
        if (!$list) {
            return $this->httpError(404);
        }

        if ($op === 'rename' && $title !== '') {
            $list->Title = $title;
            $list->write();
            $this->extend('onRenameWishlist', $list);
        } elseif ($op === 'delete') {
            $this->extend('onDeleteWishlist', $list);
            $list->delete();
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
