$ErrorActionPreference = 'Stop'
$repo = 'E:\Fitness'
Set-Location -LiteralPath $repo
$scratch = Join-Path $repo '.fitness-sdd/fe3-all'
$manifest = Get-Content -Raw -Encoding UTF8 -LiteralPath (Join-Path $scratch 'entry-gate-repair-fix-round-1-before-manifest.json') | ConvertFrom-Json
$sha = [Security.Cryptography.SHA256]::Create()
$results = foreach ($entry in $manifest) {
    $live = [IO.File]::ReadAllBytes((Join-Path $repo $entry.target))
    $before = [IO.File]::ReadAllBytes((Join-Path $repo $entry.snapshot))
    $hash = [BitConverter]::ToString($sha.ComputeHash($live)).Replace('-', '')
    $equal = [Convert]::ToBase64String($live).Equals([Convert]::ToBase64String($before))
    if (!$equal -or $hash -ne $entry.sha256) { throw "Snapshot drift: $($entry.target)" }
    [pscustomobject]@{target=$entry.target; byte_equal=$equal; sha256=$hash}
}
$results | ConvertTo-Json | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'handoff-revalidation-20260912.json')
$paths = @(& rtk proxy git ls-files --cached --others --exclude-standard) | Where-Object { $_ -notlike '.fitness-sdd/*' }
$inventory = foreach ($path in $paths) {
    $full = Join-Path $repo $path
    if (Test-Path -LiteralPath $full -PathType Leaf) {
        [pscustomobject]@{path=$path; sha256=[BitConverter]::ToString($sha.ComputeHash([IO.File]::ReadAllBytes($full))).Replace('-', '')}
    }
}
$inventory | ConvertTo-Json | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'handoff-preservation-manifest-20260912.json')
& rtk proxy git status --porcelain=v1 --untracked-files=all | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'handoff-status-20260912.txt')
$results | ConvertTo-Json -Compress
Write-Output "SNAPSHOT_DELTA_COUNT=0; PRESERVATION_PATHS=$($inventory.Count)"
