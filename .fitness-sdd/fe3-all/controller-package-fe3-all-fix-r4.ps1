$ErrorActionPreference = 'Stop'
$repo = 'E:\Fitness'
Set-Location -LiteralPath $repo
$scratch = Join-Path $repo '.fitness-sdd/fe3-all'
$before = Get-Content -Raw -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-4-before-manifest.json') | ConvertFrom-Json
$inventory = Get-Content -Raw -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-4-preservation-manifest.json') | ConvertFrom-Json
$allowed = @(Get-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-4-targets.txt') | ForEach-Object { $_.Replace('\', '/') })
$currentRoot = Join-Path $scratch 'task-fe3-all-fix-round-4-current'
if (Test-Path -LiteralPath $currentRoot) { throw 'Current FE3 fix round-4 evidence already exists; refusing to overwrite.' }
$sha = [Security.Cryptography.SHA256]::Create()
$utf8 = New-Object Text.UTF8Encoding $false
$delta = New-Object Text.StringBuilder
$current = foreach ($entry in $before) {
    $target = if ($entry.target -is [string]) { $entry.target } else { [string]$entry.target.value }
    $source = Join-Path $repo $target
    $destination = Join-Path $currentRoot $target
    [IO.Directory]::CreateDirectory([IO.Path]::GetDirectoryName($destination)) | Out-Null
    if (Test-Path -LiteralPath $source -PathType Leaf) {
        [IO.File]::Copy($source, $destination, $false)
        $bytes = [IO.File]::ReadAllBytes($source)
        $hash = [BitConverter]::ToString($sha.ComputeHash($bytes)).Replace('-', '')
        $changed = $entry.state -eq 'ABSENT' -or $hash -ne $entry.sha256
        $base = Join-Path $repo $entry.snapshot
        $diff = @(& rtk proxy git diff --no-index --binary --no-ext-diff -- $base $destination)
        if ($LASTEXITCODE -gt 1) { throw "Diff generation failed: $target" }
        if ($diff.Count) { [void]$delta.AppendLine(($diff -join "`n")) }
        [pscustomobject]@{target=$target; state='PRESENT'; bytes=$bytes.Length; sha256=$hash; changed=$changed; snapshot=$destination.Substring($repo.Length + 1)}
    } else {
        $marker = $destination + '.absent'
        [IO.File]::WriteAllText($marker, 'ABSENT AFTER FE3-ALL FIX ROUND 4')
        [pscustomobject]@{target=$target; state='ABSENT'; bytes=0; sha256=$null; changed=($entry.state -ne 'ABSENT'); snapshot=$marker.Substring($repo.Length + 1)}
    }
}
[IO.File]::WriteAllText((Join-Path $scratch 'task-fe3-all-fix-round-4-exact-delta.patch'), $delta.ToString(), $utf8)
$current | ConvertTo-Json | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-4-current-manifest.json')
$unexpected = @()
foreach ($entry in $inventory) {
    $full = Join-Path $repo $entry.path
    $hash = if (Test-Path -LiteralPath $full -PathType Leaf) { [BitConverter]::ToString($sha.ComputeHash([IO.File]::ReadAllBytes($full))).Replace('-', '') } else { 'ABSENT' }
    if ($hash -ne $entry.sha256 -and $entry.path.Replace('\', '/') -notin $allowed) { $unexpected += $entry.path }
}
$paths = @(& rtk proxy git ls-files --cached --others --exclude-standard) | Where-Object { $_ -notlike '.fitness-sdd/*' }
foreach ($path in $paths) { if ($path -notin $inventory.path -and $path.Replace('\', '/') -notin $allowed) { $unexpected += $path } }
$staged = @(& rtk proxy git diff --cached --binary)
$productCurrent = @($current | Where-Object { $_.target.Replace('\', '/') -in $allowed })
$reportEntry = @($current | Where-Object { $_.target.Replace('\', '/') -notin $allowed })
$uniqueUnexpected = @($unexpected | Sort-Object -Unique)
$gate = [pscustomobject]@{
    unexpected_paths=$uniqueUnexpected; unexpected_count=$uniqueUnexpected.Count; staged_empty=($staged.Count -eq 0);
    checked_preservation_paths=$inventory.Count; product_target_count=$allowed.Count;
    changed_product_targets=@($productCurrent | Where-Object changed | ForEach-Object target);
    unchanged_product_targets=@($productCurrent | Where-Object { !$_.changed } | ForEach-Object target);
    report_changed=[bool]($reportEntry | Where-Object changed)
}
$gate | ConvertTo-Json -Depth 6 | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-4-controller-gates.json')
$gate | ConvertTo-Json -Depth 6
if ($gate.unexpected_count -or !$gate.staged_empty) { throw 'FE3 fix round-4 preservation gate failed' }
