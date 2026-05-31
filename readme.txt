=== Posts to Newsletter ===
Contributors: YOUR_WPORG_USERNAME
Tags: newsletter, email newsletter, post digest, email campaign, mailchimp
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hand-pick and order posts into a branded email, then push a draft to Mailchimp or Campaign Monitor. Drafts only — you review and send.

== Description ==

**Posts to Newsletter** turns your published posts into a branded email newsletter. Search your posts, click to include them, drag to set the order, then push a ready-to-review **draft** to your email platform. It never sends — a human always reviews and sends inside the platform.

Branding defaults from your WordPress site (title and custom logo) and everything is overridable on the settings page, so it works on any site out of the box.

= What it does =

* **Curation screen** — search all your posts, click to include, drag to reorder. Changes save automatically.
* **Branded email** — a responsive, two-column article layout with hero image, logo, category labels, author bylines and a subscribe button. Colours and branding are configurable.
* **One-click push to draft** — creates a draft campaign on the platform you choose. It never sends.
* **Platform-aware merge tags** — your intro personalisation and the unsubscribe / preferences / web-version / address tags are written in each platform's own syntax automatically.
* **Co-Authors Plus aware** — bylines respect Co-Authors Plus when it's active.

= Email platforms =

This release connects to **Mailchimp** and **Campaign Monitor**. More providers are planned. Posts to Newsletter is an independent plugin and is not affiliated with, or endorsed by, Mailchimp or Campaign Monitor; those names are used only to describe the integrations.

= Privacy =

The plugin only talks to the email platform you configure, using the API key you provide. It does not phone home or send your content anywhere else.

== Installation ==

1. Upload the `posts-to-newsletter` folder to `/wp-content/plugins/`, or install it through **Plugins → Add New**.
2. Activate the plugin through the **Plugins** menu.
3. Go to **Newsletter → Settings** and set your branding, sender details and platform API key(s).
4. Open **Newsletter**, add and order your articles, then click **Push to Mailchimp** or **Push to Campaign Monitor**.

= API keys via wp-config.php (recommended) =

Instead of storing keys in the database, define them as constants:

`define( 'CNL_MAILCHIMP_API_KEY', 'your-key-here-us13' );`
`define( 'CNL_CM_API_KEY', 'your-key-here' );`

When a constant is set, the matching settings field is hidden and the constant takes precedence.

== Frequently Asked Questions ==

= Does it send the email? =

No. It only ever creates a **draft** campaign on your platform. You review and send from inside Mailchimp or Campaign Monitor.

= Do I need an account with an email platform? =

Yes. You need a Mailchimp API key and/or a Campaign Monitor (Create Send) API key, and at least one audience/list configured on that platform.

= Which posts can I include? =

Any published post. Search by keyword or pick from your most recent posts.

= Can I change the newsletter design? =

Branding (logo, hero image, colours, sender details, intro text and article image size) is configurable on the settings page.

= Is it tied to one site or theme? =

No. Branding defaults are pulled from the current site and everything is overridable, so it works on any site.

== Screenshots ==

1. The curation screen — search, add and drag posts into order.
2. The settings screen — branding, sender and platform connections.
3. The rendered newsletter email.

== Changelog ==

= 1.0.0 =
* Initial release: post curation, branded email rendering, and draft push to Mailchimp and Campaign Monitor.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
