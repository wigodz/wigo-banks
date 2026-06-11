# Atomic Design

- `ui/` & `atoms/` — smallest UI primitives (shadcn components). `atoms/index.ts` re-exports the ones used across the app.
- `molecules/` — small groups of atoms (e.g. `FormField` = `Label` + `Input` + error text).
- `organisms/` — larger sections composed of molecules/atoms (e.g. `PageHeader`).
- `templates/` — page-layout skeletons that arrange organisms/molecules, leaving content to the actual page.
- `../pages/` — Inertia pages, the "pages" of atomic design, built from templates.

New components should be plain Vue 3 `<script setup>` (no `lang="ts"`) unless they need to interop with typed code (e.g. shadcn `ui/` atoms).
