$ErrorActionPreference = 'Stop'
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'mysql'
$env:DB_DATABASE = 'smart_fitness_fe5_round2_test'
$env:SMART_FITNESS_TEST_DATABASE = 'smart_fitness_fe5_round2_test'
if ($env:DB_DATABASE -ne $env:SMART_FITNESS_TEST_DATABASE -or $env:DB_DATABASE -notmatch '^smart_fitness_.*test.*$') {
  throw 'Isolated test database assertion failed.'
}
Set-Location 'E:\Fitness\BE'
& 'E:\Fitness\.tools\php\php.exe' artisan migrate:status
if ($LASTEXITCODE -ne 0) { throw "migrate:status exited $LASTEXITCODE" }
