# GHSales Plugin - Complete Verification Checklist

**Purpose:** Verify all plugin features are working correctly before launch.
**Created:** November 20, 2025
**Version:** 1.1.0

---

## 📋 **Quick Database Check** (Do This First!)

### Step 1: Verify All Tables Exist

**Go to:** WordPress Admin → GH Sales → Settings (or `admin.php?page=ghsales`)

**Check:** Database Status section should show ALL tables with green checkmarks:

- ✅ `wp_ghsales_events`
- ✅ `wp_ghsales_rules`
- ✅ `wp_ghsales_color_schemes`
- ✅ `wp_ghsales_user_activity`
- ✅ `wp_ghsales_product_stats`
- ✅ `wp_ghsales_upsell_cache`
- ✅ `wp_ghsales_purchase_limits`

**If any are missing:** Plugin not installed correctly, run deactivate → activate.

---

### Step 2: Check if Tracking is Working

**Method 1: Direct Database Query**

Run this in phpMyAdmin or Adminer:

```sql
SELECT COUNT(*) as total_activity
FROM wp_ghsales_user_activity
WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

**Expected Result:** Number > 0 (if site has had visitors in last 24 hours)

**If 0:** Tracking not working, check error logs.

---

**Method 2: Test Product View Tracking**

1. Open a product page on your site in incognito/private window
2. Wait 5 seconds
3. Run this query (replace `123` with actual product ID):

```sql
SELECT * FROM wp_ghsales_user_activity
WHERE product_id = 123
AND activity_type = 'view'
ORDER BY timestamp DESC
LIMIT 1;
```

**Expected Result:** Recent record showing your view

**If no record:** Tracking broken, check browser console for errors.

---

### Step 3: Check Product Stats Aggregation

```sql
SELECT
    product_id,
    views_7days,
    conversions_7days,
    revenue_total
FROM wp_ghsales_product_stats
WHERE views_7days > 0
ORDER BY views_7days DESC
LIMIT 10;
```

**Expected Result:** Products with view counts > 0

**If all zeros:** Stats not aggregating, check cron jobs.

---

## ✅ **Feature-by-Feature Verification**

---

## 1. USER BEHAVIOR TRACKING

### 1.1 Product View Tracking

**Test Steps:**
1. Open a product page in incognito window
2. Wait 5 seconds for tracking to fire

**Verify in Database:**
```sql
SELECT COUNT(*) as view_count
FROM wp_ghsales_user_activity
WHERE activity_type = 'view'
AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

- [ ] View count increased by 1
- [ ] `product_id` field populated correctly
- [ ] `session_id` generated for guest user
- [ ] `timestamp` is recent
- [ ] `ip_address` is masked (last octet = 0)

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 1.2 Add to Cart Tracking

**Test Steps:**
1. Add a product to cart
2. Wait 5 seconds

**Verify in Database:**
```sql
SELECT * FROM wp_ghsales_user_activity
WHERE activity_type = 'add_to_cart'
ORDER BY timestamp DESC
LIMIT 1;
```

- [ ] Record created with `activity_type = 'add_to_cart'`
- [ ] `product_id` matches added product
- [ ] `meta_data` contains quantity

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 1.3 Purchase Tracking

**Test Steps:**
1. Complete a test purchase (can use Cash on Delivery)
2. Check order status is "Processing" or "Completed"

**Verify in Database:**
```sql
SELECT * FROM wp_ghsales_user_activity
WHERE activity_type = 'purchase'
ORDER BY timestamp DESC
LIMIT 1;
```

- [ ] Record created for each purchased product
- [ ] `meta_data` contains order_id, quantity, total
- [ ] `session_id` or `user_id` populated

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 1.4 Search Tracking

**Test Steps:**
1. Use WooCommerce product search
2. Search for "test product"

**Verify in Database:**
```sql
SELECT * FROM wp_ghsales_user_activity
WHERE activity_type = 'search'
AND search_query LIKE '%test%'
ORDER BY timestamp DESC
LIMIT 1;
```

- [ ] Record created with `activity_type = 'search'`
- [ ] `search_query` field contains search term

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 1.5 Category View Tracking

**Test Steps:**
1. Visit a product category page
2. Wait 5 seconds

**Verify in Database:**
```sql
SELECT * FROM wp_ghsales_user_activity
WHERE activity_type = 'category_view'
ORDER BY timestamp DESC
LIMIT 1;
```

- [ ] Record created with category_id
- [ ] Timestamp is recent

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

## 2. PRODUCT STATS AGGREGATION

### 2.1 Stats Auto-Update on Events

**Test Steps:**
1. View a product (creates 'view' activity)
2. Wait 10 seconds
3. Check product stats table

**Verify in Database:**
```sql
SELECT
    views_7days,
    views_total,
    last_updated
FROM wp_ghsales_product_stats
WHERE product_id = [PRODUCT_ID];
```

- [ ] `views_7days` incremented by 1
- [ ] `views_total` incremented by 1
- [ ] `last_updated` timestamp is recent

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 2.2 Conversion Stats on Purchase

**Test Steps:**
1. Complete test purchase of product
2. Check stats table

**Verify in Database:**
```sql
SELECT
    conversions_7days,
    conversions_total,
    revenue_total
FROM wp_ghsales_product_stats
WHERE product_id = [PRODUCT_ID];
```

- [ ] `conversions_7days` incremented
- [ ] `conversions_total` incremented
- [ ] `revenue_total` increased by order total

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 2.3 Trending Products Algorithm

**Verify in Database:**
```sql
SELECT
    product_id,
    views_7days,
    views_total,
    CASE
        WHEN views_total > 0 THEN (views_7days * 1.0 / views_total) * 100
        ELSE 0
    END as trend_score
FROM wp_ghsales_product_stats
WHERE views_7days > 0
ORDER BY trend_score DESC
LIMIT 10;
```

- [ ] Products with high recent views show high trend_score
- [ ] Calculation is correct (recent views / total views * 100)

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

## 3. SALE EVENT SYSTEM

### 3.1 Create Sale Event

**Test Steps:**
1. Go to **Sale Events → Add New**
2. Set name: "Test Sale"
3. Set start date: Today
4. Set end date: Tomorrow
5. Click **Publish**

**Verify:**
- [ ] Event saved successfully
- [ ] Shows in Sale Events list
- [ ] Status shows "Active" or "Scheduled"

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 3.2 Add Discount Rule

**Test Steps:**
1. Edit the test sale event
2. Click **Add Rule** in rules meta box
3. Select rule type: "Percentage"
4. Set discount: 10%
5. Applies to: "All Products"
6. Save/Update event

**Verify in Database:**
```sql
SELECT * FROM wp_ghsales_rules
WHERE event_id = [EVENT_ID];
```

- [ ] Rule created with correct discount_value
- [ ] `rule_type` = 'percentage'
- [ ] `applies_to` = 'all'

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 3.3 Verify Discount Applied to Cart

**Test Steps:**
1. Ensure test sale is active (start <= now <= end)
2. Add a product to cart
3. Go to cart page

**Verify:**
- [ ] Discount line item appears in cart
- [ ] Discount amount is correct (10% of product price)
- [ ] Discount label shows sale name or description

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 3.4 Sale Badges on Products

**Test Steps:**
1. View a product page (with active sale)
2. Check product thumbnail/image area

**Verify:**
- [ ] Sale badge displays on product image
- [ ] Badge shows correct discount ("10% OFF" or "Save €5")
- [ ] Badge styling is visible and readable

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 3.5 Purchase Limits Enforcement

**Test Steps:**
1. Edit sale rule, set "Max quantity per customer: 1"
2. Save rule
3. Add 1 of product to cart and complete purchase
4. Try to add same product again

**Verify:**
- [ ] Second add-to-cart is prevented OR discount not applied
- [ ] Message shown to customer about limit reached

**Verify in Database:**
```sql
SELECT * FROM wp_ghsales_purchase_limits
WHERE rule_id = [RULE_ID];
```

- [ ] Record created with `quantity_purchased = 1`
- [ ] `customer_identifier` is email or session ID

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

## 4. COLOR SCHEME OVERRIDE SYSTEM

### 4.1 Verify Admin Color Detection

**Test Steps:**
1. Go to **GH Sales → Settings**
2. Find "Elementor Colors" section
3. Click **Re-detect Elementor Colors** button

**Verify:**
- [ ] System Colors display (Primary, Secondary, Text, Accent)
- [ ] Custom Colors display (if any exist in Elementor Kit)
- [ ] Each color shows swatch with hex code
- [ ] No PHP errors in browser console or error log

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 4.2 Assign Color Scheme to Sale

**Test Steps:**
1. Edit a sale event
2. In **Event Settings** meta box, find "Color Scheme" dropdown
3. Select a color scheme
4. Save event

**Verify in Database:**
```sql
SELECT
    p.post_title as event_name,
    pm.meta_value as color_scheme_id
FROM wp_posts p
JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'ghsales_event'
AND pm.meta_key = '_ghsales_color_scheme_id';
```

- [ ] Color scheme ID saved to event meta

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 4.3 Frontend Color Override Activation

**Test Steps:**
1. Create sale with start date = now, end date = tomorrow
2. Assign color scheme with RED primary color (#FF0000)
3. Save and publish sale
4. Visit homepage in NEW incognito window
5. Right-click → Inspect Element
6. Look at `<head>` section for `<style id="ghsales-color-override">`

**Verify:**
- [ ] `<style>` tag with ID `ghsales-color-override` exists in `<head>`
- [ ] Contains `--e-global-color-primary: #FF0000 !important;`
- [ ] Contains other color variables (secondary, accent, text)
- [ ] Colors visible on page (buttons, headings, etc. change to red)

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 4.4 Color Override Deactivation

**Test Steps:**
1. Edit sale, set end date to PAST (yesterday)
2. Save event
3. Visit homepage in new incognito window
4. Inspect `<head>`

**Verify:**
- [ ] `<style id="ghsales-color-override">` tag does NOT exist
- [ ] Colors reverted to default theme colors
- [ ] No JavaScript errors in console

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 4.5 Color Scheme Cache

**Test Steps:**
1. Activate sale with color scheme
2. Visit homepage (cache is created)
3. Check transient in database

**Verify in Database:**
```sql
SELECT * FROM wp_options
WHERE option_name = '_transient_ghsales_active_color_scheme';
```

- [ ] Transient exists
- [ ] `option_value` contains serialized color scheme data
- [ ] Has expiration transient `_transient_timeout_ghsales_active_color_scheme`

**Clear cache and verify refresh:**
```sql
DELETE FROM wp_options
WHERE option_name LIKE '%ghsales_active_color_scheme%';
```

- [ ] Visit homepage again, cache recreated automatically

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

## 5. INTELLIGENT UPSELL SYSTEM

### 5.1 Recommendations on Product Page

**Test Steps:**
1. Visit any product page
2. Scroll to "Recommended Products" section (after product tabs)

**Verify:**
- [ ] Upsell section displays
- [ ] Shows 4-6 recommended products
- [ ] Products are relevant (same category or frequently bought together)
- [ ] Swiper carousel navigation works
- [ ] Product images load correctly
- [ ] Add to Cart button visible on each product

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 5.2 Recommendations in Mini Cart

**Test Steps:**
1. Add product to cart
2. Open mini cart (click cart icon)
3. Scroll to bottom of mini cart drawer

**Verify:**
- [ ] Upsell carousel displays in mini cart
- [ ] Shows products related to cart items
- [ ] Swiper carousel works (can swipe/click arrows)
- [ ] AJAX add-to-cart button works (adds without page refresh)

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 5.3 Recommendations via Shortcode

**Test Steps:**
1. Create test page/post
2. Add shortcode: `[ghsales_upsells context="homepage" limit="6"]`
3. Publish and view page

**Verify:**
- [ ] Upsell products display
- [ ] Correct number of products (6)
- [ ] Carousel works properly

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 5.4 Recommendation Algorithm Scoring

**Verify in Database:**
```sql
SELECT * FROM wp_ghsales_upsell_cache
WHERE expires_at > NOW()
ORDER BY created_at DESC
LIMIT 5;
```

- [ ] Cache entries exist
- [ ] `recommended_products` field contains comma-separated IDs
- [ ] `context_type` shows 'product_page', 'cart', 'homepage', etc.
- [ ] Expires_at timestamp is ~1 hour in future

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 5.5 AJAX Add to Cart from Upsells

**Test Steps:**
1. View product page with upsell carousel
2. Click "Add to Cart" on recommended product
3. Watch for page refresh (should NOT refresh)

**Verify:**
- [ ] Product added to cart without page reload
- [ ] Success message displays
- [ ] Cart count increases
- [ ] Mini cart updates automatically

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

## 6. CRON JOB VERIFICATION

### 6.1 Check WP-Cron is Running

**Method 1: WP-CLI**
```bash
wp cron event list
```

Look for:
- `ghsales_reset_weekly_stats`
- `ghsales_reset_monthly_stats`
- `ghsales_cleanup_old_activity`
- `ghsales_cleanup_expired_cache`

---

**Method 2: Database Query**
```sql
SELECT * FROM wp_options
WHERE option_name = 'cron';
```

- [ ] Option exists and contains ghsales cron jobs

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 6.2 Manual Cron Trigger

**Test cleanup cron:**
```bash
wp cron event run ghsales_cleanup_old_activity
```

**Verify in Database:**
```sql
SELECT COUNT(*) FROM wp_ghsales_user_activity
WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

- [ ] Old records (> 1 year) deleted
- [ ] Recent records remain

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

## 7. INTEGRATION VERIFICATION

### 7.1 gulcan-plugins Integration

**Test Steps:**
1. Add "GHSales Recommendations" widget via gulcan-plugins
2. Configure widget settings
3. View page with widget

**Verify:**
- [ ] Widget displays upsell products
- [ ] Fallback works if GHSales inactive
- [ ] Consistent styling with other widgets

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 7.2 ghminicart Integration

**Test Steps:**
1. Open mini cart drawer
2. Check for upsell carousel section

**Verify:**
- [ ] Upsells display in mini cart
- [ ] Hook `ghminicart_sale_section_content` firing
- [ ] Swiper carousel works

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 7.3 ghmenu Integration (Hot Sale Section)

**Test Steps:**
1. Open hamburger menu
2. Look for "Hot Sale" or special sales section

**Verify:**
- [ ] Sale products section displays
- [ ] Title styled correctly (brown color, bold)
- [ ] Swiper carousel doesn't close menu

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

## 8. PERFORMANCE VERIFICATION

### 8.1 Page Load Time

**Test with GTmetrix or Google PageSpeed:**
1. Test homepage
2. Test product page
3. Test cart page

**Verify:**
- [ ] Page load time < 2 seconds (target)
- [ ] No render-blocking from ghsales CSS/JS
- [ ] Color override CSS adds < 50ms

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 8.2 Database Query Performance

**Check slow query log or use Query Monitor plugin**

**Verify:**
- [ ] Product stats queries < 100ms
- [ ] Recommendation queries < 500ms (with cache)
- [ ] No N+1 query problems

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 8.3 Cache Hit Rate

**Monitor for 1 hour with traffic:**

**Check cache hits:**
```sql
SELECT
    COUNT(*) as total_cached,
    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as valid_cache
FROM wp_ghsales_upsell_cache;
```

**Calculate hit rate:**
- Valid cache / Total requests * 100 = Hit Rate %
- **Target:** > 80% hit rate

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

## 9. SECURITY VERIFICATION

### 9.1 SQL Injection Prevention

**Manual Code Review:**
- [ ] All database queries use `$wpdb->prepare()`
- [ ] No direct `$_GET` or `$_POST` variables in SQL
- [ ] All user inputs sanitized before queries

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 9.2 XSS Prevention

**Manual Code Review:**
- [ ] All output uses `esc_html()`, `esc_attr()`, `esc_url()`
- [ ] Color values validated with regex before injection
- [ ] No `echo $_POST` or `echo $_GET` without escaping

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 9.3 CSRF Protection

**Verify:**
- [ ] Admin forms use `wp_nonce_field()`
- [ ] AJAX requests verify nonces
- [ ] `check_admin_referer()` used on form submission

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### 9.4 User Capability Checks

**Verify:**
- [ ] Admin pages check `current_user_can('manage_woocommerce')`
- [ ] Sale event CPT checks capabilities
- [ ] AJAX handlers verify user permissions

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

## 10. BROWSER COMPATIBILITY

### Test on Multiple Browsers

- [ ] Chrome (latest) - Desktop
- [ ] Firefox (latest) - Desktop
- [ ] Safari (latest) - Desktop
- [ ] Edge (latest) - Desktop
- [ ] Chrome - Mobile (Android)
- [ ] Safari - Mobile (iOS)

**Verify for Each:**
- [ ] Upsell carousels work
- [ ] Color override applies
- [ ] No JavaScript console errors
- [ ] Responsive design works
- [ ] Touch gestures work on mobile

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

## 11. ERROR LOG VERIFICATION

### Check PHP Error Log

**Location:** Usually `/wp-content/debug.log` or server error log

**Look for:**
- ❌ PHP Fatal errors
- ❌ PHP Warnings
- ✅ GHSales info logs (OK to have these)

**Verify:**
- [ ] No fatal errors from ghsales
- [ ] No repeated warnings
- [ ] Info logs show tracking working

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Check Browser Console

**Open:** Chrome DevTools → Console

**Verify:**
- [ ] No JavaScript errors
- [ ] No 404 errors for assets
- [ ] No CORS errors
- [ ] Ajax requests succeeding (200 status)

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

## 📊 **Final Verification Score**

**Total Tests:** 60+

**Passed:** _____ / 60
**Failed:** _____ / 60
**Not Tested:** _____ / 60

**Pass Rate:** _____ %

**Minimum for Launch:** 95% pass rate (57/60 tests)

---

## 🚨 **Critical Issues Found**

List any CRITICAL issues that must be fixed before launch:

1. _______________________________
2. _______________________________
3. _______________________________

---

## ⚠️ **Non-Critical Issues**

List minor issues that can be fixed post-launch:

1. _______________________________
2. _______________________________
3. _______________________________

---

## ✅ **Launch Readiness Decision**

- [ ] All critical tests passed
- [ ] Pass rate > 95%
- [ ] No critical issues found
- [ ] Performance targets met
- [ ] Security verified
- [ ] Browser compatibility confirmed

**READY FOR LAUNCH:** ⬜ YES | ⬜ NO

**If NO, remaining work:**
_______________________________
_______________________________

---

## 📅 **Verification Completed**

**Date:** _______________
**By:** _______________
**Plugin Version:** 1.1.0
**WooCommerce Version:** _______________
**WordPress Version:** _______________

---

*This checklist will be updated with each new feature release.*
*Last Updated: November 20, 2025*
