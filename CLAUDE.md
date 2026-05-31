# CLAUDE.md — Posts to Newsletter

Guidance for Claude Code when working in this plugin. The user's global
`~/.claude/CLAUDE.md` (The Code Company PHP/WP standards) still applies and is
not repeated here — this file adds plugin-specific context only.

## What this is

A **standalone, reusable WordPress plugin**: hand-pick and order published posts
into a branded HTML email, then push a ready-to-review **draft** to **Mailchimp**
or **Campaign Monitor** via their APIs. It never sends — a human reviews and sends
inside the platform.

- No framework (no WPMVC). Own bootstrap + lightweight SPL autoloader.
- Namespace `PostsToNewsletter\`, prefix `ptn_` / `PTN_` / `ptn-`.
- Raw `wp_remote_*` for both APIs — no SDKs (conservative dependencies).
- Developed inside the `colacnew` dev site (`https://colacnew.localhost`) for live
  testing, but it is its **own deliverable** with its own private git repo:
  `github.com/davemac/posts-to-newsletter` (branch `main`).

## Status

- **LIVE on Colac Herald production** (since 2026-05-31). Installed via rsync,
  active, data migrated. See *Colac Herald production* below.
- **Plugin Check (PCP): 0 errors.** This is the WordPress.org gate.
- The business plan is **freemium**: a free WordPress.org version + a premium
  version. The split is **not yet implemented** — see *What's still needed*.

## Directory structure

```
posts-to-newsletter/
  posts-to-newsletter.php      # main file: header, constants, autoloader, boot
  uninstall.php                # deletes ptn_settings + ptn_newsletter_post_ids
  readme.txt                   # WordPress.org readme (Stable tag 1.0.0)
  README.md                    # GitHub readme
  .distignore                  # files excluded from the distributed/release zip
  src/
    Plugin.php                 # boot(): wires all hooks; asset_version(); activate()
    Settings.php               # ptn_settings storage + getters + settings page
    Curation.php               # admin curation screen + selection/search REST
    Selection.php              # shared: stored IDs, ordered posts, byline (CAP-aware)
    Renderer.php               # platform-aware email HTML + public render endpoint
    Push.php                   # REST routes that create platform drafts
    Integrations/
      MailchimpClient.php      # get_audiences(), create_draft()  (Marketing API)
      CampaignMonitorClient.php# get_clients(), get_lists(), create_draft() (Create Send)
  templates/
    email.php                  # the email view (required inside Renderer::render)
    curation-page.php          # the curation admin screen
    settings-page.php          # the settings admin screen
  assets/
    css/admin.css              # hand-written (NO build step)
    js/admin.js                # curation UI (drag/add/search/push)
    js/settings.js             # media picker + colour picker
```

## Architecture (classes)

| Class | Role | Key members |
|-------|------|-------------|
| `Plugin` | Bootstrap. `boot()` registers every hook | `asset_version()` (filemtime cache-bust), `activate()` (register endpoint + flush rewrites) |
| `Settings` | One option `ptn_settings`; settings page | `all()`, `get()`, `mailchimp_key()`/`cm_key()` (wp-config constant wins), `logo_url()`/`hero_url()`, `handle_save()` (admin-post) |
| `Curation` | Admin curation screen + REST | `save_selection`, `search_articles`, `render_item` |
| `Selection` | Shared selection helpers | `OPTION`, `sanitize()`, `ids()`, `posts()`, `ordered()`, `byline()` |
| `Renderer` | Email HTML + public endpoint | `register_endpoint()`, `maybe_render()`, `render($platform)`, `render_card()`, `tokens($platform)` |
| `Push` | REST: create drafts (cap `edit_others_posts`) | `/push/mailchimp`, `/push/campaignmonitor` — **never** calls a send endpoint |
| `MailchimpClient` | Marketing API, Basic auth `anystring:key`, dc from key suffix (`-usXX`) | `get_audiences()`, `create_draft()` = POST `/campaigns` then PUT `/campaigns/{id}/content` |
| `CampaignMonitorClient` | Create Send API v3.3, Basic auth `key:x` | `get_clients()`, `get_lists($clientId)`, `create_draft()` = POST `/campaigns/{clientId}.json` with `HtmlUrl` (CM fetches it server-side) |

## Identifiers (do not rename without migration — see Gotchas)

| Thing | Value |
|-------|-------|
| Options | `ptn_settings`, `ptn_newsletter_post_ids` |
| wp-config constants (API key override) | `PTN_MAILCHIMP_API_KEY`, `PTN_CM_API_KEY` |
| REST namespace | `posts-to-newsletter/v1` (routes: `/selection`, `/search`, `/push/mailchimp`, `/push/campaignmonitor`) |
| Public render endpoint | `/ptn-newsletter/` (query vars `ptn_newsletter`, `ptn_platform`) |
| Admin menu slug / settings slug | `posts-to-newsletter` / `posts-to-newsletter-settings` |
| admin-post save action | `ptn_save_settings` |
| JS localize object / handles | `ptnNewsletter` / `ptn-admin`, `ptn-settings` |
| CSS classes | `ptn-*` |
| Capabilities | curation/push `edit_others_posts`; settings `manage_options` |
| Text domain | `posts-to-newsletter` (equals slug — required for .org) |

## Platform merge-tag mapping (in `Renderer::tokens()`)

| Purpose | Campaign Monitor | Mailchimp |
|---------|------------------|-----------|
| First name (`{firstname}` token) | `[firstname,fallback=there]` | `*\|FNAME\|*` |
| Unsubscribe | `<unsubscribe>` | `*\|UNSUB\|*` |
| Web version | `<webversion>` | `*\|ARCHIVE\|*` |
| Preferences | `<preferences>` | `*\|UPDATE_PROFILE\|*` |
| Physical address | (CM auto-injects) | `*\|HTML:LIST_ADDRESS_HTML\|*` |

## Settings (`ptn_settings` keys; defaults in `Settings::defaults()`)

`site_name`, `logo_id`, `hero_id`, `brand_color` (`#cc3300`), `accent_color`
(`#e32441`), `subscribe_url`, `intro` (uses `{firstname}`), `image_size`,
`from_name`, `from_email`, `reply_to`, `mailchimp_api_key`,
`mailchimp_audience_id`, `cm_api_key`, `cm_client_id`, `cm_list_id`.
Branding defaults derive from the site (`get_bloginfo`, custom logo). API keys
render blank and are only overwritten when a new value is submitted.

## Build, test, lint

There is **no JS/CSS build** — `assets/` are hand-written static files;
`Plugin::asset_version()` cache-busts via `filemtime`.

| Task | Command (run from anywhere in the colacnew site tree) |
|------|-------|
| PHP syntax | `php -l <file>` |
| **Plugin Check (.org gate)** | `wp plugin check posts-to-newsletter --severity=5` (PCP is installed+active on local dev) |
| Render endpoint smoke test | `curl -sk "https://colacnew.localhost/ptn-newsletter/?ptn_platform=campaignmonitor"` then `…?ptn_platform=mailchimp` |
| After changing the rewrite endpoint | `wp rewrite flush` |

There is no bundled PHPCS in this plugin; rely on PCP plus the global Code
Company standards. Local login: `admin` / `dmcweb`.

## Distribution & deployment

- **Server-managed.** The plugin is NOT shipped by the colacnew GitHub Action
  (`.deployignore` excludes `plugins/`). It is installed/updated independently.
- **Update prod** = rsync the folder again (alias adds `-avzW --progress`; exclude
  dev files): `rsync --exclude='.git' --exclude='.gitignore' --exclude='.distignore' --exclude='.github' --exclude='README.md' --exclude='node_modules' ./ colac-l:www/wp-content/plugins/posts-to-newsletter/`
- **Release zip** for WordPress.org / manual install: build honouring `.distignore`
  (e.g. `wp dist-archive`), then run PCP against the **built zip** (the
  `.gitignore`/`.distignore` "hidden_files" PCP warnings only appear against the
  working tree, not the zip).
- `@prod` alias (`wp @prod …`) = ssh `colac-l`, path `www`; resolves from anywhere
  inside the colacnew site tree. **Only connect to prod with explicit permission.**

## Colac Herald production (live)

Live values currently set in prod `ptn_settings`:

| Key | Value |
|-----|-------|
| `hero_id` | 237465 |
| `image_size` | `md_img_16_9` (theme hard-crop, watermarked) |
| `subscribe_url` | `https://colacherald.com.au/subscribe/` |
| `accent_color` / `brand_color` | `#e32441` / `#cc3300` |
| `from_email` / `reply_to` | `no-reply@colacherald.com.au` |
| selection (`ptn_newsletter_post_ids`) | `[237443, 237439, 237435, 237431]` |
| Mailchimp / CM API keys | **EMPTY** — add via Newsletter → Settings to enable push |

Until keys are added, the workflow is **manual import** of the
`/ptn-newsletter/?ptn_platform=…` preview URLs into the platform. The old
`colacnew` mu-plugin newsletter (and its `FeedImageController`) was removed from
prod, so the public RSS `/feed/` no longer carries featured images.

## Safety guardrails (CRITICAL)

- **Drafts only.** Never call any platform send endpoint. The push features create
  a draft and stop.
- **Mailchimp account is `us13` and contains a REAL client, "i3 Insights".** NEVER
  read, modify, or target their audiences or campaigns. For live push testing,
  create a **new dedicated test audience** with `info@davidmcdonald.org` and push a
  test draft to it only.
- API keys go in the settings page or wp-config constants — **never** pasted into
  chat. Constants win over stored options.
- Do not connect to prod (`colac-l` / `@prod`) without explicit permission. Preview
  any prod DB write before running it.

## What's still needed (roadmap)

| Priority | Item | Notes |
|----------|------|-------|
| **1 — blocks .org free release** | **Premium separation** | The free .org build must not contain the paid push layer. Free = curate + render + manual URL import. Premium = one-click API push (`Integrations/`, `Push.php`, the API cards in `settings-page.php`). **First decide the mechanism: Freemius vs free-core + premium add-on (EDD/own store)** — that drives the physical split. |
| 2 | Licensing / update delivery | Follows the mechanism decision. |
| 3 | .org submission assets | screenshots (1 curation, 2 settings, 3 email), banner, icon — go in the SVN `/assets` dir, not the plugin zip. Optional `languages/*.pot`. |
| 4 | Live API push testing | Once keys provided — test audience only, never i3 Insights. |

## Conventions & gotchas

- **Template variable PCP suppressions are intentional.** `settings-page.php` and
  `email.php` carry `// phpcs:disable …PrefixAllGlobals.NonPrefixedVariableFound`
  because they are view partials `require`d inside a method — their variables are
  method-scoped, not global (PCP can't see that). Keep the disable.
- **`load_plugin_textdomain` was deliberately removed.** PCP discourages it for
  .org-hosted plugins (WP auto-loads translations). If you ever ship a
  **self-hosted premium** build, re-add it.
- **Renaming options/REST/endpoint is now risky** — the plugin is LIVE with prod
  data under `ptn_*`. A rename would orphan prod data and needs a migration. The
  pre-release free-rename window is closed.
- **Co-Authors Plus aware:** `Selection::byline()` uses `get_coauthors()` when CAP
  is active, else the post author.
- **Australian English**, full ternaries (no `?:`), Yoda conditions, escape all
  output, nonce + capability on every write — per the global standards.
