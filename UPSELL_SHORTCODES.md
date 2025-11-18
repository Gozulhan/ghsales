# GHSales Upsell Integration Documentation

Complete guide to using GHSales intelligent product recommendations.

> **Note:** This file was previously named UPSELL_SHORTCODES.md. The primary integration method is now through the GulcaN-Plugins product widget, not standalone shortcodes.

---

## Recommended Method: GulcaN-Plugins Product Widget

**The best way to display GHSales recommendations is through the GulcaN-Plugins WooCommerce Products widget.**

### Why Use the Widget?

- ✅ **Seamless Integration:** Works perfectly with your existing theme design
- ✅ **Consistent Styling:** Matches all your other product displays
- ✅ **Auto-Context Detection:** Automatically shows the right recommendations based on page type:
  - **Homepage:** Personalized recommendations based on browsing history
  - **Product Pages:** "Frequently bought together" and related items
  - **Cart Pages:** Complementary products based on cart contents
- ✅ **Elementor Support:** Easy drag-and-drop configuration
- ✅ **Responsive Design:** Built-in mobile/tablet optimization
- ✅ **Performance Optimized:** Uses existing caching system

### How to Use in Elementor

1. **Add the Widget:**
   - Open page in Elementor
   - Search for "GulcaN WooCommerce Products" widget
   - Drag it to your desired location

2. **Configure Settings:**
   - **Product Type:** Select "GHSales Recommendations"
   - **Limit:** Choose how many products to show (4-12 recommended)
   - **Columns:** Configure grid layout
   - All other styling options work as normal

3. **That's it!** The widget will automatically:
   - Detect the page context (homepage/product/cart)
   - Pull intelligent recommendations from GHSales
   - Display them in your theme's style
   - Fall back to latest products if no recommendations available

### Shortcode Method (Alternative)

If you're not using Elementor, you can use the gulcan-plugins shortcode:

```
[gulcan_wc_products type="ghsales_recommendations" limit="8"]
```

This gives you all the same benefits without needing Elementor.

---

## Backup Method: Generic Upsells Shortcode

**Only use this if you need custom standalone upsell displays separate from your product widgets.**

### `[ghsales_upsells]`

**Purpose:** Display upsell recommendations with custom styling, independent of the product widget system.

**Usage:**
```
[ghsales_upsells context="generic" limit="4" title="Recommended Products" columns="4"]
```

**Parameters:**
- `context` (string) - Context type for recommendations
  - `generic` - Popular and trending products (default)
  - `cart` - Based on cart contents
  - `homepage` - Personalized based on user history
- `context_id` (int) - Optional context ID (for specific product context)
- `limit` (int) - Number of products to show (default: 4)
- `title` (string) - Heading text (default: "Recommended Products")
- `columns` (int) - Grid columns (2-6, default: 4)

**Examples:**
```
<!-- Show 6 popular products in 3 columns -->
[ghsales_upsells limit="6" columns="3" title="Trending Now"]

<!-- Show cart-related recommendations -->
[ghsales_upsells context="cart" limit="4" title="Complete Your Order"]

<!-- Simple generic recommendations -->
[ghsales_upsells]
```

**When to Use:**
- Custom landing pages needing unique styling
- Blog posts with embedded products
- Special promotional sections
- Any case where you need control separate from the widget system

---

## Automatic Integrations (No Shortcode Needed)

### Mini Cart Upsells
**Location:** ghminicart sale section
**Hook:** `ghminicart_sale_section_content`
**Display:** Automatic - shows 4 recommendations in cart

**No shortcode needed** - this is automatically integrated with the ghminicart plugin.

### Product Page Upsells (Optional Hook)
**Location:** After product summary
**Hook:** `woocommerce_after_single_product_summary`
**Priority:** 15

You can **disable the automatic hook** and use shortcodes instead by removing this line from your theme's functions.php:
```php
remove_action( 'woocommerce_after_single_product_summary', array( 'GHSales_Upsell', 'render_product_upsells' ), 15 );
```

---

## Recommendation Algorithm

### How Products Are Scored:

1. **Frequently Bought Together** (80-100 points)
   - Products purchased in the same orders
   - Highest priority for relevance

2. **Price Psychology Match** (60-70 points)
   - Products priced at 25-50% of base product/cart value
   - Proven to maximize conversion rates

3. **Category Match** (40-60 points)
   - Products in same categories as cart/viewed products
   - Good for related items

4. **Trending Products** (40-60 points)
   - High recent views relative to total views
   - Great for new/seasonal products

5. **Complementary Products** (45 points)
   - Products from different categories but suitable price
   - Good for cross-category upsells

6. **Popular Products** (30-50 points)
   - Based on 7-day view counts
   - Fallback when no personalization data

**Scores are cumulative** - products matching multiple criteria get higher scores.

---

## Responsive Behavior

### Desktop (> 768px)
- Respects `columns` parameter exactly
- Optimal spacing and card sizes

### Tablet (768px - 480px)
- 4-6 columns → 3 columns
- 3 columns → 3 columns
- 2 columns → 2 columns

### Mobile (< 480px)
- **All layouts → 2 columns** (forced for best UX)
- Reduced spacing and text sizes
- Touch-friendly buttons

---

## Styling & Customization

### CSS Classes

Main containers:
- `.ghsales-upsells-shortcode` - Wrapper for shortcode output
- `.ghsales-cart-upsells` - Cart upsells container
- `.ghsales-product-upsells` - Product page upsells
- `.ghsales-homepage-upsells` - Homepage upsells

Grid/Layout:
- `.ghsales-upsells-grid` - Grid container (generic/homepage)
- `.ghsales-upsells-carousel` - Carousel container (product page)

Product cards:
- `.ghsales-upsell-card` - Individual product card
- `.ghsales-upsell-image` - Product image
- `.ghsales-upsell-title` - Product title
- `.ghsales-upsell-price` - Price display
- `.ghsales-upsell-add-to-cart` - Add to cart button

### Custom CSS Example

```css
/* Change title color */
.ghsales-upsells-title {
    color: #e74c3c;
}

/* Customize product card hover */
.ghsales-upsell-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

/* Change button color */
.ghsales-upsell-add-to-cart {
    background: #e74c3c;
}

.ghsales-upsell-add-to-cart:hover {
    background: #c0392b;
}
```

---

## Performance & Caching

### Cache Duration
- **1 hour** per user/session
- Cached in `wp_ghsales_upsell_cache` table

### Cache Keys
- Context type + Context ID + User/Session
- Separate cache for cart, product, homepage contexts

### Cache Cleanup
- Automatic daily cron job
- Removes expired entries

### Performance Tips
1. Use reasonable `limit` values (4-8 products)
2. Don't place multiple shortcodes on same page with same context
3. Cache works automatically - no configuration needed

---

## Examples by Use Case

### E-commerce Homepage (Recommended: Use Widget)
**In Elementor:**
1. Add "GulcaN WooCommerce Products" widget
2. Set Product Type to "GHSales Recommendations"
3. Configure columns and limit as needed

**Or use shortcode:**
```
[gulcan_wc_products type="ghsales_recommendations" limit="8"]
```

### Product Page (Recommended: Use Widget)
**In Elementor:**
1. Add "GulcaN WooCommerce Products" widget to product template
2. Set Product Type to "GHSales Recommendations"
3. The widget automatically detects it's a product page and shows relevant recommendations

**Or use shortcode:**
```
[gulcan_wc_products type="ghsales_recommendations" limit="6"]
```

### Blog Post with Products
```
<!-- Use generic shortcode for sidebar -->
[ghsales_upsells limit="3" columns="1" title="Related Products"]
```

### Landing Page
```
<!-- Use widget or generic shortcode -->
[gulcan_wc_products type="ghsales_recommendations" limit="8"]

<!-- OR for custom styling -->
[ghsales_upsells context="generic" limit="8" columns="4" title="Our Best Sellers"]
```

---

## Troubleshooting

### No products showing?

1. **Check product availability**
   - Products must be published and in stock
   - Check WooCommerce catalog visibility settings

2. **Check data availability**
   - User activity tracking must be active
   - Product stats need time to accumulate
   - "Frequently bought together" needs order history

3. **Check context**
   - `[ghsales_product_upsells]` only works on product pages
   - Cart context needs items in cart

4. **Check cache**
   - Recommendations cached for 1 hour
   - Clear cache: Delete from `wp_ghsales_upsell_cache` table

### Styling issues?

1. Check if CSS is loaded: `public/css/ghsales-upsells.css`
2. Check for theme CSS conflicts
3. Use browser inspector to debug

### AJAX add-to-cart not working?

1. Check JavaScript console for errors
2. Verify WooCommerce AJAX is enabled
3. Check if `wc-add-to-cart` script is loaded
4. Verify nonce in `ghsales_upsell_params`

---

## Developer Hooks & Filters

### Modify recommendations programmatically
```php
add_filter( 'ghsales_upsell_recommendations', function( $recommendations, $context ) {
    // Modify $recommendations array
    return $recommendations;
}, 10, 2 );
```

### Change cache duration
```php
add_filter( 'ghsales_upsell_cache_duration', function( $duration ) {
    return 7200; // 2 hours in seconds
} );
```

### Customize product card output
```php
add_filter( 'ghsales_upsell_card_html', function( $html, $product_id ) {
    // Modify card HTML
    return $html;
}, 10, 2 );
```

---

## Database Tables Used

- `wp_ghsales_upsell_cache` - Cached recommendations
- `wp_ghsales_user_activity` - User browsing history
- `wp_ghsales_product_stats` - Product performance metrics
- `wp_woocommerce_order_items` - Order history (for "frequently bought together")

---

## Support & Documentation

For more information:
- PRD: See `PRD.md` for business requirements
- ERD: See `ERD.md` for database schema
- Implementation: See `IMPLEMENTATION_DECISIONS.md`

---

**Version:** 1.0.0
**Last Updated:** 2025-01-18
**Plugin:** GHSales - WordPress Sales & Upsell Management
