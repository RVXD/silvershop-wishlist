<%-- Public, read-only view of a shared wishlist (rendered by WishlistController::shared by token). No owner
     data is exposed — only the list title and its products. Override in your theme to restyle. --%>
<!DOCTYPE html>
<html lang="$ContentLocale">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>$SharedWishlist.Title.XML &middot; $SiteConfig.Title.XML</title>
    <style>
        body{margin:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#333;line-height:1.5}
        .wrap{max-width:640px;margin:40px auto;background:#fff;border-radius:8px;padding:32px 36px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
        .site{margin:0;color:#888;font-size:.85em;text-transform:uppercase;letter-spacing:.04em}
        h1{margin:.2rem 0 .2rem}
        .intro{margin:0 0 1.5rem;color:#888;font-size:.9em}
        .items{list-style:none;margin:0;padding:0}
        .item{display:flex;align-items:center;gap:.9rem;padding:.75rem 0;border-top:1px solid #eee;flex-wrap:wrap}
        .item img{border-radius:4px;object-fit:cover;flex:0 0 auto}
        .item__title{flex:1;min-width:160px;text-decoration:none;color:inherit;font-weight:600}
        .item__price{font-weight:600}
        .item__avail{font-size:.85em;color:#888}
        .item__avail--out{color:#c0392b}
    </style>
</head>
<body>
    <div class="wrap">
        <p class="site">$SiteConfig.Title.XML</p>
        <h1>$SharedWishlist.Title.XML</h1>
        <p class="intro"><%t SilverShop\Wishlist.SharedIntro "A wishlist shared with you" %></p>

        <% if $SharedWishlist.Items %>
            <ul class="items">
                <% loop $SharedWishlist.Items %>
                    <li class="item">
                        <% if $Product.Image.Exists %><img src="$Product.Image.Fill(64,64).URL" alt="$BuyableTitle.ATT" width="64" height="64" /><% end_if %>
                        <a class="item__title" href="$Product.AbsoluteLink">$BuyableTitle</a>
                        <span class="item__price">$Price.Nice</span>
                        <span class="item__avail<% if not $Available %> item__avail--out<% end_if %>"><% if $Available %><%t SilverShop\Wishlist.InStock "In stock" %><% else %><%t SilverShop\Wishlist.OutOfStock "Out of stock" %><% end_if %></span>
                    </li>
                <% end_loop %>
            </ul>
        <% else %>
            <p><%t SilverShop\Wishlist.Empty "Your wishlist is empty." %></p>
        <% end_if %>
    </div>
</body>
</html>
