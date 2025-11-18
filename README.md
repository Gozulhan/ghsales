# GHSales - WordPress Sales & Upsell Management Plugin

**Version:** 1.0.0 (Planning Phase)
**Requires:** WordPress 5.8+, WooCommerce 5.0+
**License:** Proprietary
**Author:** Gulcan Home Development Team

---

## Overview

GHSales is a comprehensive WordPress/WooCommerce plugin that revolutionizes how you manage sales events, display product recommendations, and theme your store during promotional periods.

### Key Features

✅ **Unified Sale Management** - Create complex sales (BOGO, percentage off, spend thresholds) in minutes
✅ **Automatic Color Theming** - Transform your site's appearance for Black Friday, Halloween, or any event
✅ **Intelligent Upsells** - Boost average order value with smart product recommendations
✅ **GDPR Compliant** - Track user behavior legally with proper consent management
✅ **Future-Ready AI** - Smart pricing suggestions to maximize revenue (coming soon)

---

## What Makes GHSales Different?

### Traditional Approach (Multiple Plugins)
- ❌ WooCommerce Dynamic Pricing (for discounts)
- ❌ Upsell Plugin (for recommendations)
- ❌ Cookie Consent Plugin (for GDPR)
- ❌ Manual theme editing (for event colors)
- ❌ Google Analytics (for behavior tracking)

### GHSales Approach (One Plugin)
- ✅ All sale types in one place
- ✅ Built-in intelligent upsells
- ✅ GDPR-first consent system
- ✅ Automatic color theming
- ✅ Behavior tracking without external services

**Result:** Simpler, faster, more powerful.

---

## Quick Start

### Installation

1. Upload `ghsales.zip` to `/wp-content/plugins/`
2. Activate the plugin through WordPress admin
3. Go to **GH Sales → Settings** to configure
4. Create your first sale event!

### Creating Your First Sale Event

**Scenario:** Black Friday - 20% off all Electronics, BOGO on Kitchen items

1. Navigate to **GH Sales → Sale Events**
2. Click **Add New**
3. Fill in:
   - Name: "Black Friday 2025"
   - Start Date: Nov 24, 2025 00:00
   - End Date: Nov 27, 2025 23:59
4. Click **Add Sale Section**:
   - Section 1: "Electronics Discount"
     - Type: Percentage
     - Amount: 20%
     - Applies to: Category "Electronics"
   - Section 2: "Kitchen BOGO"
     - Type: Buy 1 Get 1 Free
     - Applies to: Category "Kitchen"
5. Go to **Theme Colors** tab:
   - Select "Black Friday Colors" preset
   - Or create custom colors
6. Click **Schedule**

Done! Sale activates automatically on Nov 24.

---

## Documentation

### For Store Owners
- [PRD.md](./PRD.md) - Complete product requirements and features
- [User Guide](./docs/USER_GUIDE.md) - Step-by-step tutorials (coming soon)
- [FAQ](./docs/FAQ.md) - Common questions (coming soon)

### For Developers
- [PROJECT_CONTEXT.md](./PROJECT_CONTEXT.md) - Full technical context and architecture
- [ERD.md](./ERD.md) - Database schema and relationships
- [TECHNICAL_SPECIFICATION.md](./TECHNICAL_SPECIFICATION.md) - API docs (coming soon)

### For Designers
- [Color System Guide](./docs/COLOR_SYSTEM.md) - How color override works (coming soon)
- [Template Customization](./docs/TEMPLATES.md) - Customize upsell displays (coming soon)

---

## Features Deep Dive

### 1. Sale Event Management

Create any type of sale:
- **Percentage Off** - 20% off entire store
- **Fixed Amount** - $10 off orders over $50
- **BOGO** - Buy 1 Get 1 Free
- **Buy X Get Y** - Buy 2 Get 1 50% Off
- **Spend Thresholds** - Spend $100, save 15%
- **Tiered Discounts** - $50+ = 10%, $100+ = 15%, $150+ = 20%

Apply sales to:
- ✅ Specific products
- ✅ Product categories
- ✅ Product tags
- ✅ Entire store
- ✅ Mix and match

Schedule in advance:
- ✅ Set start/end dates
- ✅ Automatic activation
- ✅ Automatic deactivation
- ✅ Recurring events (future)

---

### 2. Automatic Color Theming

Transform your site's appearance to match promotional events.

**How It Works:**
1. Create color schemes (Black Friday, Halloween, Christmas)
2. Define 5 colors: Primary, Secondary, Accent, Text, Background
3. Link color scheme to sale event
4. Colors change automatically when event activates
5. Original colors restore when event ends

**What Gets Changed:**
- ✅ Elementor global colors
- ✅ WordPress theme colors
- ✅ WooCommerce buttons and elements
- ✅ ghminicart drawer
- ✅ ghmenu drawer
- ✅ Custom CSS overrides (with !important)

**Example:**
```
Black Friday Event:
├── Primary: #000000 (Black)
├── Secondary: #1a1a1a (Dark Gray)
├── Accent: #FFD700 (Gold)
├── Text: #FFFFFF (White)
└── Background: #0A0A0A (Almost Black)

Result: Dark, premium look for Black Friday
```

---

### 3. Intelligent Upsells

Show the right products to the right customers at the right time.

#### Mini Cart Upsells (Small Impulse Buys)
- **Psychology:** Low-commitment add-ons
- **Price Rule:** 25-50% of cart total
- **Example:** Cart = €200 → Show products ≤ €100
- **Quantity:** 2-4 products
- **Location:** ghminicart drawer

#### Product Page Upsells (Cross-sells & Upgrades)
- **Psychology:** Complete your purchase
- **Types:** Accessories, Premium versions, Related items
- **Example:** Viewing coffee machine → Show coffee beans, grinder, premium model
- **Quantity:** 3-6 products
- **Location:** Below product description

#### Homepage Upsells (Personalized Discovery)
- **Psychology:** Trending and personalized
- **Types:**
  - "Based on your browsing"
  - "Trending now"
  - "Customers also bought"
  - "Complete your collection"
- **Quantity:** 8-12 products
- **Location:** Homepage sections

#### How Recommendations Work

Products are scored (0-100) based on:

**Relevance (40 points max)**
- Matches user's browsing history: +20
- Same category as cart items: +10
- Frequently bought together: +10

**Price Psychology (30 points max)**
- Price is 25-50% of main purchase: +30
- Too cheap (<25%): Lower score
- Too expensive (>50%): Lower score

**Performance (30 points max)**
- Trending product (high views): +10
- High conversion rate: +10
- Good profit margin: +10

Top-scoring products are displayed.

---

### 4. GDPR Compliance

Full EU privacy regulation compliance built-in.

**Consent Banner:**
- Auto-displays on first visit
- Three categories: Necessary, Analytics, Marketing
- Options: Accept All, Accept Selected, Reject All
- Customizable text and styling

**What We Track (with consent):**
- ✅ Product views
- ✅ Category browsing
- ✅ Search queries
- ✅ Add-to-cart events
- ✅ Purchase conversions

**Privacy Protections:**
- ✅ Masked IP addresses (192.168.1.1 → 192.168.1.0)
- ✅ Consent logged with timestamp
- ✅ No tracking without consent
- ✅ Right to data deletion
- ✅ Right to data export

**Legal Compliance:**
- ✅ GDPR (EU)
- ✅ CCPA (California)
- ✅ ePrivacy Directive (Cookie Law)

---

### 5. Smart Pricing (Future Feature)

AI-powered discount suggestions to maximize revenue while maintaining profit.

**Phase 1:** Basic Rules (Launch)
- Manual rules: "Discount slow sellers by 10%"
- Automatic application based on criteria

**Phase 2:** Smart Suggestions (3-6 months)
- Analyze product performance
- Suggest optimal discounts
- Show projected impact
- Client approves before applying

**Phase 3:** Auto-Pilot (6-12 months)
- Machine learning model
- Automatic pricing optimization
- Real-time adjustments
- Safeguards for profitability

---

## Integration with Other Plugins

### ghminicart (Mini Cart Drawer)
- **Hook:** `ghminicart_sale_section_content`
- **Use Case:** Display small upsells and BOGO offers in cart
- **Benefit:** Increase AOV at checkout moment

### ghmenu (Hamburger Menu)
- **Feature:** Hot sale section
- **Use Case:** Display event banners and featured sales in menu
- **Benefit:** Promote sales sitewide

### Elementor
- **Feature:** Color override integration
- **Use Case:** Automatically change Elementor global colors
- **Benefit:** Visual consistency during events

### WooCommerce
- **Required:** WooCommerce 5.0+
- **Integration:** Discount application, product data, cart/checkout
- **Benefit:** Seamless native functionality

---

## Performance

### Optimizations
- ✅ Database indexing on all query fields
- ✅ Upsell recommendation caching (1 hour TTL)
- ✅ Lazy loading of tracking scripts
- ✅ Minified CSS and JavaScript
- ✅ Conditional loading (only where needed)

### Benchmarks
- Page load time: <2 seconds
- Upsell calculation: <500ms
- Color override: Instant (no reload)
- Admin interface: <3 seconds

### Scalability
- Supports 10,000+ products
- Handles 1,000+ concurrent users
- Processes 100,000+ tracking events/day
- Stores 1 year of behavioral data

---

## Browser Support

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

---

## Changelog

### Version 1.0.0 (Planned)
- Initial release
- Sale event management
- Color scheme override
- GDPR consent system
- User behavior tracking
- Basic upsell engine
- Admin interface

### Version 1.1.0 (Planned - Q2 2025)
- Advanced upsell personalization
- Smart pricing suggestions
- Analytics dashboard
- A/B testing for upsells

### Version 2.0.0 (Planned - Q4 2025)
- AI-powered auto-pricing
- Predictive analytics
- Competitor price tracking
- Demand forecasting

---

## Support

### Documentation
- [User Guide](./docs/USER_GUIDE.md)
- [Developer Docs](./PROJECT_CONTEXT.md)
- [FAQ](./docs/FAQ.md)

### Contact
- **Website:** https://gulcanhome.eu
- **Email:** support@gulcanhome.eu
- **GitHub:** Create an issue in project repository

### Community
- WordPress.org Plugin Forum (after public release)
- Facebook Group (coming soon)
- Discord Server (coming soon)

---

## Contributing

This is currently a private/proprietary plugin. Contributing guidelines will be published if we open-source in the future.

---

## License

Proprietary - All Rights Reserved
© 2025 Gulcan Home Development Team

Unauthorized copying, modification, or distribution is prohibited.

---

## Credits

### Development Team
- Lead Developer: [Name]
- Database Architect: [Name]
- UX Designer: [Name]

### Technologies Used
- WordPress 5.8+
- WooCommerce 5.0+
- Elementor (optional integration)
- MySQL 5.7+
- PHP 7.4+

### Inspiration
- Shopify's unified sale system
- WooCommerce Dynamic Pricing
- Google Analytics behavior tracking
- Elementor's global color system

---

## Roadmap

### Q1 2025 (Current)
- ✅ Architecture design complete
- ✅ Database schema finalized
- 🔄 Core development starting
- 🔄 GDPR consent system implementation

### Q2 2025
- 🔲 MVP release (Phase 1 features)
- 🔲 Beta testing with 10 users
- 🔲 WordPress.org submission
- 🔲 Documentation complete

### Q3 2025
- 🔲 Public release
- 🔲 100+ active installations
- 🔲 Phase 2 development (advanced features)

### Q4 2025
- 🔲 Smart pricing suggestions launch
- 🔲 Analytics dashboard
- 🔲 1,000+ active installations

### 2026
- 🔲 AI-powered auto-pricing (Phase 3)
- 🔲 Mobile app for managing sales
- 🔲 Multi-site support

---

## FAQ

### Is GHSales free?
Pricing model TBD. Options: freemium (basic free, pro paid) or paid-only.

### Does it work with my theme?
Yes! Color override uses multiple layers (Elementor, CSS variables, !important) to work with any theme.

### Will it slow down my site?
No. GHSales is optimized for performance with caching and lazy loading.

### Is it GDPR compliant?
Yes. Built from the ground up with GDPR compliance as a core feature.

### Can I use it without Elementor?
Yes. Elementor is optional (for color override). All other features work independently.

### Does it work with other sale plugins?
Not recommended. GHSales is designed to replace other sale/upsell plugins for best results.

### Can I customize the upsell templates?
Yes. Templates are in `templates/` folder and can be overridden in your theme.

### How do I migrate from another sale plugin?
Migration tools will be provided in future versions.

---

**Ready to transform your WooCommerce store?**

[Download Beta] (coming soon) | [View Demos] (coming soon) | [Read Full Documentation](./PROJECT_CONTEXT.md)
