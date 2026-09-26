# FE6-ALL final test evidence

All results below were run by the Controller after the round-1 code changes and task re-review. No product-code change occurred between these checks and this record.

| Scope | Exact command context | Result |
| --- | --- | --- |
| FE full | `rtk npm run test` from `E:/Fitness/FE` | PASS, 86 files, 852 tests, exit 0; no skips reported. |
| FE lint | `rtk npm run lint` from `E:/Fitness/FE` | PASS, exit 0, 0 errors, 375 warnings. |
| FE production build | `rtk npm run build` from `E:/Fitness/FE` | PASS, exit 0; Vite 8.2.2, 200 modules, JS 641.25 kB (gzip 159.69 kB), chunk-size advisory. |
| Backend PT regression | `rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_fe5_round2_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_fe5_round2_test"; & "E:/Fitness/.tools/php/php.exe" artisan test --filter="(PtDirectServiceTest|PtProposalApiTest|PtProposalConcurrencyTest)" }'` from `E:/Fitness/BE` | PASS, 24 tests, 180 assertions, exit 0. |
| Backend full | Same explicit environment and bundled PHP 8.4.25, `artisan test` from `E:/Fitness/BE` | PASS, 346 tests, 4,106 assertions, exit 0. Ran sequentially after targeted suite. |
| Git whitespace | `rtk git diff --check` from `E:/Fitness` | PASS, exit 0. |
| Product path scope | Compared `git status --porcelain=v1 -uall` against 24 allowed FE paths, excluding workflow scratch | 0 unexpected product paths. |

Database safety: `BE/.env` targets `smart_fitness`, so every Backend test command explicitly set `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=smart_fitness_fe5_round2_test`, and `SMART_FITNESS_TEST_DATABASE=smart_fitness_fe5_round2_test`. The selected isolated schema was verified by `migrate:status` before tests (M001–M061 Ran). No test, migration or seed was run on `smart_fitness` or `smart_fitness_fe5_demo`; no migrate/seed command was run for FE6.

Browser visual smoke on the three protected PT pages remains unverified; no authenticated PT fixture/session was supplied for an end-to-end visual run.
