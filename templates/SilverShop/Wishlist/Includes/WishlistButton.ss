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
    </style>
    $WishlistSecurityField
    <input type="hidden" name="ProductID" value="$ID" />
    <button type="submit" class="wishlist-button__btn<% if $InWishlist %> is-saved<% end_if %>">
        <% if $InWishlist %>&#10084; <%t SilverShop\Wishlist.Saved "Saved" %><% else %>&#9825; <%t SilverShop\Wishlist.Save "Save for later" %><% end_if %>
    </button>
    <% if $InWishlist && $WishlistPageLink %><a class="wishlist-button__view" href="$WishlistPageLink"><%t SilverShop\Wishlist.ViewWishlist "View wishlist" %></a><% end_if %>
</form>
<% end_if %>
