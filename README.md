# Posts to Newsletter

A WordPress plugin that turns your published posts into a branded HTML email. Hand-pick and order posts, then preview the newsletter at a public URL ready to **import into your email platform**. Reusable on any site: branding defaults from the WordPress site (title, custom logo) and everything is overridable on the settings page.

This is the free core. One-click push of the newsletter as a draft to **Mailchimp** or **Campaign Monitor** is provided by the premium add-on, **Posts to Newsletter Pro**.

## Features (free)

- **Curation screen** - search all posts, click to include, drag to set the order (auto-saves).
- **Branded email** - responsive two-column article grid with hero, logo, category labels, author bylines, date/author pills and a Subscribe button. Colours and branding are configurable.
- **Settings page** - branding & content, sender details, and article image size.
- **Public render endpoint** - the newsletter is rendered at a URL you can preview and copy into your email platform. The HTML is **platform-aware**: `{firstname}` and the unsubscribe / preferences / web-version / address tags are written in each platform's own syntax.
- **Co-Authors Plus aware** - bylines respect Co-Authors Plus when it's active.

## Premium: one-click push

[Posts to Newsletter Pro](https://github.com/davemac/posts-to-newsletter-pro) adds **Push to Mailchimp** / **Push to Campaign Monitor** buttons that create a ready-to-review **draft** on the platform (it never sends). Without it, use the platform-ready preview URLs below to import manually.

## Requirements

- WordPress 6.0+, PHP 8.0+.

## Installation

Copy this folder into `wp-content/plugins/` and activate it from the **Plugins** menu. On activation it registers the public render endpoint.

## Usage

1. **Newsletter** (admin menu) → search and add articles, drag to order.
2. **Newsletter → Settings** → set branding, sender details and the article image size.
3. Copy a platform-ready preview URL (below) and import it into your email platform — or install **Posts to Newsletter Pro** to push a draft in one click.

## Render endpoint

Used for preview and as the manual-import / Campaign Monitor fetch URL:

- `/ptn-newsletter/?ptn_platform=mailchimp`
- `/ptn-newsletter/?ptn_platform=campaignmonitor`

Must be publicly reachable if a platform fetches it server-side.

## Extending

The core carries no email-platform integration. Add-ons attach via four hooks:

| Hook | Type | Purpose |
|------|------|---------|
| `posts_to_newsletter_settings_defaults` | filter | Register extra default settings keys |
| `posts_to_newsletter_settings_save` | filter | Sanitise and merge extra submitted fields |
| `posts_to_newsletter_settings_cards` | action | Render extra cards in the settings form |
| `posts_to_newsletter_curation_actions` | action | Render extra buttons in the curation action bar |

See [CONTRIBUTING.md](CONTRIBUTING.md) for the full architecture and coding standards.

## Development

- No build step - plain PHP, vanilla jQuery (bundled with WP), and hand-written CSS.
- Lightweight SPL autoloader: `PostsToNewsletter\` → `src/`.
- HTTP via `wp_remote_*` (no third-party SDKs).

## Credits

Built and maintained by [DMC Web](https://dmcweb.com.au).
