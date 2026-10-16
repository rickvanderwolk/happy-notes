# HappyNotes

## UI

- Never use the browser's `alert()`, `confirm()` or `prompt()`, nor Livewire's `wire:confirm`. Use the shared dialog instead: `data-confirm="…"` on a button or link (with `data-confirm-label` and `data-confirm-danger` where needed), or `confirmDialog()` / `alertDialog()` from `resources/js/dialog.js` in code. It matches the app's card style (`resources/css/_dialog.scss`).
- Only ask for confirmation on destructive actions that cannot be undone. Logging out is not one of them.
- New overlays and modals use the `.app-dialog` / `.app-dialog-card` classes, so every dialog looks the same.
- `tests/Unit/NoBrowserDialogsTest.php` fails when one of the browser dialogs shows up in `resources/`.
