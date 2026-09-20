$ErrorActionPreference = 'Stop'
$repo = 'E:\Fitness'
Set-Location -LiteralPath $repo
$scratch = Join-Path $repo '.fitness-sdd/fe3-all'
$before = Get-Content -Raw -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-before-manifest.json') | ConvertFrom-Json
$inventory = Get-Content -Raw -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-preservation-manifest.json') | ConvertFrom-Json
$currentRoot = Join-Path $scratch 'task-fe3-all-round-1-current'
if (Test-Path -LiteralPath $currentRoot) { throw 'Current FE3 evidence already exists; refusing to overwrite.' }
$sha = [Security.Cryptography.SHA256]::Create()
$utf8 = New-Object Text.UTF8Encoding $false
$delta = New-Object Text.StringBuilder
$current = foreach ($entry in $before) {
    $target = if ($entry.target -is [string]) { $entry.target } else { [string] $entry.target.value }
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
        [IO.File]::WriteAllText($marker, 'ABSENT AFTER FE3-ALL')
        [pscustomobject]@{target=$target; state='ABSENT'; bytes=0; sha256=$null; changed=($entry.state -ne 'ABSENT'); snapshot=$marker.Substring($repo.Length + 1)}
    }
}
[IO.File]::WriteAllText((Join-Path $scratch 'task-fe3-all-round-1-exact-delta.patch'), $delta.ToString(), $utf8)
$current | ConvertTo-Json | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-current-manifest.json')
$allowed = @($before | ForEach-Object { if ($_.target -is [string]) { $_.target.Replace('\', '/') } else { ([string]$_.target.value).Replace('\', '/') } })
$unexpected = @()
foreach ($entry in $inventory) {
    $source = Join-Path $repo $entry.path
    $hash = if (Test-Path -LiteralPath $source -PathType Leaf) { [BitConverter]::ToString($sha.ComputeHash([IO.File]::ReadAllBytes($source))).Replace('-', '') } else { 'ABSENT' }
    if ($hash -ne $entry.sha256 -and $entry.path -notin $allowed) { $unexpected += $entry.path }
}
$paths = @(& rtk proxy git ls-files --cached --others --exclude-standard) | Where-Object { $_ -notlike '.fitness-sdd/*' }
foreach ($path in $paths) { if ($path -notin $inventory.path -and $path -notin $allowed) { $unexpected += $path } }
$status = @(& rtk proxy git status --porcelain=v1 --untracked-files=all)
[IO.File]::WriteAllText((Join-Path $scratch 'task-fe3-all-status-current.txt'), ($status -join "`n"), $utf8)
$staged = @(& rtk proxy git diff --cached --binary)
[IO.File]::WriteAllText((Join-Path $scratch 'task-fe3-all-staged-current.patch'), ($staged -join "`n"), $utf8)
$working = @(& rtk proxy git diff --binary)
[IO.File]::WriteAllText((Join-Path $scratch 'task-fe3-all-working-current.patch'), ($working -join "`n"), $utf8)
$gate = [pscustomobject]@{unexpected_paths=$unexpected; unexpected_count=$unexpected.Count; staged_empty=($staged.Count -eq 0); checked_preservation_paths=$inventory.Count; target_count=$before.Count; changed_targets=@($current | Where-Object changed | ForEach-Object target); unchanged_targets=@($current | Where-Object { !$_.changed } | ForEach-Object target)}
$gate | ConvertTo-Json -Depth 6 | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-controller-gates.json')
$gate | ConvertTo-Json -Depth 6
if ($unexpected.Count -or $staged.Count) { throw 'FE3 preservation gate failed' }
