# Curated Newsletter

A WordPress plugin to hand-pick and order recent posts into a branded HTML email, then push a ready-to-review **draft** to **MailChimp** or **Campaign Monitor**. Drafts only — a human reviews and sends inside the platform.

Reusable on any site: branding defaults from the WordPress site (title, custom logo) and everything is overridable on the settings page.

## Features

- **Curation screen** — search all posts, click to include, drag to set the order (auto-saves).
- **Branded email** — responsive two-column article grid with hero, logo, category labels, author bylines, date/author pills and a Subscribe button. Colours and branding are configurable.
- **Settings page** — branding & content, sender details, and MailChimp + Campaign Monitor credentials, with API-fed audience/client/list pickers and connection status.
- **One-click push to draft** — creates a draft campaign on the chosen platform. Never sends.
- **Platform-aware merge tags** — `{firstname}` and the unsubscribe / preferences / web-version / address tags are rendered in each platform's syntax automatically.

## Requirements

- WordPress 6.0+, PHP 8.0+.
- For pushing: a MailChimp API key and/or a Campaign Monitor (Create Send) API key.

## Installation

Copy this folder to `wp-content/plugins/curated-newsletter/` and activate. On activation it registers the public render endpoint and migrates any legacy `colacnew_newsletter_*` options.

## Usage

1. **Newsletter** (admin menu) → search and add articles, drag to order.
2. **Newsletter → Settings** → set branding, sender, and the platform API keys; pick an audience (MailChimp) and client + list (Campaign Monitor).
3. Click **Push to MailChimp** or **Push to Campaign Monitor** → open the draft in the platform → review and send.

## Configuration

- **API keys** can be set on the settings page or, more securely, via `wp-config.php` constants:
  - `define( 'CNL_MAILCHIMP_API_KEY', '…-us13' );`
  - `define( 'CNL_CM_API_KEY', '…' );`
- **Public render endpoint** (used for preview and as Campaign Monitor's fetch URL):
  - `/cnl-newsletter/?cnl_platform=mailchimp`
  - `/cnl-newsletter/?cnl_platform=campaignmonitor`
  - Must be publicly reachable for the Campaign Monitor push (it fetches the URL server-side).

## How the pushes work

- **MailChimp** — Marketing API: `GET /lists` → `POST /campaigns` (draft) → `PUT /campaigns/{id}/content` with the rendered HTML.
- **Campaign Monitor** — Create Send API: `POST /campaigns/{clientId}.json` with `HtmlUrl` pointing at the public render endpoint; CM fetches and stores the HTML as a draft.

Both create **drafts**; the send endpoints are never called.

## Development

- No build step — plain PHP, vanilla jQuery (bundled with WP), and CSS.
- Lightweight PSR-4 autoloader: `Cnl\` → `src/`.
- HTTP via `wp_remote_*` (no third-party SDKs).
