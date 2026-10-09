<%-- Wishlist toggle for the product page. Include it in your product template with the product in scope:
     <% include SilverShop\Wishlist\WishlistButton %>
     Shown to members, and to guests when allow_guest is on; posts to the wishlist controller (CSRF-checked)
     and returns to the page. When multiple lists are enabled, a logged-in member gets a "save to list" popup
     (progressive enhancement — without JavaScript the heart simply toggles the default list). Override this
     template in your theme to restyle; override WishlistPopupScript.ss to replace the popup behaviour, or set
     WishlistController.enable_popup = false to drop it entirely. --%>
<% if $WishlistEnabled %>
<div class="wishlist-button"<% if $WishlistPopupEnabled %>
     data-wishlist-multi
     data-base="$WishlistBaseLink.ATT"
     data-view="$WishlistPageLink.ATT"
     data-product="$ID"
     data-i18n-heading="<%t SilverShop\Wishlist.AddToList 'Add to list' %>"
     data-i18n-added="<%t SilverShop\Wishlist.Added 'Added to your wishlist' %>"
     data-i18n-newlist="<%t SilverShop\Wishlist.NewList 'New list' %>"
     data-i18n-listname="<%t SilverShop\Wishlist.ListName 'List name' %>"
     data-i18n-create="<%t SilverShop\Wishlist.Create 'Create' %>"
     data-i18n-done="<%t SilverShop\Wishlist.Done 'Done' %>"
     data-i18n-view="<%t SilverShop\Wishlist.ViewWishlist 'View wishlist' %>"
     data-i18n-close="<%t SilverShop\Wishlist.Close 'Close' %>"
     data-i18n-saved="<%t SilverShop\Wishlist.Saved 'Saved' %>"
     data-i18n-save="<%t SilverShop\Wishlist.Save 'Save for later' %>"<% end_if %>>
    <style>
        .wishlist-button{display:inline-block;margin:.5rem 0}
        .wishlist-button__btn{background:none;border:1px solid #ccc;border-radius:4px;padding:7px 14px;font-size:.9rem;cursor:pointer;color:#333}
        .wishlist-button__btn.is-saved{border-color:#c0392b;color:#c0392b}
        .wishlist-button__view{display:inline-block;margin-left:.6rem;font-size:.85rem}
        .wl-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:9999;padding:16px}
        .wl-panel{background:#fff;color:#222;border-radius:10px;max-width:420px;width:100%;max-height:85vh;overflow:auto;box-shadow:0 10px 40px rgba(0,0,0,.25);font-size:1rem}
        .wl-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #eee}
        .wl-title{margin:0;font-size:1.1rem}
        .wl-x{background:none;border:none;font-size:1.6rem;line-height:1;cursor:pointer;color:#666;padding:0 4px}
        .wl-added{display:flex;align-items:center;gap:.5rem;margin:14px 18px 0;padding:10px 12px;background:#e7f5ec;border-left:4px solid #2e7d32;border-radius:4px;font-size:.9rem}
        .wl-lists{padding:4px 18px}
        .wl-row{display:flex;align-items:center;gap:.6rem;padding:.6rem 0;border-bottom:1px solid #f0f0f0;cursor:pointer}
        .wl-row:last-child{border-bottom:none}
        .wl-row input{width:18px;height:18px;flex:0 0 auto}
        .wl-new{padding:10px 18px 4px}
        .wl-newbtn{background:none;border:none;color:#1a56db;cursor:pointer;font-size:.92rem;padding:4px 0}
        .wl-newform{display:flex;gap:.4rem;margin-top:.4rem}
        .wl-newform input{flex:1;padding:6px 8px;font-size:.9rem}
        .wl-create{background:#1a56db;color:#fff;border:none;border-radius:4px;padding:6px 12px;cursor:pointer}
        .wl-foot{display:flex;align-items:center;gap:1rem;padding:14px 18px;border-top:1px solid #eee}
        .wl-done{background:#222;color:#fff;border:none;border-radius:4px;padding:7px 16px;cursor:pointer}
        .wl-view{font-size:.9rem;margin-left:auto}
    </style>
    <form method="post" action="$WishlistLink" class="wishlist-button__form">
        $WishlistSecurityField
        <input type="hidden" name="ProductID" value="$ID" />
        <button type="submit" class="wishlist-button__btn<% if $InWishlist %> is-saved<% end_if %>" data-saved="<% if $InWishlist %>1<% else %>0<% end_if %>">
            <% if $InWishlist %>&#10084; <%t SilverShop\Wishlist.Saved "Saved" %><% else %>&#9825; <%t SilverShop\Wishlist.Save "Save for later" %><% end_if %>
        </button>
    </form>
    <% if $InWishlist && $WishlistPageLink %><a class="wishlist-button__view" href="$WishlistPageLink"><%t SilverShop\Wishlist.ViewWishlist "View wishlist" %></a><% end_if %>
    <% if $WishlistPopupEnabled %><% include SilverShop\Wishlist\WishlistPopupScript %><% end_if %>
</div>
<% end_if %>
