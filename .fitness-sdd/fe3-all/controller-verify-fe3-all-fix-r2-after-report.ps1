$ErrorActionPreference = 'Stop'
$repo = 'E:\Fitness'
Set-Location -LiteralPath $repo
$scratch = Join-Path $repo '.fitness-sdd/fe3-all'
$before = Get-Content -Raw -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-2-before-manifest.json') | ConvertFrom-Json
$current = Get-Content -Raw -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-2-current-manifest.json') | ConvertFrom-Json
$inventory = Get-Content -Raw -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-2-preservation-manifest.json') | ConvertFrom-Json
$allowed = @(Get-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-2-targets.txt') | ForEach-Object { $_.Replace('\', '/') })
$sha = [Security.Cryptography.SHA256]::Create()

$productMismatches = @()
foreach ($entry in $current) {
    $target = if ($entry.target -is [string]) { $entry.target } else { [string]$entry.target.value }
    if ($target.Replace('\', '/') -notin $allowed) { continue }
    $full = Join-Path $repo $target
    $hash = if (Test-Path -LiteralPath $full -PathType Leaf) { [BitConverter]::ToString($sha.ComputeHash([IO.File]::ReadAllBytes($full))).Replace('-', '') } else { 'ABSENT' }
    if ($hash -ne $entry.sha256) { $productMismatches += $target }
}

$reportPath = '.fitness-sdd/fe3-all/task-fe3-all-report.md'
$beforeReport = @($before | Where-Object {
    $candidate = if ($_.target -is [string]) { $_.target } else { [string]$_.target.value }
    $candidate -eq $reportPath
})[0]
$liveReportHash = [BitConverter]::ToString($sha.ComputeHash([IO.File]::ReadAllBytes((Join-Path $repo $reportPath)))).Replace('-', '')
$reportChanged = $liveReportHash -ne $beforeReport.sha256

$unexpected = @()
foreach ($entry in $inventory) {
    $full = Join-Path $repo $entry.path
    $hash = if (Test-Path -LiteralPath $full -PathType Leaf) { [BitConverter]::ToString($sha.ComputeHash([IO.File]::ReadAllBytes($full))).Replace('-', '') } else { 'ABSENT' }
    if ($hash -ne $entry.sha256 -and $entry.path.Replace('\', '/') -notin $allowed) { $unexpected += $entry.path }
}
$paths = @(& rtk proxy git ls-files --cached --others --exclude-standard) | Where-Object { $_ -notlike '.fitness-sdd/*' }
foreach ($path in $paths) {
    if ($path -notin $inventory.path -and $path.Replace('\', '/') -notin $allowed) { $unexpected += $path }
}
$staged = @(& rtk proxy git diff --cached --name-only)
$gate = [pscustomobject]@{
    product_target_count = $allowed.Count
    product_hash_mismatches = @($productMismatches)
    product_hash_mismatch_count = $productMismatches.Count
    report_changed_after_snapshot = $reportChanged
    unexpected_paths = @($unexpected | Sort-Object -Unique)
    unexpected_count = @($unexpected | Sort-Object -Unique).Count
    checked_preservation_paths = $inventory.Count
    staged_empty = ($staged.Count -eq 0)
}
$gate | ConvertTo-Json -Depth 6 | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-2-controller-gates-after-report.json')
$gate | ConvertTo-Json -Depth 6
if ($gate.product_hash_mismatch_count -or !$gate.report_changed_after_snapshot -or $gate.unexpected_count -or !$gate.staged_empty) { throw 'FE3 round-2 post-report verification failed' }
