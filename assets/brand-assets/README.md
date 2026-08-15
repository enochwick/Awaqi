# Brand assets

## Favicon

Drop **one** of these here and it is picked up automatically — no code change,
no Customizer step:

| File | Notes |
| --- | --- |
| `favicon.svg` | Preferred. Scales to every size, smallest file. |
| `favicon.png` | 512×512 or 192×192. |
| `favicon.ico` | Only if you need very old browsers. |
| `apple-touch-icon.png` | 180×180. Added alongside the icon when present. |

`awaqi_favicon()` in `functions.php` handles the output.

### This is the fallback, not the primary route

WordPress's own **Site Icon** wins whenever one is set:

> Appearance → Customize → Site Identity → Site Icon

That is usually the better option — it generates every size including the Apple
touch icon, and it can be changed without a deploy. Use the files here when you
want the icon version-controlled with the theme instead.

Design note: a favicon renders at 16×16 in a browser tab. A full wordmark turns
to mush at that size — use the mark or monogram on its own, not the lockup.
