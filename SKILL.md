---
name: wp-theme-dev
description: Custom WordPress theme development skill. Activate when building pages, components, or modifying styles/scripts in this WordPress theme. Covers file structure, design tokens, CSS architecture, PHP template conventions, and dev workflow.
---

# WordPress Custom Theme — Dev Skill

## Project Overview

This is a **custom WordPress theme** (no page builder, no Gutenberg blocks — pure PHP templates + vanilla CSS/JS).

**Stack:**
- PHP templates (WordPress template hierarchy)
- Vanilla CSS — single file `assets/css/main.css`, token-based design system
- Vanilla JS — `assets/js/main.js` + page-specific scripts
- ACF (Advanced Custom Fields) for custom data
- No build step, no Tailwind, no React

---

## File Structure

```
/                          ← theme root
  style.css                ← WordPress theme header only (no actual styles here)
  functions.php            ← enqueue scripts/styles, register menus, ACF fields
  header.php               ← global header + nav
  footer.php               ← global footer
  front-page.php           ← homepage template
  home.php                 ← blog index
  page.php                 ← default page template
  page-{slug}.php          ← custom page templates (e.g. page-about.php)
  single.php               ← single post
  archive.php              ← archive/category
  404.php                  ← not found
  parts/                   ← get_template_part() partials
  inc/
    acf-fields.php         ← ACF field group registrations + awaqi_field()
  assets/
    css/main.css           ← ALL styles — one file, section-commented
    js/main.js             ← ALL global JS
    js/scene.js            ← page-specific JS (front page Spline scene)
    js/vendor/             ← third-party libs, self-hosted
    images/                ← static images
    models/                ← optimized GLB 3D models
    brand-assets/          ← logos, brand files
```

---

## CSS Architecture

**One file: `assets/css/main.css`**

Sections are separated by comment headers using this pattern:
```css
/* ── Section Name ─────────────────────────────────────────── */
```

**Section order:**
1. `@property` registered custom properties
2. Tokens (`:root`)
3. Reset
4. Layout
5. Typography
6. Component sections (buttons, nav, hero, cards, etc.)
7. Page-specific sections
8. Utility classes

**Rules:**
- Always use CSS custom properties (tokens) — never hardcode colors, spacing, or font sizes
- Never use `!important` unless overriding a WordPress core style
- Keep mobile-first; use `@media (min-width: ...)` for larger breakpoints
- Never add a new section without the standard comment header

---

## Design Tokens

```css
/* Brand */
--color-violet:       #7C5CFF;
--color-violet-light: #9B82FF;
--color-violet-dark:  #5B3FD9;
--color-ink:          #05060A;
--color-ink-mid:      #0B0D14;
--color-ink-light:    #141824;

/* Neutrals — dark theme */
--color-white:      #FFFFFF;
--color-off-white:  #F4F5F7;
--color-surface:    #0B0D14;
--color-border:     rgba(244,245,247,0.14);
--color-border-2:   rgba(244,245,247,0.08);
--color-text:       rgba(244,245,247,0.92);
--color-text-2:     rgba(244,245,247,0.72);
--color-text-3:     rgba(244,245,247,0.48);
--color-text-4:     rgba(244,245,247,0.30);

/* Typography — system stack, no webfont dependency */
--font-primary:   ui-sans-serif, -apple-system, 'SF Pro Text', 'Segoe UI', Inter, system-ui, sans-serif;
--font-secondary: ui-serif, Georgia, 'Times New Roman', serif;

/* Type scale:  --text-xs through --text-8xl (0.75rem → 5.5rem) */
/* Fluid sizes: --text-hero, --text-section */
/* Spacing:     --space-1 through --space-32  (0.25rem → 8rem) */
/* Radius:      --radius-sm through --radius-full */
/* Layout:      --gutter, --content-width, --wide-width */
/* Motion:      --ease, --duration, --duration-lg */
```

**Breakpoints** (mobile-first, `min-width` only):
`48rem` (768px) and `75rem` (1200px).

---

## PHP Template Conventions

- Use `get_template_part()` for reusable partials
- Use ACF functions (`get_field()`, `the_field()`) for custom content
- All template files start with `<?php get_header(); ?>` and end with `<?php get_footer(); ?>`
- Custom page templates use `page-{slug}.php` naming
- Enqueue all scripts and styles in `functions.php` — never inline `<script>` or `<link>` tags in templates

---

## Dev Workflow

**No build step** — edit files directly, changes are live.

**Testing:**
- The site runs on a local WordPress install or staging server
- Test responsive layout at 375px, 768px, 1024px, 1280px, 1440px+
- Check both light and dark sections on each page

**Git:**
- Branch: `main` is production
- Commit directly to `main` for this project (small single-person team)
- Commit message format: concise imperative (`Fix nav overlay z-index on mobile`)

**When adding a new page:**
1. Create `page-{slug}.php`
2. Add any ACF fields in `inc/acf-fields.php`
3. Enqueue page-specific JS in `functions.php` if needed
4. Add page-specific CSS section to `assets/css/main.css`

---

## What NOT to do

- Don't introduce a build tool (webpack, Vite, etc.) unless explicitly asked
- Don't use Tailwind or utility-class frameworks
- Don't add new PHP dependencies or composer packages without asking
- Don't modify `style.css` — it's a WordPress header file only
- Don't split CSS across multiple files
- Don't use jQuery unless it's already used on that page

---

## Copying This Skill to Another Project

When copying this skill to a new WordPress theme project:

1. Update **Project Overview** with the new project name and any stack differences
2. Replace **Design Tokens** with the new project's CSS custom properties from its `main.css`
3. Update **File Structure** if the folder layout differs
4. Update **Typography** font names
5. Update **Git** section if the branching strategy differs
6. Remove or update any page-specific notes that don't apply
