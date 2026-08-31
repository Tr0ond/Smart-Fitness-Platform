# Backend instructions

Read `../AGENTS.md` and the complete `../PROJECT_RULES.md` before changing this project.
This directory now contains the implemented Laravel REST Backend core. Preserve
the approved M001-M060 schema, business invariants, authorization, idempotency,
transaction/concurrency tests, immutable history and backend-only secrets.
Use an isolated `smart_fitness_*test*` MariaDB schema for tests. Never run a
destructive command against `smart_fitness` or a database with data to keep.
Do not add new modules, migrations, providers or external calls without a user request.
Do not automatically install Laravel Boost or change the machine's PHP installation.
Use PHP 8.4 or newer as required by the committed dependency lockfile.
