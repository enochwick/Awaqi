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
parts/                     hero-scene.php, model.php, post-list.php
assets/css/main.css        ALL styles — one file, token-based
assets/js/main.js          Global JS
assets/js/scene.js         Front-page loader → scene cross-fade
assets/js/vendor/          model-viewer, self-hosted
assets/images/             Static images
assets/models/             Optimized GLB

── tooling, excluded from deploys ──
deploy.php                 Deployer recipe (alternative SSH path)
.cpanel.yml                cPanel Git deployment (alternative path)
composer.json              Pulls in Deployer
tools/corridor-to-glb.py   Blender: corridor OBJ → GLB with rebuilt PBR
tools/obj-to-glb.py        Blender: generic OBJ → decimated GLB
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
| Hero heading | Large headline over the scene |
| Hero paragraph | Supporting line |
| Interaction hint | Bottom-right nudge — empty hides it |
| Button label / link | Top-right CTA — empty label hides it |

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

## The 3D model

The front page shows a **Sci Fi Corridor** below the Spline scene, rendered by
a self-hosted `<model-viewer>`.

| | |
| --- | --- |
| Source | 36 MB OBJ, 273,497 faces, 5 materials |
| Output | `assets/models/corridor.glb` — 3.1 MB, Draco-compressed |
| Geometry | kept in full; no decimation needed |
| Poster | `assets/images/corridor-poster.jpg` (90 KB Blender render) |

`raw-3d/` holds raw 3D source and is **git-ignored on purpose** — keep raw
sources in cloud storage and commit only the optimized GLB.

### Rebuilding it

```bash
/Applications/Blender.app/Contents/MacOS/Blender --background \
  --python tools/corridor-to-glb.py -- \
  "raw-3d/Sci Fi Corridor_Obj/Sci Fi Corridor.obj" \
  assets/models/corridor.glb
```

The source `.mtl` declares four flat-white materials with no `map_Kd`, so a
straight import renders the whole corridor white. The material *names* say what
each surface should be, so [`tools/corridor-to-glb.py`](tools/corridor-to-glb.py)
rebuilds them as PBR:

| Material | Faces | Becomes |
| --- | --- | --- |
| `default` | 242,550 | dark hull panelling |
| `Mat_1` | 30,765 | lighter metal trim |
| `Light_White` | 112 | emissive white strips |
| `Light_Blue` | 56 | emissive strips, tinted to the Awaqi violet `#7C5CFF` |
| `Yellow_Stripes.tex` | 14 | the bundled PNG, wired up as a real texture |

Pass a triangle budget as a third argument to decimate; omit it to keep all
geometry.

### Camera framing

It is a corridor, so `<model-viewer>`'s default orbit — which frames the whole
bounding box from outside — shows only a dark shell. `parts/model.php` places
the camera *inside*, at mid-height, looking down its length:

```
camera-target="0m 404.2m 3736.4m"   ← glTF-space centre
camera-orbit="0deg 90deg 3600m"     ← just inside the far end, looking down Z
```

Those numbers come from the model's bounds: centre `(0, 404, 3736)`, length
`7667` along Z. **Regenerating the model at a different scale or orientation
means recomputing them**, or the view opens inside a wall.

`exposure="0.7"` keeps the dark hull dark so the emissive strips carry the
image. Auto-rotate is deliberately off — orbiting from inside swings the camera
through the walls.

### Swapping in a different model

1. Drop the new GLB in `assets/models/`
2. Update `AWAQI_MODEL_PATH` in `functions.php`
3. Recompute the camera values above for the new bounds
4. Replace the poster in `assets/images/`

To hide the section entirely, delete the GLB — the section and its 1 MB viewer
script only load when that file is present.

## Conventions

- Plain CSS and JS, no build step — nothing to compile before a deploy
- All output escaped (`esc_html`, `esc_url`, `esc_attr`)
- Text domain `awaqi` throughout
- Design tokens are CSS custom properties in `assets/css/main.css`, mirrored in
  `theme.json` for the block editor
