# FE-5 demo data (local only)

Dedicated database: `smart_fitness_fe5_demo`. The default `BE/.env` remains `smart_fitness`; no application configuration file was changed. To use the demo in the web UI, start the Backend process with `DB_DATABASE=smart_fitness_fe5_demo` in that process's environment. The existing `FE/.env` points to `http://127.0.0.1:8000/api`, so the demo Backend should listen on port 8000 when using the current Frontend configuration. Do not point a production process at this database.

PowerShell from `E:\Fitness\BE`:

```powershell
$env:DB_DATABASE = 'smart_fitness_fe5_demo'
& 'E:\Fitness\.tools\php\php.exe' artisan serve --host=127.0.0.1 --port=8000
```

PT login: `dev.pt01@smartfitness.local` / `DevOnly!ChangeMe123` (development-only password). Assigned Member: `dev.member01@smartfitness.local`, profile ID `1`, code `HV_DEMO_01`. PT02 is deliberately unassigned and cannot open Member01's workspace.

Fixture contents: one current PT assignment, one official active Plan with a future scheduled workout, one completed immutable Session with one logged set, two body measurements (weight trend), one linked append-only coaching note, plus the base demo accounts and exercise catalog. No Membership registration, term or usage is fabricated. PT profile comes from the base demo seed.

To seed again without replacing manual profile edits or duplicating fixture rows, from `E:\Fitness`:

```powershell
$env:DB_DATABASE = 'smart_fitness_fe5_demo'
& 'E:\Fitness\.tools\php\php.exe' '.fitness-sdd/fe5-demo/run-demo.php'
```

The runner refuses any non-local or non-demo database, and leaves pre-existing demo accounts/catalog intact. If the assignment was closed or a different active Plan was created during testing, it refuses to undo that change. `verify-api.php` checks ten FE-5 GET endpoints, populated response bodies, PT02 denial and no Membership/usage side effect; both scripts require the same environment override. A second seed run kept fixture counts at assignment 1, Plan 1, measurements 2, completed Session 1 and note 1.

Important incident during setup: an initial migration command failed to apply the intended `DB_DATABASE` override and ran pending migration M061 on the default local `smart_fitness` database. It added `nhom_co.trang_thai` with default `HOAT_DONG` and its check constraint; no drop, reset or seed was run on that database. `migrate:status` reports M061 as batch 2. No reversal was attempted. All subsequent schema creation, base seed, FE-5 fixture and API checks were guarded against `smart_fitness_fe5_demo`.
