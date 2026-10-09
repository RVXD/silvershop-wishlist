<%-- Wishlist toggle for the product page. Include it in your product template with the product in scope:
     <% include SilverShop\Wishlist\WishlistButton %>
     Shown to members, and to guests when allow_guest is on; posts to the wishlist controller (CSRF-checked)
     and returns to the page. --%>
<% if $WishlistEnabled %>
<form method="post" action="$WishlistLink" class="wishlist-button">
    <style>
        .wishlist-button{display:inline-block;margin:.5rem 0}
        .wishlist-button__btn{background:none;border:1px solid #ccc;border-radius:4px;padding:7px 14px;font-size:.9rem;cursor:pointer;color:#333}
        .wishlist-button__btn.is-saved{border-color:#c0392b;color:#c0392b}
        .wishlist-button__view{display:inline-block;margin-left:.6rem;font-size:.85rem}
        .wishlist-addto{display:flex;align-items:center;gap:.4rem;margin:.4rem 0;font-size:.85rem;flex-wrap:wrap}
        .wishlist-addto select{padding:3px 6px;font-size:.85rem}
        .wishlist-addto button{background:none;border:1px solid #ccc;border-radius:4px;padding:5px 11px;font-size:.85rem;cursor:pointer;color:#333}
    </style>
    $WishlistSecurityField
    <input type="hidden" name="ProductID" value="$ID" />
    <button type="submit" class="wishlist-button__btn<% if $InWishlist %> is-saved<% end_if %>">
        <% if $InWishlist %>&#10084; <%t SilverShop\Wishlist.Saved "Saved" %><% else %>&#9825; <%t SilverShop\Wishlist.Save "Save for later" %><% end_if %>
    </button>
    <% if $InWishlist && $WishlistPageLink %><a class="wishlist-button__view" href="$WishlistPageLink"><%t SilverShop\Wishlist.ViewWishlist "View wishlist" %></a><% end_if %>
</form>
<% if $MemberWishlists %>
<form method="post" action="$WishlistLink('add')" class="wishlist-addto">
    $WishlistSecurityField
    <input type="hidden" name="ProductID" value="$ID" />
    <label><%t SilverShop\Wishlist.AddToList "Add to list" %></label>
    <select name="WishlistID"><% loop $MemberWishlists %><option value="$ID">$Title.XML</option><% end_loop %></select>
    <button type="submit"><%t SilverShop\Wishlist.Add "Add" %></button>
</form>
<% end_if %>
<% end_if %>
