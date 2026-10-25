# Current handoff — 2026-10-25

Read `README.md`, `docs/handoff/CURSOR_START_AR.md`, the COMPLETE owner archive at
`docs/owner/CLICK_PENDING_BATCH_20261017_AR.txt`, and the current audit reports
before editing. Latest explicit owner decisions override superseded history.
This is CLICK HR, not ORANGE ERP. The owner requested a complete GitHub source
handoff to continue in Cursor. Do not deploy or initialize a live database merely
because this source package has been opened. The full live review remains pending.
Numbered historical README/test files are not the current release instructions.

# CLICK HR maintenance rules

- Read the current owner archive and the actual implementation before editing.
- Keep approved screens, colors, workflows, payroll rules and permissions unchanged unless the owner specifically changes them.
- Runtime PHP, JavaScript, CSS and catalog files use stable names describing their responsibility. Modify the existing owning file. Never add a release or batch number to a runtime filename, or add a parallel patch file for an existing responsibility.
- Release numbers belong in delivery metadata and cache query parameters. Existing numeric database keys, action names, function identifiers and migration markers are compatibility contracts; do not rename them as cosmetic cleanup.
- A new module requires a distinct responsibility, all callers updated, and a dependency check. Merge same-function additions when their load order and scope can be preserved.
- Run `tests/test_runtime_paths.php` and the current functional suites. Report legacy failing fixtures separately; do not claim the entire historic suite passes.
- Preserve .env.php, hr/private, stored files, database contents, keys and production configuration. Take a verified code snapshot before hosted replacement.
- Retire only explicitly mapped old code paths after their callers are switched. Keep rollback copies outside the served site; never use a wildcard deletion of numbered files.
- For the current cleanup, the owner explicitly requested that the agent perform installation and cleanup, with no upload steps delegated to the owner.
