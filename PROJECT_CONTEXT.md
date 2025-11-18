# GHSales Plugin - Complete Project Context

## Plugin Overview

**ghsales** is a comprehensive WordPress/WooCommerce plugin that handles:
1. **Sale Event Management** - Create and manage sales (discounts, BOGO, spend thresholds, etc.)
2. **Sitewide Color Theme Override** - Automatically change Elementor colors during sale events
3. **Intelligent Upsell System** - Smart product recommendations based on user behavior
4. **GDPR-Compliant User Tracking** - Track user behavior with proper consent management
5. **Smart Pricing Engine** - AI-powered discount suggestions (future feature)

---

## Core Concepts

### 1. Sale Events
Each sale event is a **single entity** that controls:
- **What's on sale** (discount rules, products, categories)
- **How it looks** (color palette override)
- **When it runs** (date range)
- **Where it shows** (which sections display what)

### 2. Modular Sale Sections
Each sale event can have **multiple sale sections**:
- Section 1: "BOGO Deals" (Buy 1 Get 1 Free on Electronics)
- Section 2: "Spend & Save" (10% off when cart > €50)
- Section 3: "Flash Deals" (30% off specific products)

Each section can be displayed independently in different locations:
- Mini cart drawer (via `ghminicart_sale_section_content` hook)
- Menu drawer (ghmenu hot sale section)
- Product pages
- Homepage

### 3. Color Schemes (Separate from Sales)
- **Multiple sales can run simultaneously** (different discount rules)
- **Only ONE color scheme is active** at any time
- Client chooses which color scheme to activate
- Examples: Black Friday colors, Koninginnedag (orange/Dutch flag), Halloween, Christmas

---

## Technical Architecture

### Database Schema

#### wp_ghsales_user_activity
Tracks user behavior (views, searches, add-to-cart, clicks)
- Requires GDPR consent before tracking
- Stores masked IP addresses
- Links to session_id (guests) or user_id (logged in)

#### wp_ghsales_product_stats
Aggregated product performance metrics
- Total views, 7-day views, 30-day views
- Conversion rates
- Revenue tracking
- Profit margins (for smart pricing)

#### wp_ghsales_events
Sale event definitions
- Name, type (manual/auto/suggested)
- Start/end dates
- Status (draft/active/scheduled/ended)
- Links to color scheme
- Sale rules (JSON)

#### wp_ghsales_color_schemes
Color palette definitions
- Scheme name (Black Friday, Halloween, etc.)
- Primary, Secondary, Accent, Text, Background colors
- Only one can be active at a time

#### wp_ghsales_rules
Individual discount rules for each event
- Rule type: percentage, bogo, buy_x_get_y, spend_threshold
- Applies to: products, categories, tags, all
- Discount value
- Conditions (JSON for complex logic)
- Priority (which rule wins if multiple match)

#### wp_ghsales_upsell_cache
Cached product recommendations
- Context: cart, product page, homepage
- User-specific or session-specific
- Recommended products with scores
- Expires after set time

#### wp_ghsales_consent_log
GDPR consent tracking (legal requirement)
- Session ID and user ID
- Consent type (analytics, marketing)
- Consent given (yes/no)
- Masked IP address
- Timestamp

---

## GDPR Compliance

### Cookie Strategy
Three cookie categories:
1. **Necessary** - No consent needed (session, cart)
2. **Analytics** - Needs consent (behavior tracking, personalization)
3. **Marketing** - Needs consent (remarketing, advertising)

### Consent Banner
- Auto-injected or via shortcode `[ghsales_consent]`
- Checkboxes for each category
- Buttons: Accept All, Accept Selected, Reject All
- Logs consent to database with masked IP

### IP Address Masking
- IPv4: Remove last octet (192.168.1.1 → 192.168.1.0)
- IPv6: Remove last 80 bits
- GDPR compliant for data minimization

### User Rights
- Right to be forgotten (delete all user data)
- Right to access (export all user data)
- Right to object (stop tracking)

---

## Color Override System

### Multi-Layer Approach

**Layer 1: Elementor Global Colors Override**
```php
update_option('elementor_scheme_color', $event_colors);
\Elementor\Plugin::$instance->files_manager->clear_cache();
```

**Layer 2: High-Priority CSS Injection**
Inject CSS with `!important` at priority 999 in `wp_head`:
- Override CSS variables (`:root`)
- Override common theme patterns
- Override Elementor widgets
- Override WooCommerce elements
- Override ghmenu & ghminicart plugins

**Layer 3: Maximum Priority Enqueue**
Enqueue CSS at priority 9999 in `wp_enqueue_scripts`

### Color Backup & Restore
- Backup current Elementor colors before sale event activates
- Restore original colors when sale event ends
- Store in `ghsales_color_backup` option

---

## Upsell System

### Display Locations & Psychology

#### Mini Cart (Small Upsells)
**Psychology:** Impulse add-on purchases, low commitment
- **Price Rule:** 25-50% of current cart total (max)
- **Quantity:** 2-4 products
- **Display:** Small cards, quick add-to-cart buttons
- **Example:** Cart = €200, show products ≤ €100

#### Product Page (Medium Upsells)
**Psychology:** "Complete your purchase" or "Upgrade to this"
- **Types:** Cross-sells (accessories), Upsells (premium version), Related items
- **Quantity:** 3-6 products
- **Display:** Product cards below main product

#### Homepage (Big Personalized Upsells)
**Psychology:** Discovery, trending, personalized
- **Types:** "Based on browsing", "Trending now", "Customers also bought", "Complete your collection"
- **Quantity:** 8-12 products in carousel/grid
- **Display:** Multiple sections with different data sources

### Scoring Algorithm

Each product gets a score (0-100) based on:

**Relevance (0-40 points)**
- Matches user interests: +20
- Same category as cart items: +10
- Frequently bought together: +10

**Price Psychology (0-30 points)**
- Price ratio 25-50% of context: +30 (sweet spot)
- Too cheap (<25%): Lower score
- Too expensive (>50%): Lower score

**Performance (0-30 points)**
- Trending product: +10
- High conversion rate: +10
- Good profit margin: +10

Top N products by score are displayed.

---

## Smart Pricing Engine (Future)

### Phase 1: Manual + Basic Rules (Launch)
Client sets rules:
- "Discount products with <5 sales in 30 days by 10%"
- "Discount overstocked items (>50 units) by 15%"
- "Discount high-margin items (>40%) by up to 20%"

### Phase 2: Smart Suggestions (Mid-term)
Plugin analyzes and **suggests** discounts:
- Product A: Suggest 15% off
  - Reason: High margin (45%), slow sales, trending category
  - Projected: +30% sales, maintain profitability
- Client approves or modifies suggestions

### Phase 3: Auto-Pilot (Long-term)
Machine learning model:
- Analyzes historical sales data (6+ months)
- Considers profit margins, stock, seasonality
- Predicts optimal discount percentage
- Automatically applies and monitors results

**Data Required:**
- Purchase history (6+ months minimum)
- Profit margins per product
- Stock levels over time
- Seasonal trends
- Competitor pricing (optional)

---

## What WooCommerce Already Tracks (We Can Use)

✅ **Purchase History** - `wp_posts` and `wp_woocommerce_order_items`
✅ **Customer Data** - `wp_users` and `wp_usermeta`
✅ **Cart Sessions** - `wp_woocommerce_sessions` (48 hours)
✅ **Order Analytics** - WooCommerce Analytics (built-in)

## What We Need to Track

❌ **Product Views** - Not tracked natively
❌ **Browsing Patterns** - Which categories explored
❌ **Time on Product Pages** - Engagement metrics
❌ **Search Queries** - What customers search for
❌ **Click Behavior** - Products clicked but not added to cart

---

## Integration with Other Plugins

### ghminicart Integration
Action hook: `ghminicart_sale_section_content`
- ghsales hooks into this to display cart-relevant upsells
- Shows small upsells (impulse buys)
- Can show event-specific offers (1+1 free, etc.)

### ghmenu Integration
Hot sale section with Elementor template selector
- Can display different sale aspects than cart
- Example: Event banner in menu, BOGO in cart
- Same sale data, different presentation

---

## Sale Event Structure Example

```
Sale Event: "Black Friday 2025"
├── Basic Info
│   ├── Name: "Black Friday Sale"
│   ├── Start: Nov 24, 2025 00:00
│   ├── End: Nov 27, 2025 23:59
│   └── Status: Active
│
├── Sale Rules (Multiple Sections)
│   ├── Section 1: "Electronics BOGO"
│   │   ├── Type: Buy 1 Get 1 Free
│   │   ├── Applies to: Category "Electronics"
│   │   └── Display: Mini cart + Product pages
│   │
│   ├── Section 2: "Spend €50 Save 10%"
│   │   ├── Type: Percentage discount on threshold
│   │   ├── Applies to: All products
│   │   └── Display: Cart total banner
│   │
│   └── Section 3: "Flash Deals"
│       ├── Type: 30% off specific products
│       ├── Applies to: Product IDs [123, 456, 789]
│       └── Display: Homepage carousel
│
├── Color Scheme (Optional)
│   ├── Linked to: "Black Friday Colors"
│   ├── Primary: #000000 (Black)
│   ├── Secondary: #FFFFFF (White)
│   └── Accent: #FFD700 (Gold)
│
└── Display Settings
    ├── Show in minicart: Yes
    ├── Show in menu: Yes
    └── Show on homepage: Yes
```

---

## Admin Interface Structure

### Sale Events Page
**Custom Post Type:** `ghsales_event`
- List view: All sale events with status
- Quick edit: Enable/disable sales
- Bulk actions: Activate/deactivate multiple sales

### Sale Event Editor (Tabs)

**Tab 1: Basic Info**
- Event name
- Start/end dates
- Event type (manual, auto, suggested)
- Description

**Tab 2: Sale Rules (Repeater)**
- Add multiple sale sections
- For each section:
  - Section name
  - Rule type dropdown (%, BOGO, Buy X Get Y, Spend threshold)
  - Applies to (products, categories, tags, all)
  - Product/category selector
  - Discount value
  - Priority (if multiple rules)
  - Display locations (checkboxes)

**Tab 3: Theme Colors**
- Link to existing color scheme (dropdown)
- OR create new scheme inline
- Color pickers for each global color:
  - Primary
  - Secondary
  - Accent
  - Text
  - Background
- Preview button

**Tab 4: Display Options**
- Where to show this sale:
  - Mini cart (checkbox)
  - Menu drawer (checkbox)
  - Product pages (checkbox)
  - Homepage (checkbox)
  - Category pages (checkbox)
- Template selectors for each location

### Color Schemes Page
**Custom Post Type:** `ghsales_color_scheme`
- List view: All color schemes
- "Active" indicator (only one can be active)
- Quick activate button

**Color Scheme Editor**
- Scheme name (Black Friday, Halloween, etc.)
- Color pickers for each color
- Preview: Shows before/after comparison
- "Detect Current Theme Colors" button (scans site)

### Analytics Dashboard
- Product performance table:
  - Views (total, 7-day, 30-day)
  - Conversions
  - Conversion rate
  - Revenue
- Sale event performance:
  - Revenue generated
  - Products sold
  - Discount amount given
- Upsell performance:
  - Click-through rate
  - Conversion rate
  - Revenue from upsells

---

## Development Phases

### Phase 1: MVP (Launch)
1. ✅ Sale event management (manual)
2. ✅ Color scheme override system
3. ✅ GDPR consent management
4. ✅ User behavior tracking (with consent)
5. ✅ Basic upsell system (rule-based):
   - Frequently bought together
   - Category-based recommendations
   - Price-ratio filtering
6. ✅ Display upsells in: minicart, product pages
7. ✅ Admin interface (sale events, color schemes)

### Phase 2: Enhanced (3-6 months)
1. Advanced upsell system:
   - Personalized recommendations
   - Homepage upsell sections
   - User browsing history integration
2. Smart pricing suggestions:
   - Analyze stock/margins/sales velocity
   - Suggest optimal discounts
   - Client approval workflow
3. A/B testing for upsells
4. Performance analytics dashboard

### Phase 3: AI-Powered (6-12 months)
1. Machine learning pricing model
2. Predictive analytics
3. Auto-optimization of upsells
4. Competitor price tracking
5. Demand forecasting

---

## Key Design Decisions

### Why Separate Sales from Color Schemes?
- Multiple sales can run simultaneously (different products/categories)
- But only one visual theme should be active (avoid color chaos)
- Example: Black Friday sale + Christmas sale both active, but only Black Friday colors showing

### Why Track Everything from Day 1?
- Machine learning requires historical data (6+ months minimum)
- Can't retroactively collect data
- Even if we don't use it immediately, we'll have it when needed

### Why GDPR First?
- EU legal requirement (fines up to €20M or 4% revenue)
- Builds user trust
- Industry standard best practice
- Easier to build in from start than retrofit

### Why Price Psychology in Upsells?
- Research shows upsells work best at 25-50% of main purchase
- Too cheap = looks low quality
- Too expensive = feels like pressure
- Sweet spot = "easy decision" for customer

---

## File Structure

```
ghsales/
├── ghsales.php                 # Main plugin file
├── PROJECT_CONTEXT.md          # This file
├── README.md                   # User-facing documentation
├── includes/
│   ├── class-ghsales-core.php              # Main plugin class (singleton)
│   ├── class-ghsales-gdpr.php              # GDPR consent management
│   ├── class-ghsales-tracker.php           # User behavior tracking
│   ├── class-ghsales-color-manager.php     # Color override system
│   ├── class-ghsales-sale-engine.php       # Sale rules processing
│   ├── class-ghsales-upsell-engine.php     # Upsell recommendations
│   ├── class-ghsales-pricing-engine.php    # Smart pricing (future)
│   └── class-ghsales-installer.php         # Database setup
├── admin/
│   ├── class-ghsales-admin.php             # Admin menu & settings
│   ├── admin-page.php                      # Main admin page HTML
│   ├── class-ghsales-event-cpt.php         # Sale events custom post type
│   ├── class-ghsales-color-scheme-cpt.php  # Color schemes custom post type
│   └── class-ghsales-analytics.php         # Analytics dashboard
├── assets/
│   ├── css/
│   │   ├── ghsales-admin.css               # Admin styling
│   │   ├── ghsales-frontend.css            # Frontend styling
│   │   └── ghsales-consent-banner.css      # GDPR banner styling
│   └── js/
│       ├── ghsales-admin.js                # Admin JavaScript
│       ├── ghsales-consent.js              # Consent banner logic
│       ├── ghsales-upsells.js              # Upsell display logic
│       └── ghsales-tracking.js             # Frontend tracking
└── templates/
    ├── consent-banner.php                  # GDPR consent banner
    ├── upsell-cart.php                     # Cart upsell template
    ├── upsell-product.php                  # Product page upsell template
    └── upsell-homepage.php                 # Homepage upsell template
```

---

## Important Constraints & Requirements

### Client Needs
1. **Easy to use** - Non-technical client must be able to create sales
2. **Flexible** - Support any type of sale (%, BOGO, spend thresholds, etc.)
3. **Visual consistency** - Site colors should match sale events (Black Friday = dark theme)
4. **Profitable** - Smart pricing must maintain profit margins
5. **GDPR compliant** - Must work in EU markets

### Technical Constraints
1. **WordPress/WooCommerce only** - No standalone version
2. **Elementor integration** - Must override Elementor global colors
3. **Works with existing plugins** - ghminicart, ghmenu integration
4. **Performance** - Upsell calculations must be fast (cache results)
5. **Scalability** - Must handle 10,000+ products

### Business Logic
1. **Multiple sales simultaneously** - Different rules for different products
2. **One color scheme at a time** - Avoid visual chaos
3. **Client controls colors** - Not automatic based on sale
4. **Profit protection** - Never suggest discounts that lose money
5. **User privacy** - Only track with consent, mask IPs, allow data deletion

---

## Next Steps for Development

1. Create main plugin file (`ghsales.php`)
2. Set up database installer
3. Build GDPR consent system (foundation for everything else)
4. Implement user tracking (product views, searches, etc.)
5. Create sale event custom post type & editor
6. Build color scheme override system
7. Implement basic upsell engine
8. Create frontend templates
9. Build admin dashboard
10. Test & iterate

---

## Questions for Future Consideration

1. Should upsells be shown immediately or after delay (to avoid appearing pushy)?
2. Should we limit number of active sales to prevent confusion?
3. How to handle conflicting discount rules (multiple sales on same product)?
4. Should color changes be instant or fade transition?
5. How to A/B test different upsell strategies?
6. Should we integrate with email marketing (send sale notifications)?
7. How to handle timezone differences for global stores?
8. Should we allow scheduling sale events in advance?

---

## Related Plugins

### ghminicart
- Mini cart drawer for WooCommerce
- Has `ghminicart_sale_section_content` action hook
- ghsales will hook into this to display small upsells

### ghmenu
- Hamburger menu for mobile
- Has hot sale section with Elementor template selector
- Can show different sale content than cart
- Example: Menu shows event banner, cart shows BOGO items

---

## Technologies & Dependencies

- **PHP 7.4+** (WordPress requirement)
- **WordPress 5.8+**
- **WooCommerce 5.0+** (required)
- **Elementor** (optional but recommended for color override)
- **MySQL 5.7+** (for database)

---

## Contact & Support

- Plugin Author: Gulcan Home Development Team
- Website: https://gulcanhome.eu
- Support: Create issue in project repository

---

**Last Updated:** 2025-01-18
**Version:** Planning Phase (Not yet implemented)
**Status:** Architecture & Design Complete, Ready for Development
