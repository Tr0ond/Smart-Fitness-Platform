$ErrorActionPreference = 'Stop'
$repo = 'E:\Fitness'
$artifact = Join-Path $repo '.fitness-sdd\fe6-all'
$before = Join-Path $artifact 'task-1-round-final-before'
$after = Join-Path $artifact 'task-1-round-final-after'
New-Item -ItemType Directory -Path $after -Force | Out-Null
$sha = [System.Security.Cryptography.SHA256]::Create()
$results = @()
foreach ($line in [System.IO.File]::ReadAllLines((Join-Path $before 'manifest.tsv'))) {
  $relative = ($line -split "`t")[0]
  if (-not $relative) { continue }
  $source = Join-Path $repo ($relative.Replace('/', '\'))
  $old = Join-Path $before ($relative.Replace('/', '\'))
  $copy = Join-Path $after ($relative.Replace('/', '\'))
  New-Item -ItemType Directory -Path (Split-Path -Parent $copy) -Force | Out-Null
  if (-not (Test-Path -LiteralPath $source -PathType Leaf)) { throw "Missing final target: $relative" }
  Copy-Item -LiteralPath $source -Destination $copy
  $oldHash = [System.BitConverter]::ToString($sha.ComputeHash([System.IO.File]::ReadAllBytes($old)))
  $newHash = [System.BitConverter]::ToString($sha.ComputeHash([System.IO.File]::ReadAllBytes($source)))
  $state = if ($oldHash -eq $newHash) { 'UNCHANGED' } else { 'CHANGED' }
  $results += "$relative`t$state`t$newHash"
}
$results | Set-Content -LiteralPath (Join-Path $artifact 'task-1-round-final-delta.tsv') -Encoding utf8
$results | Write-Output
