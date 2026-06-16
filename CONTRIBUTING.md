# Contributing to Posts to Newsletter

Thanks for your interest in improving the plugin. This document covers the
architecture, coding standards, and how to test changes.

## What this plugin is

The free core of a freemium pair. It lets you hand-pick and order published posts
into a branded HTML email and preview it at a public render endpoint, ready to
import into your email platform. A separately distributed premium add-on adds
one-click push of that email as a draft to Mailchimp or Campaign Monitor; the core
exposes extension hooks the add-on attaches to (see *Extension hooks* below).

## Requirements

- PHP 8.0+
- WordPress 6.0+

## Architecture

No framework. The main file registers a small SPL autoloader for the
`PostsToNewsletter\` namespace (mapped to `src/`) and boots on `plugins_loaded`.

| Class | Role |
|-------|------|
| `Plugin` | Bootstrap: registers all hooks; activation flushes the rewrite endpoint |
| `Settings` | Stores branding/sender settings in one option (`ptn_settings`); settings page |
| `Curation` | Drag-and-drop curation screen + selection/search REST routes |
| `Selection` | Shared helpers: stored IDs, ordered posts, byline (Co-Authors Plus aware) |
| `Renderer` | Platform-aware email HTML + the public render endpoint |
| `Templates` | Registry of selectable email templates (free core ships one; add-ons register more) |

There is **no JavaScript/CSS build step** — `assets/` are hand-written static files,
cache-busted from their `filemtime`.

## Extension hooks (for add-ons)

The core stays free of any email-platform integration. Add-ons extend it through:

| Hook | Type | Purpose |
|------|------|---------|
| `posts_to_newsletter_settings_defaults` | filter | Register extra default keys in `ptn_settings` |
| `posts_to_newsletter_settings_save` | filter | Sanitise and merge extra submitted fields |
| `posts_to_newsletter_settings_cards` | action | Render extra cards inside the settings form |
| `posts_to_newsletter_templates` | filter | Register extra email templates, keyed by id (`label` + absolute `file`) |
| `posts_to_newsletter_platform_tokens` | filter | Add or override a platform's merge-tag tokens (`firstname` / `footer` / `preheader`) |
| `posts_to_newsletter_render_allowed` | filter | Gate the public render endpoint (return `false` to restrict access; default `true`) |
| `posts_to_newsletter_curation_actions` | action | Render general (non-platform) buttons in the curation action bar |
| `posts_to_newsletter_delivery_actions` | action | Render delivery controls (e.g. the push button) at the top of the curation Delivery panel |

## Coding standards

- WordPress Coding Standards (PHPCS).
- Escape all output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`); sanitise all
  input; use `$wpdb->prepare()` for SQL.
- Nonce + capability checks on every write (REST routes and admin-post handlers).
- Australian English in comments and docs. Full ternaries (no `?:`). Yoda conditions.
- Public hook names use the full `posts_to_newsletter_` prefix; internal
  options/constants/CSS use the `ptn_` / `PTN_` / `ptn-` short prefix.

## Testing

Run [Plugin Check](https://wordpress.org/plugins/plugin-check/) before submitting:

```bash
wp plugin check posts-to-newsletter --severity=5
```

Smoke-test the public render endpoint (run `wp rewrite flush` after changing it):

```bash
curl -s "https://example.test/ptn-newsletter/?ptn_platform=campaignmonitor"
curl -s "https://example.test/ptn-newsletter/?ptn_platform=mailchimp"
```

## Pull requests

Keep changes focused and match the surrounding style. Describe what you changed and
how you verified it. Note any user-facing or hook API changes.
