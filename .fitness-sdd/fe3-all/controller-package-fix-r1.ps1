$ErrorActionPreference = 'Stop'
$repo = 'E:\Fitness'
Set-Location -LiteralPath $repo
$scratch = Join-Path $repo '.fitness-sdd/fe3-all'
$prefix = 'entry-gate-repair-fix-round-1'
$utf8 = New-Object Text.UTF8Encoding $false
$sha = [Security.Cryptography.SHA256]::Create()
$before = Get-Content -Raw -Encoding UTF8 -LiteralPath (Join-Path $scratch "$prefix-before-manifest.json") | ConvertFrom-Json
$currentRoot = Join-Path $scratch "$prefix-current"
if (Test-Path -LiteralPath $currentRoot) { throw 'Current evidence exists; do not overwrite.' }
$delta = New-Object Text.StringBuilder
$current = foreach ($entry in $before) {
    $source = Join-Path $repo $entry.target
    $destination = Join-Path $currentRoot $entry.target
    [IO.Directory]::CreateDirectory([IO.Path]::GetDirectoryName($destination)) | Out-Null
    [IO.File]::Copy($source, $destination, $false)
    $hash = [BitConverter]::ToString($sha.ComputeHash([IO.File]::ReadAllBytes($source))).Replace('-', '')
    $diff = @(& rtk proxy git diff --no-index --binary --no-ext-diff -- (Join-Path $repo $entry.snapshot) $destination)
    if ($LASTEXITCODE -gt 1) { throw 'Diff generation failed' }
    if ($diff.Count) { [void]$delta.AppendLine(($diff -join "`n")) }
    [pscustomobject]@{target=$entry.target; sha256=$hash; changed=($hash -ne $entry.sha256); snapshot=$destination}
}
[IO.File]::WriteAllText((Join-Path $scratch "$prefix-exact-delta.patch"), $delta.ToString(), $utf8)
$current | ConvertTo-Json | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch "$prefix-current-manifest.json")
$inventory = Get-Content -Raw -Encoding UTF8 -LiteralPath (Join-Path $scratch 'handoff-preservation-manifest-20260912.json') | ConvertFrom-Json
$allowed = @($before | ForEach-Object { $_.target.Replace('\', '/') })
$unexpected = @()
foreach ($entry in $inventory) {
    $source = Join-Path $repo $entry.path
    $hash = if (Test-Path -LiteralPath $source -PathType Leaf) { [BitConverter]::ToString($sha.ComputeHash([IO.File]::ReadAllBytes($source))).Replace('-', '') } else { 'ABSENT' }
    if ($hash -ne $entry.sha256 -and $entry.path -notin $allowed) { $unexpected += $entry.path }
}
$paths = @(& rtk proxy git ls-files --cached --others --exclude-standard) | Where-Object { $_ -notlike '.fitness-sdd/*' }
foreach ($path in $paths) { if ($path -notin $inventory.path -and $path -notin $allowed) { $unexpected += $path } }
$status = @(& rtk proxy git status --porcelain=v1 --untracked-files=all)
[IO.File]::WriteAllText((Join-Path $scratch "$prefix-status-current.txt"), ($status -join "`n"), $utf8)
$staged = @(& rtk proxy git diff --cached --binary)
[IO.File]::WriteAllText((Join-Path $scratch "$prefix-staged-current.patch"), ($staged -join "`n"), $utf8)
$working = @(& rtk proxy git diff --binary)
[IO.File]::WriteAllText((Join-Path $scratch "$prefix-working-current.patch"), ($working -join "`n"), $utf8)
$gate = [pscustomobject]@{unexpected_paths=$unexpected; unexpected_count=$unexpected.Count; staged_empty=($staged.Count -eq 0); checked_preservation_paths=$inventory.Count; changed_targets=@($current | Where-Object changed | ForEach-Object target)}
$gate | ConvertTo-Json -Depth 5 | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch "$prefix-controller-gates.json")
$gate | ConvertTo-Json -Depth 5
if ($unexpected.Count -or $staged.Count) { throw 'Preservation gate failed' }
