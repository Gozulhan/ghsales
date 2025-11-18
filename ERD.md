# Entity Relationship Diagram (ERD)
## GHSales Plugin Database Schema

**Version:** 1.0.0
**Date:** 2025-01-18
**Database:** MySQL 5.7+

---

## Visual ERD (ASCII Diagram)

```
┌─────────────────────────────────────┐
│     wp_ghsales_events               │
│─────────────────────────────────────│
│ PK  id                 BIGINT       │◄──┐
│     event_name         VARCHAR(255) │   │
│     event_type         VARCHAR(50)  │   │
│     start_date         DATETIME     │   │
│     end_date           DATETIME     │   │
│     status             VARCHAR(20)  │   │
│ FK  color_scheme_id    BIGINT       │───┐
│     settings           LONGTEXT     │   │
│     created_at         DATETIME     │   │
│     updated_at         DATETIME     │   │
└─────────────────────────────────────┘   │
        ▲                                  │
        │                                  │
        │ 1:N                              │
        │                                  │
┌───────┴─────────────────────────────┐   │
│     wp_ghsales_rules                │   │
│─────────────────────────────────────│   │
│ PK  id                 BIGINT       │   │
│ FK  event_id           BIGINT       │───┘
│     rule_type          VARCHAR(50)  │
│     applies_to         VARCHAR(50)  │
│     target_ids         TEXT         │
│     discount_value     DECIMAL(10,2)│
│     conditions         LONGTEXT     │
│     priority           INT          │
└─────────────────────────────────────┘


┌─────────────────────────────────────┐
│   wp_ghsales_color_schemes          │
│─────────────────────────────────────│
│ PK  id                 BIGINT       │◄──┐
│     scheme_name        VARCHAR(255) │   │
│     primary_color      VARCHAR(7)   │   │
│     secondary_color    VARCHAR(7)   │   │
│     accent_color       VARCHAR(7)   │   │
│     text_color         VARCHAR(7)   │   │
│     background_color   VARCHAR(7)   │   │
│     is_active          TINYINT(1)   │   │
│     created_at         DATETIME     │   │
└─────────────────────────────────────┘   │
                                           │
                                (FK from events)


┌─────────────────────────────────────┐
│   wp_ghsales_user_activity          │
│─────────────────────────────────────│
│ PK  id                 BIGINT       │
│     session_id         VARCHAR(100) │───┐
│     user_id            BIGINT       │   │
│     activity_type      VARCHAR(50)  │   │ Links to WP Users
│     product_id         BIGINT       │   │ (optional FK)
│     category_id        BIGINT       │   │
│     search_query       VARCHAR(255) │   │
│     meta_data          LONGTEXT     │   │
│     ip_address         VARCHAR(45)  │   │
│     user_agent         TEXT         │   │
│     consent_given      TINYINT(1)   │   │
│     timestamp          DATETIME     │   │
└─────────────────────────────────────┘   │
                                           │
                                           │
┌─────────────────────────────────────┐   │
│   wp_ghsales_consent_log            │   │
│─────────────────────────────────────│   │
│ PK  id                 BIGINT       │   │
│     session_id         VARCHAR(100) │───┤
│     user_id            BIGINT       │   │
│     consent_type       VARCHAR(50)  │   │
│     consent_given      TINYINT(1)   │   │
│     ip_address         VARCHAR(45)  │   │
│     consent_date       DATETIME     │   │
└─────────────────────────────────────┘   │
                                           │
                                           │
┌─────────────────────────────────────┐   │
│   wp_ghsales_upsell_cache           │   │
│─────────────────────────────────────│   │
│ PK  id                 BIGINT       │   │
│     context_type       VARCHAR(50)  │   │
│     context_id         BIGINT       │   │
│     user_id            BIGINT       │   │
│     session_id         VARCHAR(100) │───┘
│     recommended_prods  TEXT         │
│     expires_at         DATETIME     │
│     created_at         DATETIME     │
└─────────────────────────────────────┘


┌─────────────────────────────────────┐
│   wp_ghsales_product_stats          │
│─────────────────────────────────────│
│ PK  product_id         BIGINT       │
│     views_total        INT          │
│     views_7days        INT          │
│     views_30days       INT          │
│     conversions_total  INT          │
│     conversions_7days  INT          │
│     revenue_total      DECIMAL(10,2)│
│     profit_margin      DECIMAL(5,2) │
│     last_updated       DATETIME     │
└─────────────────────────────────────┘
        │
        │ Links to WooCommerce
        │ wp_posts (product_id)
        ▼
```

---

## Table Definitions

### 1. wp_ghsales_events
**Purpose:** Stores sale event definitions (Black Friday, Halloween, etc.)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique event identifier |
| event_name | VARCHAR(255) | NOT NULL | Display name (e.g., "Black Friday 2025") |
| event_type | VARCHAR(50) | NOT NULL | Type: 'manual', 'auto', 'suggested' |
| start_date | DATETIME | NOT NULL | When event becomes active |
| end_date | DATETIME | NOT NULL | When event ends |
| status | VARCHAR(20) | DEFAULT 'draft' | Status: 'draft', 'active', 'scheduled', 'ended' |
| color_scheme_id | BIGINT UNSIGNED | NULL, FK → color_schemes.id | Linked color scheme (optional) |
| settings | LONGTEXT | NULL | JSON: Display settings, templates, etc. |
| created_at | DATETIME | NOT NULL | Record creation timestamp |
| updated_at | DATETIME | NOT NULL | Last modification timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_status (status)
- INDEX idx_dates (start_date, end_date)
- FOREIGN KEY (color_scheme_id) REFERENCES wp_ghsales_color_schemes(id) ON DELETE SET NULL

**Sample Data:**
```sql
INSERT INTO wp_ghsales_events VALUES (
    1,
    'Black Friday 2025',
    'manual',
    '2025-11-24 00:00:00',
    '2025-11-27 23:59:59',
    'scheduled',
    2, -- links to Black Friday color scheme
    '{"show_in_cart":true,"show_in_menu":true}',
    '2025-01-18 10:00:00',
    '2025-01-18 10:00:00'
);
```

---

### 2. wp_ghsales_rules
**Purpose:** Individual discount rules for sale events (1 event can have many rules)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique rule identifier |
| event_id | BIGINT UNSIGNED | NOT NULL, FK → events.id | Parent sale event |
| rule_type | VARCHAR(50) | NOT NULL | Type: 'percentage', 'bogo', 'buy_x_get_y', 'spend_threshold' |
| applies_to | VARCHAR(50) | NOT NULL | Target: 'products', 'categories', 'tags', 'all' |
| target_ids | TEXT | NULL | Comma-separated IDs (product/category/tag IDs) |
| discount_value | DECIMAL(10,2) | NOT NULL | Discount amount (% or fixed) |
| conditions | LONGTEXT | NULL | JSON: Complex conditions (min qty, user roles, etc.) |
| priority | INT | DEFAULT 0 | Priority when multiple rules match (higher = first) |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_event (event_id)
- FOREIGN KEY (event_id) REFERENCES wp_ghsales_events(id) ON DELETE CASCADE

**Sample Data:**
```sql
INSERT INTO wp_ghsales_rules VALUES (
    1,
    1, -- Black Friday event
    'bogo',
    'categories',
    '15,23', -- Category IDs
    0.00, -- Not used for BOGO
    '{"buy":1,"get":1,"free":true}',
    10 -- High priority
);

INSERT INTO wp_ghsales_rules VALUES (
    2,
    1,
    'percentage',
    'all',
    NULL,
    10.00, -- 10% discount
    '{"min_cart_total":50.00}',
    5 -- Lower priority
);
```

---

### 3. wp_ghsales_color_schemes
**Purpose:** Color palette definitions for sitewide theming

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique scheme identifier |
| scheme_name | VARCHAR(255) | NOT NULL | Display name (e.g., "Black Friday Colors") |
| primary_color | VARCHAR(7) | NOT NULL | Hex color code (e.g., #000000) |
| secondary_color | VARCHAR(7) | NOT NULL | Hex color code |
| accent_color | VARCHAR(7) | NOT NULL | Hex color code |
| text_color | VARCHAR(7) | NOT NULL | Hex color code |
| background_color | VARCHAR(7) | NOT NULL | Hex color code |
| is_active | TINYINT(1) | DEFAULT 0 | Only one can be active at a time |
| created_at | DATETIME | NOT NULL | Record creation timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_active (is_active)

**Constraints:**
- Only one record can have is_active = 1 at a time (enforced in application logic)

**Sample Data:**
```sql
INSERT INTO wp_ghsales_color_schemes VALUES (
    1,
    'Default Theme',
    '#3498db',
    '#2c3e50',
    '#e74c3c',
    '#333333',
    '#ffffff',
    1, -- Active
    '2025-01-18 10:00:00'
);

INSERT INTO wp_ghsales_color_schemes VALUES (
    2,
    'Black Friday',
    '#000000',
    '#1a1a1a',
    '#ffd700',
    '#ffffff',
    '#0a0a0a',
    0,
    '2025-01-18 10:05:00'
);
```

---

### 4. wp_ghsales_user_activity
**Purpose:** Track user behavior (views, searches, clicks) for personalization

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique activity record |
| session_id | VARCHAR(100) | NOT NULL | WooCommerce session ID (for guests) |
| user_id | BIGINT UNSIGNED | NULL | WordPress user ID (for logged-in users) |
| activity_type | VARCHAR(50) | NOT NULL | Type: 'view', 'search', 'add_to_cart', 'click', 'category_view' |
| product_id | BIGINT UNSIGNED | NULL | Product being viewed/clicked |
| category_id | BIGINT UNSIGNED | NULL | Category being browsed |
| search_query | VARCHAR(255) | NULL | Search term (if activity_type='search') |
| meta_data | LONGTEXT | NULL | JSON: Additional data (quantity, variant, etc.) |
| ip_address | VARCHAR(45) | NULL | Masked IP (GDPR compliant: 192.168.1.0) |
| user_agent | TEXT | NULL | Browser user agent string |
| consent_given | TINYINT(1) | DEFAULT 0 | Whether user consented to tracking |
| timestamp | DATETIME | NOT NULL | When activity occurred |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_session (session_id)
- INDEX idx_user (user_id)
- INDEX idx_product (product_id)
- INDEX idx_timestamp (timestamp)

**Sample Data:**
```sql
INSERT INTO wp_ghsales_user_activity VALUES (
    1,
    'wc_session_abc123',
    NULL, -- Guest user
    'view',
    456, -- Product ID
    NULL,
    NULL,
    NULL,
    '192.168.1.0', -- Masked IP
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64)...',
    1, -- Consent given
    '2025-01-18 14:23:15'
);
```

---

### 5. wp_ghsales_product_stats
**Purpose:** Aggregated product performance metrics for analytics and smart pricing

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| product_id | BIGINT UNSIGNED | PK | WooCommerce product ID |
| views_total | INT UNSIGNED | DEFAULT 0 | Total views all-time |
| views_7days | INT UNSIGNED | DEFAULT 0 | Views in last 7 days |
| views_30days | INT UNSIGNED | DEFAULT 0 | Views in last 30 days |
| conversions_total | INT UNSIGNED | DEFAULT 0 | Total purchases all-time |
| conversions_7days | INT UNSIGNED | DEFAULT 0 | Purchases in last 7 days |
| revenue_total | DECIMAL(10,2) | DEFAULT 0 | Total revenue generated |
| profit_margin | DECIMAL(5,2) | NULL | Profit margin % (for smart pricing) |
| last_updated | DATETIME | NOT NULL | Last time stats were updated |

**Indexes:**
- PRIMARY KEY (product_id)
- INDEX idx_views_7days (views_7days)
- INDEX idx_conversions_7days (conversions_7days)

**Notes:**
- product_id links to wp_posts.ID (WooCommerce products)
- Automatically updated via cron jobs (reset 7-day/30-day counters)

**Sample Data:**
```sql
INSERT INTO wp_ghsales_product_stats VALUES (
    456, -- Product ID
    1250, -- Total views
    85, -- Views last 7 days
    320, -- Views last 30 days
    42, -- Total conversions
    5, -- Conversions last 7 days
    2450.00, -- Total revenue
    35.50, -- Profit margin %
    '2025-01-18 15:00:00'
);
```

---

### 6. wp_ghsales_upsell_cache
**Purpose:** Cache calculated upsell recommendations for performance

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique cache entry |
| context_type | VARCHAR(50) | NOT NULL | Context: 'cart', 'product', 'homepage' |
| context_id | BIGINT UNSIGNED | NULL | Product ID (for product context) |
| user_id | BIGINT UNSIGNED | NULL | User ID (for personalized recommendations) |
| session_id | VARCHAR(100) | NULL | Session ID (for guest recommendations) |
| recommended_products | TEXT | NOT NULL | JSON: Array of product IDs with scores |
| expires_at | DATETIME | NOT NULL | When cache expires (refresh after this) |
| created_at | DATETIME | NOT NULL | When cache was created |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_context (context_type, context_id)
- INDEX idx_user (user_id)
- INDEX idx_expires (expires_at)

**Notes:**
- Cache TTL: 1 hour for logged-in users, 30 minutes for guests
- Expired cache entries are auto-deleted by cron job

**Sample Data:**
```sql
INSERT INTO wp_ghsales_upsell_cache VALUES (
    1,
    'product',
    456, -- Viewing product 456
    123, -- Logged-in user ID
    NULL,
    '[{"id":789,"score":85},{"id":234,"score":72},{"id":567,"score":68}]',
    '2025-01-18 16:00:00', -- Expires in 1 hour
    '2025-01-18 15:00:00'
);
```

---

### 7. wp_ghsales_consent_log
**Purpose:** GDPR compliance - log user consent decisions

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique log entry |
| session_id | VARCHAR(100) | NOT NULL | Session identifier |
| user_id | BIGINT UNSIGNED | NULL | User ID (if logged in) |
| consent_type | VARCHAR(50) | NOT NULL | Type: 'analytics', 'marketing', 'tracking' |
| consent_given | TINYINT(1) | NOT NULL | 1 = consented, 0 = rejected |
| ip_address | VARCHAR(45) | NULL | Masked IP address (GDPR compliant) |
| consent_date | DATETIME | NOT NULL | When consent was given/rejected |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_session (session_id)
- INDEX idx_user (user_id)

**Legal Requirement:**
- Must be retained for proof of consent
- Must allow data export for user rights (GDPR Article 20)

**Sample Data:**
```sql
INSERT INTO wp_ghsales_consent_log VALUES (
    1,
    'wc_session_abc123',
    NULL,
    'analytics',
    1, -- Consent given
    '192.168.1.0', -- Masked
    '2025-01-18 14:00:00'
);

INSERT INTO wp_ghsales_consent_log VALUES (
    2,
    'wc_session_abc123',
    NULL,
    'marketing',
    0, -- Consent rejected
    '192.168.1.0',
    '2025-01-18 14:00:00'
);
```

---

## Relationships

### One-to-Many (1:N)
1. **events → rules**
   - One sale event has many discount rules
   - CASCADE DELETE: When event is deleted, all rules are deleted

2. **color_schemes → events** (optional)
   - One color scheme can be used by many events
   - SET NULL: When scheme is deleted, events remain but lose color link

### Optional Foreign Keys
3. **user_activity.user_id → wp_users.ID**
   - Links activity to WordPress user (if logged in)
   - NULL for guest users

4. **product_stats.product_id → wp_posts.ID**
   - Links stats to WooCommerce product
   - Product must exist (enforced in application)

### Session Tracking
5. **session_id** appears in multiple tables:
   - user_activity
   - consent_log
   - upsell_cache
   - Links guest behavior across sessions (same session = same user)

---

## Data Flow

### Creating a Sale Event
```
1. Admin creates event in wp_ghsales_events
   ↓
2. Admin adds rules in wp_ghsales_rules (FK → events.id)
   ↓
3. Admin links color scheme (FK → color_schemes.id)
   ↓
4. Event activates (start_date reached)
   ↓
5. Rules are applied at checkout (query wp_ghsales_rules)
6. Color scheme activates (query wp_ghsales_color_schemes)
```

### Tracking User Behavior
```
1. User views product (consent required)
   ↓
2. Check wp_ghsales_consent_log (has user consented?)
   ↓
3. If yes: Insert into wp_ghsales_user_activity
   ↓
4. Update wp_ghsales_product_stats (increment views)
   ↓
5. Data used for upsell recommendations
```

### Showing Upsells
```
1. User opens cart/product page
   ↓
2. Check wp_ghsales_upsell_cache (is there valid cache?)
   ↓
3. If yes: Return cached recommendations
   ↓
4. If no: Calculate recommendations:
   - Query wp_ghsales_user_activity (what has user viewed?)
   - Query wp_ghsales_product_stats (what's trending?)
   - Calculate scores
   - Store in wp_ghsales_upsell_cache
   ↓
5. Display top N products
```

---

## Maintenance & Optimization

### Daily Cron Jobs
- Delete expired upsell cache entries
- Delete old user activity (>1 year)
- Update product stats aggregations

### Weekly Cron Jobs
- Reset views_7days counters
- Optimize tables (OPTIMIZE TABLE)

### Monthly Cron Jobs
- Reset views_30days counters
- Archive old sale events (status='ended')
- Clean up consent logs (retain 2 years minimum)

### Indexes to Monitor
- user_activity.timestamp (most queried)
- product_stats.views_7days (for trending)
- upsell_cache.expires_at (for cleanup)

---

## Storage Estimates

### For 10,000 Products, 1,000 Daily Users

| Table | Records/Year | Size Estimate |
|-------|--------------|---------------|
| events | ~50 | 10 KB |
| rules | ~200 | 50 KB |
| color_schemes | ~20 | 5 KB |
| user_activity | ~3.6M | 500 MB |
| product_stats | 10,000 | 1 MB |
| upsell_cache | ~5,000 | 2 MB |
| consent_log | ~365,000 | 50 MB |
| **TOTAL** | | **~553 MB/year** |

**Notes:**
- Largest table: user_activity (grows continuously)
- Mitigation: Archive or delete records older than 1 year
- With archiving: ~150 MB/year steady state

---

## Security Considerations

### SQL Injection Prevention
- All queries use prepared statements
- User input is sanitized before DB operations

### Data Privacy (GDPR)
- IP addresses are masked (last octet removed)
- consent_log proves user consent for tracking
- Users can request data deletion (wp_ghsales_user_activity)

### Access Control
- Only admin users can create/edit sale events
- Database tables use wp_ prefix (standard WordPress)
- No direct database access from frontend

---

## Migration Strategy

### Initial Install
```sql
-- Run installer script
-- Creates all 7 tables
-- Adds indexes
-- Seeds default color scheme
```

### Version Updates
```sql
-- Version 1.0 → 1.1 (example)
-- Add new column to events table
ALTER TABLE wp_ghsales_events
ADD COLUMN priority INT DEFAULT 0;

-- Add new index
CREATE INDEX idx_priority ON wp_ghsales_events(priority);
```

### Uninstall Cleanup
```sql
-- Option 1: Keep data (default)
-- Do nothing

-- Option 2: Delete all data
DROP TABLE IF EXISTS wp_ghsales_consent_log;
DROP TABLE IF EXISTS wp_ghsales_upsell_cache;
DROP TABLE IF EXISTS wp_ghsales_user_activity;
DROP TABLE IF EXISTS wp_ghsales_product_stats;
DROP TABLE IF EXISTS wp_ghsales_rules;
DROP TABLE IF EXISTS wp_ghsales_events;
DROP TABLE IF EXISTS wp_ghsales_color_schemes;

-- Delete options
DELETE FROM wp_options WHERE option_name LIKE 'ghsales_%';
```

---

**Document Status:** Complete
**Last Updated:** 2025-01-18
**Reviewed By:** Technical Lead
