<%-- The member's wishlist, for the account area. Rendered by AccountPage_wishlist.ss (scope = the account
     page controller, so the links/CSRF field come from $Top). --%>
<section class="wishlist">
    <style>
        .wishlist h2{margin:0 0 1rem}
        .wishlist__list{list-style:none;margin:0;padding:0}
        .wishlist__item{display:flex;align-items:center;gap:.75rem;padding:.6rem 0;border-top:1px solid #eee;flex-wrap:wrap}
        .wishlist__item img{border-radius:4px;object-fit:cover;flex:0 0 auto}
        .wishlist__title{flex:1;min-width:160px;text-decoration:none;color:inherit}
        .wishlist__price{font-weight:600}
        .wishlist__drop{background:#2e7d32;color:#fff;border-radius:4px;padding:1px 7px;font-size:.72rem;font-weight:600;margin-left:.3rem}
        .wishlist__avail{font-size:.8rem;color:#888}
        .wishlist__avail--out{color:#c0392b}
        .wishlist__act{display:inline;margin:0}
        .wishlist__act button{background:none;border:1px solid #ccc;border-radius:4px;padding:3px 10px;font-size:.8rem;cursor:pointer;color:#555}
        .wishlist__toolbar{margin:1rem 0 0}
    </style>

    <h2><%t SilverShop\Wishlist.Heading "My wishlist" %></h2>

    <% if $CurrentMember.Wishlist.Items %>
        <ul class="wishlist__list">
            <% loop $CurrentMember.Wishlist.Items %>
                <li class="wishlist__item">
                    <% if $Product.Image.Exists %><img src="$Product.Image.Fill(56,56).URL" alt="$BuyableTitle.ATT" width="56" height="56" /><% end_if %>
                    <a class="wishlist__title" href="$Product.Link">$BuyableTitle</a>
                    <span class="wishlist__price">$Price.Nice<% if $PriceDropped %><span class="wishlist__drop"><%t SilverShop\Wishlist.PriceDropped "price dropped" %></span><% end_if %></span>
                    <span class="wishlist__avail<% if not $Available %> wishlist__avail--out<% end_if %>"><% if $Available %><%t SilverShop\Wishlist.InStock "In stock" %><% else %><%t SilverShop\Wishlist.OutOfStock "Out of stock" %><% end_if %></span>
                    <% if $Available %>
                        <form method="post" action="$Top.WishlistCartLink" class="wishlist__act">$Top.WishlistSecurityField<input type="hidden" name="ProductID" value="$ProductID" /><input type="hidden" name="VariationID" value="$VariationID" /><button type="submit"><%t SilverShop\Wishlist.AddToCart "Add to cart" %></button></form>
                    <% end_if %>
                    <form method="post" action="$Top.WishlistRemoveLink" class="wishlist__act">$Top.WishlistSecurityField<input type="hidden" name="ProductID" value="$ProductID" /><input type="hidden" name="VariationID" value="$VariationID" /><button type="submit"><%t SilverShop\Wishlist.Remove "Remove" %></button></form>
                </li>
            <% end_loop %>
        </ul>

        <div class="wishlist__toolbar">
            <form method="post" action="$Top.WishlistMoveAllLink">$Top.WishlistSecurityField<button type="submit"><%t SilverShop\Wishlist.MoveAll "Add all to cart" %></button></form>
        </div>
    <% else %>
        <p><%t SilverShop\Wishlist.Empty "Your wishlist is empty." %></p>
    <% end_if %>
</section>
