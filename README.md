# SilverShop Wishlist

Member wishlists for [SilverShop](https://github.com/silvershop/silvershop-core) on Silverstripe CMS 6: logged-in
customers save products (or specific variations) for later, manage them in the account area, and move them to the
cart.

> **Status — v1 + v2.** Add / remove / toggle from the product page, an account section listing saved items
> with live price + availability and a "price dropped" badge, and move-to-cart (single or all). **Guests** get
> a session wishlist that is **merged into their account on login** (v2). The data model is built around a
> `Wishlist` container, so the remaining tiers (multiple named lists, sharing) slot in without reshaping it.

## Requirements

- PHP 8.3+
- `silverstripe/framework` ^6.0
- `silvershop/core` ^6
- `silvershop/stock` ^6 *(optional — shows live in-stock / out-of-stock state)*

## Installation

```bash
composer require silvershop/wishlist
```

Then run `/dev/build?flush=all`.

## Usage

**Product page — the toggle button.** Add the include to your product template (the product is in scope):

```ss
<% include SilverShop\Wishlist\WishlistButton %>
```

It renders for members (and for guests when `allow_guest` is on) and posts to the wishlist controller
(CSRF-checked), toggling the whole product on/off the list and returning to the page.

**Account area — the wishlist section.** It's available at `…/account/wishlist` out of the box (rendered by
`AccountPage_wishlist.ss`). Add a link to it in your account navigation — the account controller exposes
`$WishlistLink`:

```ss
<a href="$WishlistLink"><%t SilverShop\Wishlist.Nav "Wishlist" %></a>
```

The section lists each saved item with its current price, availability, a price-drop badge, **Add to cart** /
**Remove**, and **Add all to cart**.

**Variations.** The model stores a specific variation per item (`WishlistItem.Variation`), used for price, stock
and move-to-cart. The default product-page button saves the whole product; a variation-aware save can post a
`VariationID` to the same endpoints.

## Endpoints

All POST-only + CSRF-checked, operating on the current member's default list, then redirecting back:

| Action | Does |
| --- | --- |
| `wishlist/add` | Add `ProductID` (+ optional `VariationID`, `Quantity`) |
| `wishlist/remove` | Remove the buyable |
| `wishlist/toggle` | Add if absent, remove if present |
| `wishlist/movetocart` | Add the buyable to the cart |
| `wishlist/moveall` | Add every item to the cart |

## Configuration

```yaml
SilverShop\Wishlist\Control\WishlistController:
  allow_guest: true              # guests build a session wishlist, merged on login (false = require login)
  remove_on_add_to_cart: false   # remove an item once it's moved to the cart
```

## Extension hooks

`onAddToWishlist($item, $buyable)` / `onRemoveFromWishlist($item, $buyable)` on `Wishlist`, and
`updateWishlistResponse($request)` on the controller.

## Roadmap

Done: member wishlist (v1) + guest session wishlist merged on login (v2).

- **v3** — multiple named lists.
- **v4** — sharing (public link).

Back-in-stock / price-drop *alerts* are out of scope here (a separate `silvershop/stock-alerts` concern).

## Development

```bash
composer install
composer lint   # PHP_CodeSniffer (PSR-12)
composer stan   # PHPStan (level 5) + silverstan
composer test   # PHPUnit
```

## Licence

BSD-3-Clause.
