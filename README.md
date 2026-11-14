# Gradiam Simple Admin

Simplified wp-admin for clients who are not WordPress users, by
[GradiamDigital](https://gradiamdigital.cz). Theme-independent and reusable on other client sites;
same structure as the GradiamDigital theme and plugins (WordPress Plugin Boilerplate: core class +
Loader + partial classes).

## What it does

- **Role "Site manager"** (`gradiam_client`, Czech "Správce webu"): edits pages, posts and custom post
  types with `capability_type => post`, media, categories; can publish but **cannot delete pages**;
  no plugins, themes, users, updates or WordPress settings. It also gets `manage_gb_bookings`
  (Gradiam Booking) and `gradiam_site_settings` (the site's own settings page).
- **Short menu**: only the items chosen in *Settings → Simple admin*. Hidden screens opened by URL
  redirect to the start screen. (Hiding is for clarity; access is controlled by the capabilities.)
- **Start screen** instead of the dashboard (also after login): a greeting, a tile per menu item with
  a short description and a count badge (e.g. new bookings first), "View the website", support contact.
- **Clean toolbar**: no WordPress logo, comments, "New", updates, search, command palette.
- **No foreign notices**: notices of core and other plugins (updates, reviews, ads) are removed;
  SCF/ACF, Polylang and GradiamDigital notices stay (filter `gradiam_simple_admin_keep_notices`).
- **Footer** with the support contact instead of "Thank you for creating with WordPress" and the version.
- **Profile** without colour schemes, application passwords and developer options;
  **block editor** without the code editor, Openverse, block directory and remote patterns.
- **"Simple view"** switch in the toolbar for administrators: preview the client's view with your own account.

The simplified admin applies to users with the capability `gradiam_simple_admin` who are not
administrators (`manage_options`), and to administrators with "Simple view" on.

## Integrations

| With | How |
|---|---|
| Chata Filipov theme | filter `chata_filipov_options_capability` → `gradiam_site_settings` opens "Chalet settings" to the role |
| Gradiam Booking | the role has `manage_gb_bookings` (bookings and booking settings) |
| Simple Custom Post Order | reordering needs `edit_posts`, which the role has |

## Filters

| Filter | Purpose |
|---|---|
| `gradiam_simple_admin_role_caps` | capabilities of the role (rebuilt on settings save or when `ROLE_VERSION` changes) |
| `gradiam_simple_admin_default_menu` | default menu slugs before the settings are saved |
| `gradiam_simple_admin_menu` | final list of visible top-level menu slugs |
| `gradiam_simple_admin_tile_descriptions` | tile descriptions keyed by menu slug |
| `gradiam_simple_admin_toolbar_remove` | toolbar nodes removed in the simple view |
| `gradiam_simple_admin_keep_notices` | regex of callback names whose admin notices stay |

## Lifecycle

- Activation creates the role; a plugin update with a new `ROLE_VERSION` rebuilds it automatically.
- Deactivation keeps the role (users keep their access, only the simplification disappears).
- Uninstall removes the role (its users become subscribers), the settings and the preview flags.

## Structure

```
gradiam-simple-admin.php            Header, constants, activation hook, gradiam_simple_admin_run()
uninstall.php
includes/
  class-gradiam-simple-admin.php          Core class: define_*_hooks()
  class-gradiam-simple-admin-loader.php   Hook registry
  class-gradiam-simple-admin-i18n.php     Text domain
  partials/
    class-gradiam-simple-admin-role.php      Role, capabilities, translated role name
    class-gradiam-simple-admin-settings.php  Settings → Simple admin
    class-gradiam-simple-admin-view.php      Menu, start screen, toolbar, notices, footer, profile, editor
admin/css/admin.css
languages/                          .pot + cs_CZ
```

## Development

```bash
composer install && composer lint
wp i18n make-pot . languages/gradiam-simple-admin.pot --exclude=node_modules,vendor
wp i18n make-mo languages
```
