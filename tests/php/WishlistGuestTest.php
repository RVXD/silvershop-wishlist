<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Tests;

use SilverShop\Page\Product;
use SilverShop\Wishlist\Model\SessionWishlist;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;

class WishlistGuestTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures/wishlist.yml';

    private Controller $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // The session-backed guest wishlist reads ShopTools::getSession(); provide a request + session.
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        Injector::inst()->registerService($request, HTTPRequest::class);
        $this->controller = new Controller();
        $this->controller->setRequest($request);
        $this->controller->pushCurrent();
    }

    protected function tearDown(): void
    {
        $this->controller->popCurrent();
        parent::tearDown();
    }

    private function productA(): Product
    {
        return $this->objFromFixture(Product::class, 'product_a');
    }

    public function testSessionAddHasRemoveToggle(): void
    {
        $session = SessionWishlist::create();
        $a = $this->productA();

        $this->assertFalse($session->HasBuyable($a));

        $session->AddBuyable($a);
        $this->assertTrue($session->HasBuyable($a));
        $this->assertSame(1, $session->count());

        $session->AddBuyable($a); // idempotent
        $this->assertSame(1, $session->count());

        $this->assertFalse($session->ToggleBuyable($a)); // removes
        $this->assertSame(0, $session->count());
    }

    public function testMergeIntoMemberListOnLogin(): void
    {
        $session = SessionWishlist::create();
        $session->AddBuyable($this->objFromFixture(Product::class, 'product_a'));
        $session->AddBuyable($this->objFromFixture(Product::class, 'product_b'));
        $this->assertSame(2, $session->count());

        $buyer = $this->objFromFixture(Member::class, 'buyer');
        $buyer->onAfterMemberLoggedIn(); // fires the merge

        $this->assertSame(2, $buyer->Wishlist()->Items()->count());
        $this->assertSame(0, SessionWishlist::create()->count()); // session cleared
    }

    public function testMergeDeduplicatesAgainstExistingItems(): void
    {
        $buyer = $this->objFromFixture(Member::class, 'buyer');
        $buyer->Wishlist()->AddBuyable($this->objFromFixture(Product::class, 'product_a')); // already saved

        $session = SessionWishlist::create();
        $session->AddBuyable($this->objFromFixture(Product::class, 'product_a')); // dup
        $session->AddBuyable($this->objFromFixture(Product::class, 'product_b'));

        $buyer->onAfterMemberLoggedIn();

        $this->assertSame(2, $buyer->Wishlist()->Items()->count()); // A (deduped) + B
    }
}
