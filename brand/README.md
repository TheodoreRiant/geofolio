# Mapped Places brand assets

Source files and rendered images for the plugin's identity. This folder is excluded from the plugin archive (`export-ignore`).

## Identity

A folded map (the *folio*) with a pin. Colours: blue `#1F4E79` (background), orange `#E07A3F` (pin), light blue `#DCE7F2` / `#C9D8E6` (folds and roads), white. Typeface: Poppins (SIL Open Font License, bundled in `assets/fonts/`).

## Files

| File | Use |
|---|---|
| `icon.svg` | Master icon, 256 viewBox. Edit this one, then re-render. |
| `icon-16.png` … `icon-1024.png` | Rendered icon. `icon-180.png` is the Apple touch icon, `icon-192.png` and `icon-512.png` the PWA sizes. |
| `icon-maskable.svg`, `icon-maskable-512.png` | Full-bleed variant with the content inside the central 80 % safe zone (Android adaptive icons, PWA `purpose: maskable`). |
| `favicon.ico` | 16, 32 and 48 px. |
| `icon-mono.svg` | Single-colour version (`currentColor`), used for the WordPress admin menu (`MappedPlaces\Domain\PlacePostType::MENU_ICON`, encoded with a black fill so WordPress can recolour it). |
| `og-image.png`, `og-image@2x.png` | Social preview, 1280×640 (2560×1280 for the retina variant). Source: `og.html`. |
| `banner.html` | Source of the wordpress.org banners (`.wordpress-org/banner-1544x500.png`, downscaled to 772×250). |

The wordpress.org icon (`.wordpress-org/icon.svg`, `icon-128x128.png`, `icon-256x256.png`) is the same master icon.

## Where to use them

- **GitHub social preview**: repository *Settings → General → Social preview → Upload an image*, with `og-image.png` (1280×640 is the recommended size). Shown when the repository link is shared on social networks, Slack, etc.
- **Website / documentation site**: `favicon.ico`, `icon-180.png` (`<link rel="apple-touch-icon">`), `icon-192.png` and `icon-512.png` in the web manifest, `og-image.png` in `<meta property="og:image">` and `<meta name="twitter:image">`.
- **wordpress.org**: the icon and banners are deployed from `.wordpress-org/` by the release workflow.

## Re-rendering

```bash
# PNG icons from the SVG (needs Node and the sharp package, e.g. npx -p sharp)
node -e "const s=require('sharp'); for (const n of [16,32,48,64,128,180,192,256,512,1024]) s('brand/icon.svg',{density:400}).resize(n,n).png().toFile('brand/icon-'+n+'.png')"

# Social preview and banner: open og.html / banner.html in a browser at the exact size, or use headless Chrome
"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new --hide-scrollbars --window-size=1280,640 --screenshot=brand/og-image.png brand/og.html
```

`og.html` and `banner.html` load Poppins from `../assets/fonts/`, so they render correctly from this folder.
