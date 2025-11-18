# GHSales Implementation Decisions

This document tracks important architectural and scope decisions made during development.

---

## GDPR Consent Management - REMOVED FROM SCOPE

**Date:** 2025-01-18
**Status:** FINAL DECISION - DO NOT REIMPLEMENT

### Decision
GHSales will **NOT** include built-in GDPR consent management. GDPR compliance will be handled by external cookie consent plugins (Cookiebot, CookieYes, etc.).

### Rationale
1. **Avoids Duplication:** Store owners already have preferred GDPR consent solutions installed
2. **Reduces Complexity:** No need to maintain consent banner UI, consent logging, or legal compliance updates
3. **Better UX:** One consent banner for entire site, not multiple plugin-specific banners
4. **Maintenance:** GDPR regulations change - let specialized plugins handle updates

### Implementation
- **GHSales tracks by default** (no consent checks in code)
- Store owners responsible for installing external GDPR plugin
- External cookie plugins will block/allow GHSales tracking scripts
- No `wp_ghsales_consent_log` table created
- No consent-related fields in `wp_ghsales_user_activity` table

### PRD & ERD Updates
- ✅ PRD Section 1.3 updated to reflect removal
- ✅ ERD Section 7 (consent_log table) marked as removed
- ✅ user_activity table schema updated (removed consent_given field)

### Code Cleanup Completed
**Date:** 2025-01-18
**Status:** ✅ COMPLETED

All GDPR consent code has been removed from the codebase:
- ✅ Deleted `includes/class-ghsales-gdpr.php` (entire file - 405 lines)
- ✅ Removed `wp_ghsales_consent_log` table creation from installer
- ✅ Removed `consent_given` field from `wp_ghsales_user_activity` table
- ✅ Removed 5 consent checks from tracker class methods
- ✅ Removed 10 GDPR-related methods from core class
- ✅ Added helper methods to tracker class (get_session_id, get_user_id, mask_ip, etc.)
- ✅ Updated plugin description (removed "GDPR-compliant")
- ✅ Updated admin notice to reflect external GDPR plugin requirement

**Files Modified:**
1. `includes/class-ghsales-installer.php` - Database schema
2. `includes/class-ghsales-tracker.php` - Tracking logic
3. `includes/class-ghsales-core.php` - Core plugin class
4. `ghsales.php` - Main plugin file

**Result:** GHSales now tracks by default. Store owners must use external cookie consent plugins (Cookiebot, CookieYes, etc.) for GDPR compliance.

---

## Color Scheme Integration with Sale Events

**Date:** 2025-01-18
**Status:** CONFIRMED IMPLEMENTED

### Question
Does the admin sale creation interface include a color scheme selector?

### Answer
**YES** - Color scheme selection is already implemented in the Event Settings meta box.

### Implementation Details
- File: `admin/class-ghsales-event-cpt.php:443-509`
- Location: Event Settings meta box (sidebar)
- Field: `_ghsales_color_scheme_id` (post meta)
- UI: Dropdown selector with "-- None --" option
- Query: Pulls from `wp_ghsales_color_schemes` table

### Code Reference
```php
// Line 497-507
<label for="ghsales_color_scheme"><?php esc_html_e( 'Color Scheme (optional)', 'ghsales' ); ?></label>
<select id="ghsales_color_scheme" name="ghsales_color_scheme_id" class="widefat">
    <option value=""><?php esc_html_e( '-- None --', 'ghsales' ); ?></option>
    <?php foreach ( $color_schemes as $scheme ) : ?>
        <option value="<?php echo esc_attr( $scheme->id ); ?>" <?php selected( $color_scheme_id, $scheme->id ); ?>>
            <?php echo esc_html( $scheme->scheme_name ); ?>
        </option>
    <?php endforeach; ?>
</select>
```

---

## Future Decisions

Track any major implementation decisions here to avoid confusion during development or future maintenance.

### Template
```
## [Decision Title]

**Date:** YYYY-MM-DD
**Status:** DRAFT / CONFIRMED / FINAL

### Decision
Brief description of what was decided

### Rationale
Why this decision was made

### Implementation
How this is implemented in code

### Related Files
- path/to/file.php:line
```
