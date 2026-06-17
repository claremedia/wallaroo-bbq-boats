# Wallaroo BBQ Boats — WordPress Theme

Custom lean WordPress theme for [Wallaroo BBQ Boats](https://wallaroobbqboats.com.au). Self-drive BBQ boat hire at Copper Cove Marina, Wallaroo, South Australia.

Built with Tailwind CSS v3. No page builder. No Elementor. Performance-first.

---

## Prerequisites

- **Node.js** v18 or higher (tested on v22)
- **npm** v9 or higher
- **WordPress** 6.4+
- **ACF Free** (optional — fields degrade gracefully without it)

---

## Installation

### 1. Install the theme

Drop the `wallaroo-bbq-boats/` folder into `wp-content/themes/` and activate it in WordPress.

The compiled CSS (`assets/css/app.css`) is committed to the repository, so **the theme works immediately without running a build step**.

### 2. Install Node dependencies (for local development only)

> **Note:** Due to a known npm issue with paths containing spaces (e.g. Local by Flywheel's "Local Sites" directory), `npm install` may not install packages into `node_modules` correctly. Install Tailwind CSS globally instead:

```bash
npm install -g tailwindcss@3
```

This makes the `tailwindcss` CLI available globally, which is what both npm scripts call.

---

## Development

Watch mode — recompiles CSS on every file save:

```bash
npm run dev
```

---

## Production build

Minified, PurgeCSS applied, ready to commit:

```bash
npm run build
```

The output goes to `assets/css/app.css`. Commit this file — it must be present for the theme to work on Cloudways.

---

## Fonts

The theme uses **self-hosted** fonts (no Google Fonts CDN) for performance. Font files must be placed in `assets/fonts/`:

| File | Source |
|------|--------|
| `anton-regular.woff2` | [Google Fonts — Anton](https://fonts.google.com/specimen/Anton) → Download → extract `.woff2` |
| `inter-regular.woff2` | [Google Fonts — Inter](https://fonts.google.com/specimen/Inter) → weight 400 |
| `inter-medium.woff2`  | Inter weight 500 |
| `inter-semibold.woff2` | Inter weight 600 |

### How to download woff2 files

1. Visit [Google Fonts](https://fonts.google.com)
2. Search for the font (Anton or Inter)
3. Select the weights listed above
4. Click **Download family**
5. Inside the zip, use a converter such as [cloudconvert.com](https://cloudconvert.com/ttf-to-woff2) or [fontsquirrel.com/tools/webfont-generator](https://www.fontsquirrel.com/tools/webfont-generator) to convert the `.ttf` files to `.woff2`
6. Rename files to match the names above and place them in `assets/fonts/`

> Once fonts are in place, run `npm run build` to rebuild the CSS, and commit both the font files and `assets/css/app.css`.

---

## Cloudways deployment

Cloudways does not run Node.js build steps. The workflow is:

1. Build locally: `npm run build`
2. Commit `assets/css/app.css` to Git (**do not gitignore it**)
3. Push to GitHub
4. Pull on Cloudways (or use Cloudways Git deployment)

The `.gitignore` in this repo intentionally **does not** ignore `assets/css/app.css`.

---

## ACF (Advanced Custom Fields)

Fields are registered locally via `inc/acf-fields.php` using `acf_add_local_field_group()`. No import step required — just activate ACF Free and the fields appear on the front page edit screen.

Fields registered:
- **Homepage Hero** — headline, subheading
- **Trust Strip** — repeater (icon SVG, label)
- **How It Works** — repeater (step number, heading, body)
- **Testimonials** — repeater (quote, name, rating)

All calls are wrapped in `function_exists('acf_add_local_field_group')` so the theme loads cleanly without ACF.

---

## Theme structure

```
wallaroo-bbq-boats/
├── assets/
│   ├── css/
│   │   └── app.css          ← compiled Tailwind (commit this)
│   ├── fonts/               ← self-hosted woff2 files (see above)
│   ├── images/
│   └── js/
│       └── main.js          ← vanilla JS (sticky nav, mobile menu, FAQ, smooth scroll)
├── inc/
│   └── acf-fields.php       ← ACF local field group registration
├── src/
│   └── css/
│       └── input.css        ← Tailwind source + @font-face declarations
├── templates/               ← page templates (future use)
├── functions.php            ← theme setup, enqueue, bloat removal
├── header.php               ← sticky header with pill nav
├── footer.php               ← three-column dark navy footer
├── front-page.php           ← full homepage (all sections)
├── index.php                ← fallback archive/single template
├── package.json
├── tailwind.config.js
├── style.css                ← WordPress theme header only (no styles)
└── README.md
```

---

## Brand

| Token | Value |
|-------|-------|
| Navy  | `#0A2A5E` |
| White | `#FFFFFF` |
| Red   | `#D32027` |
| Sky   | `#3FA9DC` |
| Cream | `#F2E8D5` |

Defined as CSS custom properties on `:root` and mapped as Tailwind colour tokens (`brand-navy`, `brand-white`, `brand-red`, `brand-sky`, `brand-cream`).

---

## Performance notes

- All scripts loaded with `defer` — no render-blocking JS
- Compiled CSS is small enough to load in `<head>` without blocking render
- Hero image uses `loading="eager"` and `fetchpriority="high"` (LCP element)
- All other images use `loading="lazy"`
- WordPress emoji, jQuery Migrate, oEmbed, RSS links, WLW manifest, shortlink, and REST API head link all removed in `functions.php`
- Fonts use `font-display: swap`
- No external font CDN requests
