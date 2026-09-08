# Polar resource bundles

Editable feature sources live in `source/`, outside the public theme. Run from the project root:

```sh
python3 tools/polar-assets/build.py
```

The theme ships three CSS files (`style.css` theme metadata, `main.css`, `admin.css`) and two JavaScript files (`main.js`, `admin.js`). The front end uses main.css/main.js, the owner's external Font Awesome stylesheet, LiteZoom and WordPress comment-reply. Optional map providers still load their SDK only when a map is opened.

`bundle-order.json` is the explicit dependency-resolved build order. Builds use local sources and cached font stylesheets, with no homepage snapshot or network request. `inc/asset-bundles.json` maps original front-end handles to bundled modules; PHP retains the handles as dependency aliases and sends the enabled script list. Disabled modules do not run. Admin modules are gated separately, preserving their localized settings.

Font definitions retain their original remote font URLs. Font Awesome is served exclusively from https://static.bluecdn.com/libs/fontawesome-pro-plus/7.3.1/css/all.min.css; no local Font Awesome copies are included.

After building, run `python3 tools/polar-assets/audit.py` to check JavaScript/CSS syntax and detect unlisted business assets or duplicate build entries. Dependency installation: `npm ci --prefix tools/polar-assets/lucide-motion`.
