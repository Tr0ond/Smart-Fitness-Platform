$ErrorActionPreference = 'Stop'
$repo = 'E:\Fitness'
Set-Location -LiteralPath $repo
$scratch = Join-Path $repo '.fitness-sdd/fe3-all'
$targetsFile = Join-Path $scratch 'task-fe3-all-fix-round-2-targets.txt'
$beforeRoot = Join-Path $scratch 'task-fe3-all-fix-round-2-before'
if (Test-Path -LiteralPath $beforeRoot) { throw 'FE3 fix round-2 snapshot already exists; refusing to overwrite.' }

$sha = [Security.Cryptography.SHA256]::Create()
$targets = @(Get-Content -Encoding UTF8 -LiteralPath $targetsFile | Where-Object { $_.Trim() -ne '' })
$workflowReport = '.fitness-sdd/fe3-all/task-fe3-all-report.md'
$snapshotPaths = @($targets) + $workflowReport
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
        [IO.File]::WriteAllText($marker, 'ABSENT BEFORE FE3-ALL FIX ROUND 2')
        [pscustomobject]@{target=$target; state='ABSENT'; bytes=0; sha256=$null; snapshot=$marker.Substring($repo.Length + 1)}
    }
}
$manifest | ConvertTo-Json | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-2-before-manifest.json')

$paths = @(& rtk proxy git ls-files --cached --others --exclude-standard) | Where-Object { $_ -notlike '.fitness-sdd/*' }
$inventory = foreach ($path in $paths) {
    $full = Join-Path $repo $path
    if (Test-Path -LiteralPath $full -PathType Leaf) {
        $bytes = [IO.File]::ReadAllBytes($full)
        [pscustomobject]@{path=$path; sha256=[BitConverter]::ToString($sha.ComputeHash($bytes)).Replace('-', '')}
    }
}
$inventory | ConvertTo-Json | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-2-preservation-manifest.json')
& rtk proxy git status --porcelain=v1 --untracked-files=all | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-2-status-before.txt')
& rtk proxy git diff --binary | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-2-working-before.patch')
& rtk proxy git diff --cached --binary | Set-Content -Encoding UTF8 -LiteralPath (Join-Path $scratch 'task-fe3-all-fix-round-2-staged-before.patch')
[pscustomobject]@{product_target_count=$targets.Count; snapshot_path_count=$snapshotPaths.Count; present_count=@($manifest | Where-Object state -eq 'PRESENT').Count; absent_count=@($manifest | Where-Object state -eq 'ABSENT').Count; preservation_paths=$inventory.Count} | ConvertTo-Json -Compress
