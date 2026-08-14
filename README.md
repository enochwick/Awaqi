# Awaqi — WordPress theme

Immersive single-screen WordPress theme built around an interactive Spline 3D
scene, deployed with [Deployer](https://deployer.org) over git.

This repo holds **the theme only** — never WordPress core, uploads, plugins, or
the database. That keeps deploys fast, safe to roll back, and free of anything
WordPress owns at runtime.

**The repository root is the theme.** Git-based installers (Deployer for Git,
WP Pusher, cPanel) treat a repo as a theme only when `style.css` sits at the
top level, so the theme files live at the root and the tooling lives beside
them.

```
style.css                  Theme header (no styles — see assets/css/main.css)
functions.php              Setup, assets, conditionals, Customizer fallback
header.php  footer.php     Chrome
front-page.php             The 3D scene
home.php  archive.php      Blog index and archives
page.php  single.php       Content templates
index.php  404.php         Fallback and not-found
inc/acf-fields.php         ACF field group + awaqi_field() resolver
inc/waitlist.php           Signup storage, handler and admin screen
parts/                     hero-scene.php, waitlist.php, post-list.php
assets/css/main.css        ALL styles — one file, token-based
assets/js/main.js          Global JS
assets/js/scene.js         Front-page loader → scene cross-fade

── tooling, excluded from deploys ──
deploy.php                 Deployer recipe (alternative SSH path)
.cpanel.yml                cPanel Git deployment (alternative path)
composer.json              Pulls in Deployer
tools/model-to-glb.py      Blender: OBJ/FBX/glTF → Draco GLB (unused; kept for future models)
docs/static-preview.html   The original static HTML version
raw-3d/                    Raw 3D source (git-ignored)
SKILL.md                   Theme development conventions
```

## Local setup

Any local WordPress works — Local, MAMP, `wp server`, Docker. Clone the repo so
the theme lands in the right place:

```bash
cd /path/to/wordpress/wp-content/themes
git clone https://github.com/enochwick/Awaqi.git awaqi
```

Because the repo root is the theme, the clone *is* the theme directory — no
symlink needed.

Then activate **Awaqi** under Appearance → Themes.

### Set the front page to the scene

Settings → Reading → "Your homepage displays" → **A static page**, and pick any
page. The scene renders from `front-page.php`; the page's own content is not
required.

### Editing content without a deploy

Everything client-facing lives in **Appearance → Customize → Awaqi — Scene &
Hero**:

| Setting | What it does |
| --- | --- |
| Spline scene URL | The public share URL from Spline |
| Hero heading | Headline over the scene. **Each new line becomes a line break on desktop**; small screens wrap on their own |
| Hero paragraph | Supporting line |
| Button label / link | Top-right CTA — empty label hides it |
| Waitlist heading / paragraph | The signup section |
| Waitlist button label | Submit button text |
| Success message | Shown after a signup |

Nav items come from **Appearance → Menus**, assigned to the *Primary* location.
The CTA button is appended automatically. With no menu assigned, only the CTA
shows, so a fresh install still looks intentional.

## Deploying — Deployer for Git plugin (current setup)

The [Deployer for Git](https://wordpress.org/plugins/deployer-for-git/) plugin
installs the theme straight from GitHub. No SSH, no git on the server, and the
free tier covers public repositories.

**Deployer for Git → Install Theme:**

| Field | Value |
| --- | --- |
| Provider Type | GitHub |
| Repository URL | `https://github.com/enochwick/Awaqi` |
| Branch | `main` |
| Is Private Repository | unchecked (repo is public) |

Then **Appearance → Themes → activate Awaqi**. Installing does not activate.

This plugin has no "subdirectory" setting — it treats the whole repository as
the theme. That is why `style.css` lives at the repo root. Moving the theme
back into a `wp-content/themes/awaqi/` subfolder would break this deploy path.

The plugin copies every tracked file, so `deploy.php`, `README.md`, `docs/` and
`tools/` land in the theme folder too. They are inert — WordPress only reads
the template files — but the `.cpanel.yml` path below excludes them if you care.

To update: push to `main`, then re-run Install Theme. It overwrites in place.

## Deploying — cPanel Git Version Control (alternative)

[`.cpanel.yml`](.cpanel.yml) drives it.

**Why a pull can "succeed" and change nothing:** cPanel clones the repo into
`~/repositories/Awaqi` and stops there. Without `.cpanel.yml` it never copies
anything into `public_html`, so the deploy reports success while the live site
is untouched.

### Setup

1. **Set `WP_ROOT` in [`.cpanel.yml`](.cpanel.yml).** It must be the folder
   holding `wp-config.php`. `$HOME/public_html` is right for a primary domain;
   addon domains and subdomains differ. Check cPanel → Domains → Document Root.
2. Commit and push.
3. cPanel → Git Version Control → **Manage** → **Deploy HEAD Commit**.
4. **Activate the theme** in Appearance → Themes. Copying files does not switch
   the active theme — this is the step most often missed.

### What the tasks do

- Abort if `wp-config.php` is not at `WP_ROOT`, rather than deploying into a
  path WordPress never reads
- Back up an existing non-Awaqi theme folder once, instead of overwriting it
- Mirror the theme with `rsync --delete`, falling back to `cp -R` on hosts
  without rsync
- List the deployed files so the log shows what actually landed

Re-deploying over an existing Awaqi install does **not** create repeat backups.

### If the site still looks unchanged

1. Confirm the files arrived: `ls -la ~/public_html/wp-content/themes/awaqi`
2. Confirm Awaqi is the **active** theme in Appearance → Themes
3. Purge caches — LiteSpeed Cache and WP Rocket are common on cPanel hosts, and
   Cloudflare caches separately from your server
4. Hard-reload the browser (⌘⇧R)

## Deploying — Deployer over SSH (alternative)

Kept for a future move to a VPS. Not used by the cPanel flow above, and its
`hostname` / `wp_path` / `deploy_path` values are still placeholders.

### One-time server setup

The server needs PHP, git, and an SSH key that can read the repo.

```bash
# On the server
mkdir -p /var/www/awaqi/deploy/theme
```

If a theme folder was previously uploaded by hand, move it aside — the deploy
refuses to overwrite a real directory:

```bash
mv /var/www/awaqi/public/wp-content/themes/awaqi \
   /var/www/awaqi/public/wp-content/themes/awaqi-manual-backup
```

### Point the recipe at your infrastructure

In [`deploy.php`](deploy.php), update:

- `repository` — your git remote
- `hostname`, `remote_user`, `port` — SSH details
- `wp_path` — the folder containing `wp-config.php`
- `deploy_path` — where releases live (keep it **outside** the web root)

### Deploy

```bash
composer install          # once, installs Deployer locally
vendor/bin/dep deploy production
```

What happens: Deployer clones the branch, extracts only
the repo into a timestamped release, lints every PHP file,
flips the `current` symlink, points `wp-content/themes/awaqi` at it, and flushes
WP-CLI caches if WP-CLI is installed.

```bash
vendor/bin/dep rollback production    # instant revert to the previous release
vendor/bin/dep wp:info production     # what is actually live right now
vendor/bin/dep deploy staging         # same flow against staging
```

### How the symlink works

```
/var/www/awaqi/deploy/theme/
  releases/1  releases/2  releases/3
  current -> releases/3

/var/www/awaqi/public/wp-content/themes/awaqi -> /var/www/awaqi/deploy/theme/current
```

WordPress builds theme paths from `WP_CONTENT_DIR` rather than `realpath()`, so
a symlinked theme resolves its URLs correctly. Five releases are kept, making
rollback a symlink flip rather than a redeploy.

> If your host blocks symlinks (some shared hosting does), set `deploy_path`
> directly to `{wp_path}/wp-content/themes/awaqi-releases` and adapt
> `wp:link_theme` — or switch to Deployer's `rsync` recipe.

## The waitlist — where the emails go

The front page shows a signup form below the Spline scene. **Nothing is wired to
an external service.** Every signup goes to two places, both inside WordPress:

1. **The database.** Each address is stored as a private `awaqi_lead` post.
   See them under **Waitlist** in the wp-admin sidebar. Export with any CSV
   plugin, or WP-CLI:
   `wp post list --post_type=awaqi_lead --post_status=private --field=post_title`
2. **An email to the site admin**, sent with `wp_mail()` to whatever address is
   set in Settings → General.

> **Check the admin email actually arrives.** Shared hosts frequently have
> `wp_mail()` misconfigured, and it fails silently. The signup is still saved to
> the database either way, so nothing is lost — but you will not be notified.
> An SMTP plugin, or a transactional provider, fixes it.

To send to a provider instead (Mailchimp, ConvertKit, Resend), hook the action
that fires after a successful signup — no template changes needed.

- **Where they land:** an `awaqi_lead` custom post type, listed under
  **Waitlist** in wp-admin. The list is read-only (new entries cannot be added
  by hand) and shows the address and the date.
- **Notification:** the site admin gets an email per signup. Disable it with
  `add_filter( 'awaqi_notify_admin', '__return_false' );`
- **Spam:** an off-screen honeypot field. Bots that fill it get a success
  response and are silently discarded.
- **Security:** nonce-verified, `sanitize_email()` then `is_email()`, and
  duplicates are treated as success so the form never reveals who is on the list.

The form posts to `admin-post.php` and redirects back to `#waitlist` with a
status, so it works with JavaScript disabled.

**The form is below the fold by design.** The hero overlay is a full `100dvh`,
so the front page opens on the scene alone — the signup is reached by scrolling
or by the "Join waitlist" button, which smooth-scrolls to it.

Because that redirect is a full page load, the return trip is choreographed:

1. **The redirect carries `#waitlist`.** The browser paints the page at the form
   on first frame, so the visitor never appears to move from where they
   submitted. The confirmation is simply there.
2. **The intro loader is suppressed on arrival.** `awaqi_is_waitlist_return()`
   checks the `joined` flag server-side, so the curtain does not replay — it is
   rendered but starts hidden, ready to be reused.
3. **After ~2.2s the curtain rises**, the page jumps home behind it, and the
   curtain lowers onto the hero with the form back below the fold. The jump is
   never visible as a scroll.
4. **The flags and fragment are stripped** from the URL with `replaceState`, so
   a refresh does not replay any of it.

A **failed** signup breaks out of this: it stays at the field with its message,
because that is where the address has to be corrected.

`main.js` reuses the loader for step 3 via an `is-transition` class declared
after `is-hidden`, so `scene.js` — which hides the loader on its own schedule
when the Spline iframe loads — cannot close the curtain mid-transition.

Editable copy lives in **Appearance → Customize → Awaqi — Scene & Hero**
(or the ACF options page): heading, paragraph, button label, success message.

## The Spline badge

`.scene-mask` covers the "Built with Spline" badge in the bottom-right of the
embed. Be aware of what this is: the embed is cross-origin, so it cannot be
styled or modified — the badge is **covered, not removed**. If the badge is
there because the scene is on a free Spline plan, hiding it is a licensing
question rather than a technical one. The clean alternatives are a paid Spline
plan, or Spline's code export (`@splinetool/runtime`), which renders in your own
canvas with no badge at all.

## Conventions

- Plain CSS and JS, no build step — nothing to compile before a deploy
- All output escaped (`esc_html`, `esc_url`, `esc_attr`)
- Text domain `awaqi` throughout
- Design tokens are CSS custom properties in `assets/css/main.css`, mirrored in
  `theme.json` for the block editor
