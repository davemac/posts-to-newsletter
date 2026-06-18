=== DMC Posts to Newsletter Builder ===
Contributors: davemac
Tags: newsletter, email newsletter, post digest, email campaign, mailchimp
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hand-pick and order posts into a branded HTML email, then preview a platform-ready newsletter to import into your email service.

== Description ==

**DMC Posts to Newsletter Builder** turns your published posts into a branded email newsletter. Search your posts, click to include them, drag to set the order, then preview the finished newsletter at a public URL — ready to copy into your email platform.

Branding defaults from your WordPress site (title and custom logo) and everything is overridable on the settings page, so it works on any site out of the box.

= What it does =

* **Curation screen** - search all your posts, click to include, drag to reorder, with a live preview that updates as you go. Set the edition's subject line, write an intro greeting (with `{firstname}` personalisation), and choose an email template. Changes save automatically.
* **Branded email** - a responsive, two-column article layout with hero image, logo, category labels, author bylines and a subscribe button. Colours and branding are configurable.
* **Platform-ready preview** - the newsletter renders at a public URL in **Mailchimp** or **Campaign Monitor** flavour. Your intro personalisation and the unsubscribe / preferences / web-version / address tags are written in each platform's own merge-tag syntax, so you can import the HTML and the tags just work.
* **Co-Authors Plus aware** - bylines respect Co-Authors Plus when it's active.

= One-click push (premium add-on) =

The free plugin produces newsletter HTML you import into your platform manually. The premium add-on **Posts to Newsletter Pro** adds **Push to Mailchimp** and **Push to Campaign Monitor** buttons that create a ready-to-review **draft** on the platform in one click. It never sends — a human always reviews and sends inside the platform. Pro also adds more email templates to choose from on the curation screen.

= Compatibility note =

DMC Posts to Newsletter Builder is an independent plugin and is not affiliated with, or endorsed by, Mailchimp or Campaign Monitor; those names are used only to describe the newsletter formats it can produce.

= Privacy =

The free plugin does not phone home or send your content anywhere. It only renders the newsletter on your own site.

= Credits =

Built and maintained by DMC Web (https://dmcweb.com.au).

== Installation ==

1. Upload the `dmc-posts-to-newsletter-builder` folder to `/wp-content/plugins/`, or install it through **Plugins → Add New**.
2. Activate the plugin through the **Plugins** menu.
3. Go to **Newsletter → Settings** and set your branding, sender details and article image size.
4. Open **Newsletter**, add and order your articles, then copy a preview URL to import into your email platform.

= Preview URLs =

The newsletter renders at:

`/ptn-newsletter/?ptn_platform=mailchimp`
`/ptn-newsletter/?ptn_platform=campaignmonitor`

Open the one for your platform and import the HTML.

== Frequently Asked Questions ==

= Does it send the email? =

No. The free plugin only renders the newsletter for you to import. The premium add-on can create a **draft** on your platform, but nothing ever sends automatically — you review and send from inside Mailchimp or Campaign Monitor.

= Do I need an email platform account? =

Not for the free plugin — it renders HTML you can use anywhere. To push drafts automatically you need the premium add-on plus a Mailchimp or Campaign Monitor account.

= Which posts can I include? =

Any published post. Search by keyword or pick from your most recent posts.

= Can I change the newsletter design? =

Yes. Branding (logo, hero image, colours, sender details and article image size) is set on the settings page. The subject line, intro greeting and email template are set per edition on the curation screen — the free plugin ships one template, and Posts to Newsletter Pro adds more.

= Is it tied to one site or theme? =

No. Branding defaults are pulled from the current site and everything is overridable, so it works on any site.

== Screenshots ==

1. The curation screen - search, add and drag posts into order.
2. The settings screen - branding and sender details.
3. The rendered newsletter email.

== Changelog ==

= 1.0.0 =
* Initial release: post curation, branded email rendering, and a platform-aware public render endpoint for manual import. One-click draft push to Mailchimp and Campaign Monitor is available via the premium Posts to Newsletter Pro add-on.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
