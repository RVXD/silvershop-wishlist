<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Tests;

use SilverShop\Page\Product;
use SilverShop\Wishlist\Model\Wishlist;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;

class WishlistTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures/wishlist.yml';

    private function buyer(): Member
    {
        return $this->objFromFixture(Member::class, 'buyer');
    }

    private function productA(): Product
    {
        return $this->objFromFixture(Product::class, 'product_a');
    }

    public function testMemberGetsADefaultWishlist(): void
    {
        $wishlist = $this->buyer()->Wishlist();

        $this->assertInstanceOf(Wishlist::class, $wishlist);
        $this->assertTrue($wishlist->isInDB());
        $this->assertSame($this->buyer()->ID, (int) $wishlist->MemberID);
    }

    public function testAddIsIdempotent(): void
    {
        $wishlist = $this->buyer()->Wishlist();
        $wishlist->AddBuyable($this->productA());
        $wishlist->AddBuyable($this->productA());

        $this->assertSame(1, $wishlist->Items()->count());
        $this->assertTrue($this->buyer()->hasInWishlist($this->productA()));
    }

    public function testToggle(): void
    {
        $wishlist = $this->buyer()->Wishlist();

        $this->assertTrue($wishlist->ToggleBuyable($this->productA()));  // added
        $this->assertSame(1, $wishlist->Items()->count());
        $this->assertFalse($wishlist->ToggleBuyable($this->productA())); // removed
        $this->assertSame(0, $wishlist->Items()->count());
    }

    public function testItemResolvesBuyableAndPrice(): void
    {
        $item = $this->buyer()->Wishlist()->AddBuyable($this->productA());

        $this->assertSame($this->productA()->ID, $item->Buyable()->ID);
        $this->assertSame(10.0, $item->getPrice());
        $this->assertSame(10.0, (float) $item->AddedPrice); // price recorded at add time
    }

    public function testPriceDroppedFlag(): void
    {
        $item = $this->buyer()->Wishlist()->AddBuyable($this->productA());
        $this->assertFalse($item->getPriceDropped());

        $item->AddedPrice = 15.00; // pretend it was added when dearer
        $this->assertTrue($item->getPriceDropped());
    }

    public function testCascadeDeleteRemovesItems(): void
    {
        $wishlist = $this->buyer()->Wishlist();
        $wishlist->AddBuyable($this->productA());
        $itemID = $wishlist->Items()->first()->ID;

        $wishlist->delete();

        $this->assertNull(\SilverShop\Wishlist\Model\WishlistItem::get()->byID($itemID));
    }

    public function testOwnershipPermissions(): void
    {
        $wishlist = $this->buyer()->Wishlist();
        $other = $this->objFromFixture(Member::class, 'other');

        $this->assertTrue($wishlist->canEdit($this->buyer()));
        $this->assertFalse($wishlist->canEdit($other));
    }
}
