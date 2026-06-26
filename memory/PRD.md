# Books & Authors — Frontend Redesign PRD

## Original Problem Statement
> Perbaiki tampilan Vue.js pada code dari repo tersebut dan buat lebih professional. **Ingat hanya tampilan frontend.**
>
> - State Management & UI/UX: how efficiently data is handled on the frontend and how intuitive the UI feels.
> - A clean interface to manage authors and books.
> - Smooth pagination controls.
> - Advanced UI/UX: fully responsive (mobile-friendly) with smooth loading states / skeletons.
> - Validation & error handling: robust backend request validation, user-friendly frontend error alerts.

## Scope (locked)
- **Frontend Vue 3 + TypeScript + Tailwind** only.
- Backend (Laravel) and API contract untouched. All existing endpoints, routes, store/composables remain functional.

## User Choices
- Style: Dashboard profesional, bebas (designer's call).
- Theme: **Light + Dark mode** with persistent toggle.
- Features wanted: Search bar + filter, sortable columns, toast notifications, modal/drawer for form, plus designer's discretion.

## Architecture / Tech Stack
- **Vue 3.5** (Composition API, `<script setup lang="ts">`)
- **Vue Router 4** (added — was missing in original `package.json`)
- **Tailwind CSS v4** via `@tailwindcss/vite` plugin (added — was missing)
- **lucide-vue-next** icons
- **Vite 8**
- **Google Fonts**: DM Sans · Instrument Serif · JetBrains Mono

## Design Language — "Editorial Pro"
| Token | Value |
|---|---|
| Body font | DM Sans (400/500/600/700) |
| Display font (italic accents) | Instrument Serif |
| Numeric font | JetBrains Mono |
| Light bg | `#faf8f3` warm cream with subtle SVG grain |
| Dark bg | `#0c0b0a` near-black with warm tint |
| Accent | Amber `#c98410` (light) / `#f3c54c` (dark) |
| Radius | 8 → 28 px tokens |
| Shadows | Soft 4-layer system, accent focus ring |

## What Has Been Implemented (Jan 2026)
- ✅ Theme tokens for **light & dark** in `src/styles.css`, no-FOUC theme bootstrap in `index.html`.
- ✅ Theme toggle composable `src/lib/theme.ts` + sidebar toggle (Sun/Moon).
- ✅ Toast composable `src/lib/toast.ts` + `ToastHost.vue` (Teleport to body, slide-in from right, auto-dismiss, success/error/info variants).
- ✅ Redesigned `App.vue` shell: editorial sidebar, mobile drawer with backdrop, mobile topbar (menu + theme), hero with eyebrow + serif italic title.
- ✅ Dashboard (`SummaryOverview.vue`):
  - 4 stat cards (Total Authors, Total Books, Avg Books/Author, Recently Added) with iconography and gradient sheen.
  - Two recent-activity panels with skeletons, staggered enter animation, refined empty states.
- ✅ Authors module (`AuthorsManager.vue`):
  - Sticky sortable columns (Nama, Books, Updated) with chevron indicator.
  - Search with icon prefix, status filter, reset-filter button.
  - Skeleton table rows during initial load.
  - **Slide-in drawer** form (validation w/ inline + toast) — `novalidate` to suppress native bubble.
  - **Modal** confirmation for delete with warning icon.
  - **Undo toast** (7s) with animated progress bar (preserved existing logic).
  - Custom pager component with chevron buttons + page indicator.
  - Detail panel with serif-styled badges and book list.
- ✅ Books module (`BooksManager.vue`):
  - Mirror Authors UX. Adds Author filter, Published-date filter, sortable Title/Author/Published columns.
  - Monospaced ISBN. Iconified metadata in detail card.
- ✅ Smooth animations: page-fade transition between routes, stagger lists, micro-interactions (RefreshCw spin, button active states).
- ✅ Skeleton loader with shimmer gradient (theme-aware).
- ✅ Full responsive design: 1920 → 420px breakpoints verified.
- ✅ Accessibility: `aria-label`, `role="alertdialog"`, `tabindex` + keyboard `Enter` on sortable headers, focus rings tied to accent.
- ✅ `data-testid` attributes on every interactive element.

## Files Touched
**New**
- `src/lib/theme.ts`
- `src/lib/toast.ts`
- `src/components/ToastHost.vue`

**Rewritten**
- `index.html` (font loading, theme bootstrap script)
- `src/styles.css` (full design system + dark mode)
- `src/App.vue` (shell, mobile sidebar, theme toggle, page transition)
- `src/components/SummaryOverview.vue`
- `src/components/AuthorsManager.vue`
- `src/components/BooksManager.vue`

**Config / deps (additive only)**
- `package.json`: relaxed `engines.node` to `>=20`, added `start` script.
- Installed: `vue-router@4`, `tailwindcss@4`, `@tailwindcss/vite@4`.
- `vite.config.ts`: bind to `0.0.0.0:3000` + allow preview host.

## Untouched (per scope)
- `src/lib/api.ts` (network layer, error handling)
- `src/router/index.ts`
- `src/types.ts`
- `src/pages/*` (thin wrappers)
- All `/app/backend/**` (Laravel)

## Verification
- Lint (ESLint): clean.
- Manual screenshots: light + dark modes for Dashboard, Authors, Books, drawer open, validation toast, mobile menu, mobile data view.
- The backend (Laravel/PHP) is not runnable in this preview pod (PHP not installed). All UI states (loading, error, empty, populated skeletons) verified visually. Data-bound flows will activate the moment the Laravel API is reachable — no API contract was changed.

## Backlog
- P1: Per-row inline edit, bulk actions (delete multiple).
- P1: Export CSV from current filter set.
- P2: Author avatar/initial generator from name.
- P2: Keyboard shortcut (Cmd-K) command palette.
- P3: Detail view as full page route (`/authors/:id`, `/books/:id`).
