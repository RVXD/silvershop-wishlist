<%-- Account-area "wishlist" section (the /account/wishlist action). Mirrors the other AccountPage_<action>
     templates: the account navigation + the section content. Override in your theme to restyle. --%>
<% include SilverShop\Includes\AccountNavigation %>
<div class="silvershop-account silvershop-account--wishlist silvershop-typography">
    <% include SilverShop\Wishlist\WishlistItems %>
</div>
