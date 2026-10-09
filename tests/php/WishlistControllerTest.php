<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Tests;

use SilverShop\Cart\ShoppingCart;
use SilverShop\Page\Product;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\SecurityToken;

class WishlistControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'fixtures/wishlist.yml';

    // Assert on redirect status codes (e.g. guest → login) rather than following them.
    protected $autoFollowRedirection = false;

    protected function setUp(): void
    {
        parent::setUp();
        // The frontend request reads the Live stage; publish the fixture products so the controller finds them.
        $this->objFromFixture(Product::class, 'product_a')->publishRecursive();
        $this->objFromFixture(Product::class, 'product_b')->publishRecursive();
        // CSRF is exercised in production; disable it here so the POST bodies stay minimal (mirrors reviews).
        SecurityToken::disable();
    }

    private function buyer(): Member
    {
        return $this->objFromFixture(Member::class, 'buyer');
    }

    private function productID(string $handle): int
    {
        return (int) $this->objFromFixture(Product::class, $handle)->ID;
    }

    public function testAddRequiresPost(): void
    {
        $this->logInAs('buyer');

        $response = $this->get('wishlist/add?ProductID=' . $this->productID('product_a'));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGuestIsRedirectedToLogin(): void
    {
        $this->logOut();

        $response = $this->post('wishlist/add', ['ProductID' => $this->productID('product_a')]);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testAddThenToggleRemoves(): void
    {
        $this->logInAs('buyer');
        $pid = $this->productID('product_a');

        $this->post('wishlist/add', ['ProductID' => $pid]);
        $this->assertSame(1, $this->buyer()->Wishlist()->Items()->count());

        $this->post('wishlist/toggle', ['ProductID' => $pid]);
        $this->assertSame(0, $this->buyer()->Wishlist()->Items()->count());
    }

    public function testRemove(): void
    {
        $this->logInAs('buyer');
        $this->buyer()->Wishlist()->AddBuyable($this->objFromFixture(Product::class, 'product_a'));

        $this->post('wishlist/remove', ['ProductID' => $this->productID('product_a')]);

        $this->assertSame(0, $this->buyer()->Wishlist()->Items()->count());
    }

    public function testMoveToCart(): void
    {
        $this->logInAs('buyer');
        $pid = $this->productID('product_a');
        $this->buyer()->Wishlist()->AddBuyable($this->objFromFixture(Product::class, 'product_a'));

        $this->post('wishlist/movetocart', ['ProductID' => $pid]);

        $cart = ShoppingCart::curr();
        $this->assertNotNull($cart);
        $this->assertSame(1, $cart->Items()->count());
    }
}
