<?php

declare(strict_types=1);

namespace SilverShop\Wishlist\Report;

use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Reports\Report;

/**
 * Products ranked by how many times they have been saved to a wishlist — a demand / merchandising signal
 * ("people want this"). Counts {@link \SilverShop\Wishlist\Model\WishlistItem} rows grouped by the product
 * they reference ({@see \SilverShop\Wishlist\Model\WishlistItem::$has_one} `Product`); a variation saved on a
 * list counts towards its parent product. Limited to the top 100.
 */
class MostWishlistedProductsReport extends Report
{
    private const LIMIT = 100;

    public function title()
    {
        return _t(__CLASS__ . '.TITLE', 'Most wishlisted products');
    }

    public function description()
    {
        return _t(__CLASS__ . '.DESC', 'Products ranked by how many times they have been saved to a wishlist.');
    }

    public function group()
    {
        return _t('SilverShop\\Reports.GROUP', 'Shop');
    }

    public function sort()
    {
        return 400;
    }

    public function sourceRecords($params = null)
    {
        $query = SQLSelect::create();
        $query->setSelect([
            'Product' => '"st"."Title"',
            'TimesWishlisted' => 'COUNT("wi"."ID")',
        ]);
        $query->setFrom('"SilverShop_WishlistItem" AS "wi"');
        $query->addInnerJoin('SiteTree', '"st"."ID" = "wi"."ProductID"', 'st');
        $query->addWhere('"wi"."ProductID" > 0');
        $query->setGroupBy(['"wi"."ProductID"', '"st"."Title"']);
        $query->setOrderBy('COUNT("wi"."ID")', 'DESC');
        $query->setLimit(self::LIMIT);

        $list = ArrayList::create();
        foreach ($query->execute() as $row) {
            $list->push(ArrayData::create([
                'Product' => $row['Product'],
                'TimesWishlisted' => (int) $row['TimesWishlisted'],
            ]));
        }

        return $list;
    }

    public function columns()
    {
        return [
            'Product' => _t(__CLASS__ . '.ColProduct', 'Product'),
            'TimesWishlisted' => _t(__CLASS__ . '.ColTimesWishlisted', 'Times wishlisted'),
        ];
    }
}
