$ErrorActionPreference = 'Stop'
$repo = 'E:\Fitness'
Set-Location -LiteralPath $repo
$scratch = Join-Path $repo '.fitness-sdd/fe3-all'
$targets = @(Get-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'final-fix-round-5-targets.txt') | Where-Object { $_.Trim() -ne '' })
$backendEntry = @(Get-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'final-fix-round-5-backend-entry-paths.txt') | Where-Object { $_.Trim() -ne '' })
$beforeRoot = Join-Path $scratch 'final-fix-round-5-before'
if (Test-Path -LiteralPath $beforeRoot) { throw 'FE3 final fix round-5 snapshot already exists; refusing to overwrite.' }
$sha = [Security.Cryptography.SHA256]::Create()
$report = '.fitness-sdd/fe3-all/task-fe3-all-report.md'
$snapshotPaths = @($targets) + $report
$manifest = foreach ($target in $snapshotPaths) {
    $source = Join-Path $repo $target
    $destination = Join-Path $beforeRoot $target
    [IO.Directory]::CreateDirectory([IO.Path]::GetDirectoryName($destination)) | Out-Null
    if (Test-Path -LiteralPath $source -PathType Leaf) {
        [IO.File]::Copy($source, $destination, $false)
        $bytes = [IO.File]::ReadAllBytes($source)
        [pscustomobject]@{target=$target; state='PRESENT'; bytes=$bytes.Length; sha256=[BitConverter]::ToString($sha.ComputeHash($bytes)).Replace('-', ''); snapshot=$destination.Substring($repo.Length + 1)}
    } else {
        $marker = $destination + '.absent'
        [IO.File]::WriteAllText($marker, 'ABSENT BEFORE FE3-ALL FINAL FIX ROUND 5')
        [pscustomobject]@{target=$target; state='ABSENT'; bytes=0; sha256=$null; snapshot=$marker.Substring($repo.Length + 1)}
    }
}
$manifest | ConvertTo-Json | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'final-fix-round-5-before-manifest.json')
$backendManifest = foreach ($target in $backendEntry) {
    $source = Join-Path $repo $target
    if (!(Test-Path -LiteralPath $source -PathType Leaf)) { throw "Missing Backend entry-repair path: $target" }
    $bytes = [IO.File]::ReadAllBytes($source)
    [pscustomobject]@{path=$target; bytes=$bytes.Length; sha256=[BitConverter]::ToString($sha.ComputeHash($bytes)).Replace('-', '')}
}
$backendManifest | ConvertTo-Json | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'final-fix-round-5-backend-entry-before-manifest.json')
$paths = @(& rtk proxy git ls-files --cached --others --exclude-standard) | Where-Object { $_ -notlike '.fitness-sdd/*' }
$inventory = foreach ($path in $paths) {
    $full = Join-Path $repo $path
    if (Test-Path -LiteralPath $full -PathType Leaf) {
        $bytes = [IO.File]::ReadAllBytes($full)
        [pscustomobject]@{path=$path; sha256=[BitConverter]::ToString($sha.ComputeHash($bytes)).Replace('-', '')}
    }
}
$inventory | ConvertTo-Json | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'final-fix-round-5-preservation-manifest.json')
& rtk proxy git status --porcelain=v1 --untracked-files=all | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'final-fix-round-5-status-before.txt')
& rtk proxy git diff --binary | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'final-fix-round-5-working-before.patch')
& rtk proxy git diff --cached --binary | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'final-fix-round-5-staged-before.patch')
[pscustomobject]@{product_target_count=$targets.Count; snapshot_path_count=$snapshotPaths.Count; present_count=@($manifest | Where-Object state -eq 'PRESENT').Count; absent_count=@($manifest | Where-Object state -eq 'ABSENT').Count; backend_entry_paths=$backendManifest.Count; preservation_paths=$inventory.Count} | ConvertTo-Json -Compress
