# Awaqi — WordPress theme

Immersive single-screen WordPress theme built around an interactive Spline 3D
scene, deployed with [Deployer](https://deployer.org) over git.

This repo holds **the theme only** — never WordPress core, uploads, plugins, or
the database. That keeps deploys fast, safe to roll back, and free of anything
WordPress owns at runtime.

```
deploy.php                          Deployer recipe (hosts, tasks, flow)
composer.json                       Pulls in Deployer
tools/obj-to-glb.py                 Blender script: raw OBJ → web-ready GLB
docs/static-preview.html            The original static HTML version
wp-content/themes/awaqi/
  style.css                         Theme header
  functions.php                     Setup, assets, Customizer options
  theme.json                        Block editor palette + layout
  header.php footer.php             Chrome
  front-page.php                    The 3D scene
  index.php singular.php 404.php    Content templates
  parts/hero-scene.php              Scene + overlay markup
  assets/css/main.css               All styles
  assets/js/scene.js                Loader → scene cross-fade
```

## Local setup

Any local WordPress works — Local, MAMP, `wp server`, Docker. Clone the repo so
the theme lands in the right place:

```bash
cd /path/to/wordpress/wp-content/themes
git clone git@github.com:USERNAME/awaqi.git awaqi-repo
ln -s awaqi-repo/wp-content/themes/awaqi awaqi
```

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

## Deploying

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
`wp-content/themes/awaqi` into a timestamped release, lints every PHP file,
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

`assets/` holds raw 3D source and is **git-ignored on purpose**. The interior
OBJ is 1.2 GB / 7M faces — GitHub rejects files over 100 MB, and a file that
size makes a repo unusable even under LFS. Keep raw sources in cloud storage and
commit only the optimized GLB the site actually loads.

To produce that GLB:

```bash
/Applications/Blender.app/Contents/MacOS/Blender --background \
  --python tools/obj-to-glb.py -- \
  assets/Free_interior_scene_01_update_Corona_8.obj \
  wp-content/themes/awaqi/assets/models/interior.glb \
  250000
```

The last argument is the triangle budget. 150k–300k is a sane range for the web.

The last argument is the triangle budget. 150k–300k is a sane range for the web.

The conversion runs and produces a valid, well-compressed file:

| | |
| --- | --- |
| Source | 1.2 GB OBJ, 2,407 objects, 14.1M triangles |
| Output | 3.2 MB GLB, 241k triangles, Draco-compressed |
| Preserved | positions, normals, UVs |

**But the result is not usable as a showcase yet, for two reasons:**

1. **No materials.** The OBJ references a `.mtl` file that is not in the
   download, so the GLB carries a single default material — the model renders
   flat grey. The textures exist in the original download's `tex/` folder, but
   reassigning 2,400+ materials by hand is not realistic.
2. **Interior detail did not survive decimation.** Getting from 14.1M to 241k
   triangles means keeping 1.7% of the geometry. Collapse-decimating the joined
   mesh preserves large flat surfaces (walls, floor, ceiling) and destroys the
   small objects that make the scene worth looking at. Test renders from inside
   the model come back essentially featureless.

On top of that, it is an *interior* — a default orbit camera sits outside and
sees a closed grey shell.

**The realistic paths forward**, best first:

- Use `assets/img/interior-poster.jpg` (the 4K render) as a still. It is
  genuinely beautiful and costs 259 KB. Already bundled.
- Re-export from the source `.c4d` with materials, and decimate *per object*
  with a floor on small items rather than joining first.
- Import the model into Spline and serve it the same way as the hero scene,
  which sidesteps the material and camera problems entirely.

To hide the model section, delete
`wp-content/themes/awaqi/assets/models/interior.glb` — the section and its 1 MB
viewer script only load when that file is present.

### Where the model shows up

`parts/model.php` renders a `<model-viewer>` section below the Spline scene on
the front page — but only when `assets/models/interior.glb` actually exists.
Without it, the front page stays a locked single screen exactly as before, so a
fresh checkout is never broken by the missing (git-ignored) binary.

The viewer library is vendored at `assets/js/vendor/model-viewer.min.js` rather
than loaded from a CDN, and is enqueued only on views that show a model.

`assets/img/interior-poster.jpg` is a downsized 4K render used as the loading
poster, and is a perfectly good standalone hero image if the GLB never lands.

## Conventions

- Plain CSS and JS, no build step — nothing to compile before a deploy
- All output escaped (`esc_html`, `esc_url`, `esc_attr`)
- Text domain `awaqi` throughout
- Design tokens are CSS custom properties in `assets/css/main.css`, mirrored in
  `theme.json` for the block editor
