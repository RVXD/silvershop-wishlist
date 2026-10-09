<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Tests;

use SilverShop\Page\Product;
use SilverShop\Wishlist\Control\WishlistController;
use SilverShop\Wishlist\Model\Wishlist;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\SecurityToken;

/**
 * v4 — sharing: toggle a list public (read-only by token), view it without logging in, owner isolation.
 */
class WishlistSharingTest extends FunctionalTest
{
    protected static $fixture_file = 'fixtures/wishlist.yml';

    protected $autoFollowRedirection = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objFromFixture(Product::class, 'product_a')->publishRecursive();
        Config::modify()->set(WishlistController::class, 'allow_sharing', true);
        SecurityToken::disable();
    }

    private function buyer(): Member
    {
        return $this->objFromFixture(Member::class, 'buyer');
    }

    public function testShareThenUnshare(): void
    {
        $this->logInAs('buyer');
        $listID = (int) $this->buyer()->Wishlist()->ID;

        $this->post('wishlist/share', ['WishlistID' => $listID]);
        $list = Wishlist::get()->byID($listID);
        $this->assertSame('Shared', $list->Visibility);
        $this->assertNotEmpty($list->Token);

        $this->post('wishlist/unshare', ['WishlistID' => $listID]);
        $this->assertSame('Private', Wishlist::get()->byID($listID)->Visibility);
    }

    public function testSharedViewAccessibleByTokenToGuest(): void
    {
        $this->logInAs('buyer');
        $list = $this->buyer()->Wishlist();
        $list->Title = 'Gift ideas';
        $list->write();
        $list->AddBuyable($this->objFromFixture(Product::class, 'product_a'));
        $this->post('wishlist/share', ['WishlistID' => $list->ID]);
        $token = Wishlist::get()->byID($list->ID)->Token;

        $this->logOut();
        $response = $this->get('wishlist/shared/' . $token);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Gift ideas', (string) $response->getBody());
    }

    public function testPrivateTokenReturns404(): void
    {
        $this->logInAs('buyer');
        $list = $this->buyer()->Wishlist();
        $list->Visibility = 'Private';
        $list->Token = 'abc123def456';
        $list->write();

        $this->logOut();
        $response = $this->get('wishlist/shared/abc123def456');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testShareDisabledReturns400(): void
    {
        Config::modify()->set(WishlistController::class, 'allow_sharing', false);
        $this->logInAs('buyer');

        $response = $this->post('wishlist/share', ['WishlistID' => $this->buyer()->Wishlist()->ID]);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCannotShareAnotherMembersList(): void
    {
        $this->logInAs('buyer');
        $listID = (int) $this->buyer()->Wishlist()->ID;

        $this->logInAs('other');
        $response = $this->post('wishlist/share', ['WishlistID' => $listID]);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Private', Wishlist::get()->byID($listID)->Visibility);
    }
}
