<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Tests;

use SilverShop\Page\Product;
use SilverShop\Wishlist\Model\Wishlist;
use SilverShop\Wishlist\Report\MostWishlistedProductsReport;
use SilverStripe\Dev\SapphireTest;

class MostWishlistedProductsReportTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static bool $use_draft_site = true;

    /**
     * The report instantiates, has a title, and its hand-written SQL runs cleanly against an empty database
     * (SQLite in CI) without error.
     */
    public function testReportRunsCleanlyOnEmptyDatabase(): void
    {
        $report = new MostWishlistedProductsReport();

        $this->assertNotEmpty((string) $report->title(), 'report has a title');
        $this->assertCount(0, $report->sourceRecords(), 'report runs cleanly with no data');
    }

    /**
     * A product saved on more lists ranks above one saved on fewer, and the count reflects the number of
     * wishlist items referencing it.
     */
    public function testMostWishlistedProductRanksFirst(): void
    {
        $popular = Product::create(['Title' => 'Popular', 'BasePrice' => 10]);
        $popular->write();
        $niche = Product::create(['Title' => 'Niche', 'BasePrice' => 20]);
        $niche->write();

        // 'Popular' is saved on two lists, 'Niche' on one.
        $listOne = Wishlist::create();
        $listOne->write();
        $listOne->AddBuyable($popular);
        $listOne->AddBuyable($niche);

        $listTwo = Wishlist::create();
        $listTwo->write();
        $listTwo->AddBuyable($popular);

        $rows = (new MostWishlistedProductsReport())->sourceRecords();

        $this->assertCount(2, $rows, 'two distinct products are ranked');

        $first = $rows->first();
        $this->assertSame('Popular', $first->Product, 'most-wishlisted product ranks first');
        $this->assertSame(2, (int) $first->TimesWishlisted, 'counts the wishlist items referencing the product');

        $this->assertContains('Niche', $rows->column('Product'), 'the less-wishlisted product still appears');
    }
}
