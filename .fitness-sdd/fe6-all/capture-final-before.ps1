$ErrorActionPreference = 'Stop'
$repo = 'E:\Fitness'
$artifact = Join-Path $repo '.fitness-sdd\fe6-all'
$snapshot = Join-Path $artifact 'task-1-round-final-before'
$targets = @(
  'BE/routes/api.php',
  'BE/app/Http/Controllers/Api/Pt/PtDirectServiceController.php',
  'BE/app/Services/Pt/PtDirectService.php',
  'BE/tests/Feature/PtDirectServiceTest.php',
  'FE/src/services/buoi_huan_luyen.api.js',
  'FE/src/services/buoi_huan_luyen.api.test.js',
  'FE/src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.test.js',
  'FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.vue',
  'FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.test.js',
  'FE/src/components/PT/khung_xem_de_xuat.vue',
  'FE/src/components/PT/khung_xem_de_xuat.test.js',
  'docs/BACKEND_API_CONTRACT.md',
  '.fitness-sdd/fe6-all/task-1-report.md'
)
New-Item -ItemType Directory -Path $snapshot -Force | Out-Null
$manifest = @()
foreach ($relative in $targets) {
  $source = Join-Path $repo ($relative.Replace('/', '\'))
  $dest = Join-Path $snapshot ($relative.Replace('/', '\'))
  New-Item -ItemType Directory -Path (Split-Path -Parent $dest) -Force | Out-Null
  if (Test-Path -LiteralPath $source -PathType Leaf) {
    Copy-Item -LiteralPath $source -Destination $dest
    $manifest += "$relative`tPRESENT`t$((Get-Item -LiteralPath $source).Length)"
  } else {
    New-Item -ItemType File -Path "$dest.absent" -Force | Out-Null
    $manifest += "$relative`tABSENT"
  }
}
$manifest | Set-Content -LiteralPath (Join-Path $snapshot 'manifest.tsv') -Encoding utf8
foreach ($relative in $targets | Where-Object { $_ -like 'BE/*' -or $_ -eq 'docs/BACKEND_API_CONTRACT.md' }) {
  $source = Join-Path $repo ($relative.Replace('/', '\'))
  $dest = Join-Path $artifact ("baseline-tree\" + $relative.Replace('/', '\'))
  New-Item -ItemType Directory -Path (Split-Path -Parent $dest) -Force | Out-Null
  if (-not (Test-Path -LiteralPath $dest)) { Copy-Item -LiteralPath $source -Destination $dest }
}
Write-Output "Captured $($targets.Count) targets in $snapshot"
