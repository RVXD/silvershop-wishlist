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
 * v3 — multiple named lists: create / rename / delete, add to a specific list, and owner isolation.
 */
class WishlistMultiListTest extends FunctionalTest
{
    protected static $fixture_file = 'fixtures/wishlist.yml';

    protected $autoFollowRedirection = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objFromFixture(Product::class, 'product_a')->publishRecursive();
        Config::modify()->set(WishlistController::class, 'allow_multiple_lists', true);
        SecurityToken::disable();
    }

    private function buyer(): Member
    {
        return $this->objFromFixture(Member::class, 'buyer');
    }

    private function listByTitle(Member $member, string $title): ?Wishlist
    {
        /** @var Wishlist|null $list */
        $list = $member->Wishlists()->filter('Title', $title)->first();

        return $list;
    }

    public function testCreateList(): void
    {
        $this->logInAs('buyer');
        $this->assertSame(0, $this->buyer()->OrderedWishlists()->count());

        $this->post('wishlist/createlist', ['Title' => 'Gifts']);

        $this->assertSame(1, $this->buyer()->OrderedWishlists()->count());
        $this->assertNotNull($this->listByTitle($this->buyer(), 'Gifts'));
    }

    public function testCreateListDisabledReturns400(): void
    {
        Config::modify()->set(WishlistController::class, 'allow_multiple_lists', false);
        $this->logInAs('buyer');

        $response = $this->post('wishlist/createlist', ['Title' => 'Nope']);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(0, $this->buyer()->OrderedWishlists()->count());
    }

    public function testAddToSpecificList(): void
    {
        $this->logInAs('buyer');
        $this->post('wishlist/createlist', ['Title' => 'First']);
        $this->post('wishlist/createlist', ['Title' => 'Second']);

        $first = $this->listByTitle($this->buyer(), 'First');
        $second = $this->listByTitle($this->buyer(), 'Second');
        $this->assertNotNull($first);
        $this->assertNotNull($second);

        $this->post('wishlist/add', [
            'ProductID' => (int) $this->objFromFixture(Product::class, 'product_a')->ID,
            'WishlistID' => $second->ID,
        ]);

        $this->assertSame(0, Wishlist::get()->byID($first->ID)->Items()->count());
        $this->assertSame(1, Wishlist::get()->byID($second->ID)->Items()->count());
    }

    public function testRenameList(): void
    {
        $this->logInAs('buyer');
        $this->post('wishlist/createlist', ['Title' => 'Old']);
        $list = $this->listByTitle($this->buyer(), 'Old');
        $this->assertNotNull($list);

        $this->post('wishlist/renamelist', ['WishlistID' => $list->ID, 'Title' => 'New name']);

        $this->assertSame('New name', Wishlist::get()->byID($list->ID)->Title);
    }

    public function testDeleteList(): void
    {
        $this->logInAs('buyer');
        $this->post('wishlist/createlist', ['Title' => 'Keep']);
        $this->post('wishlist/createlist', ['Title' => 'Remove']);
        $removeID = $this->listByTitle($this->buyer(), 'Remove')->ID;
        $keepID = $this->listByTitle($this->buyer(), 'Keep')->ID;

        $this->post('wishlist/deletelist', ['WishlistID' => $removeID]);

        $this->assertNull(Wishlist::get()->byID($removeID));
        $this->assertNotNull(Wishlist::get()->byID($keepID));
    }

    public function testCannotManageAnotherMembersList(): void
    {
        $this->logInAs('buyer');
        $this->post('wishlist/createlist', ['Title' => 'Private']);
        $listID = $this->listByTitle($this->buyer(), 'Private')->ID;

        $this->logInAs('other');
        $rename = $this->post('wishlist/renamelist', ['WishlistID' => $listID, 'Title' => 'Hacked']);
        $this->assertSame(404, $rename->getStatusCode());

        $delete = $this->post('wishlist/deletelist', ['WishlistID' => $listID]);
        $this->assertSame(404, $delete->getStatusCode());

        $this->assertSame('Private', Wishlist::get()->byID($listID)->Title);
    }
}
