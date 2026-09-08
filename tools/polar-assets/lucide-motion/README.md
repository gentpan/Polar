# Animated icons

Active header icons use the site owner's original Lordicon JSON exports via
`lordicon/create-icon.tsx`. Sun, moon and close use the registered Lucide Animated
components retained in `icons/` with their upstream MIT license. The system-theme
monitor uses `lucide-react`. GitHub and X use the owner-provided lineal Lordicon animations.

`content-icons.ts` owns the shared DOM lifecycle for inline icons and comment
replies. `category-list.ts` manages the homepage list-cover previews.

Run `npm ci --prefix tools/polar-assets/lucide-motion`, then
`python3 tools/polar-assets/build.py`. The output is bundled into main.js.
Mounts/unmounts follow PJAX; reduced-motion preferences skip animation. The
superseded Morphicons source, dependencies, bundle and styles have been removed.
