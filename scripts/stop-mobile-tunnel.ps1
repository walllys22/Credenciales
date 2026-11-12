$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$statePath = Join-Path $projectRoot 'storage/app/private/credencial-tunnel/active.json'
if (-not (Test-Path -LiteralPath $statePath)) { Write-Output 'No hay un tunel de fichas registrado.'; exit 0 }
$state = Get-Content -LiteralPath $statePath -Raw | ConvertFrom-Json
foreach ($kind in @('tunnel', 'gateway')) {
    $ownedProcess = Get-Process -Id $state.($kind + '_pid') -ErrorAction SilentlyContinue
    if ($ownedProcess -and $ownedProcess.StartTime.Ticks -eq $state.($kind + '_started')) { Stop-Process -Id $ownedProcess.Id }
}
Remove-Item -LiteralPath $statePath
Write-Output 'Tunel de fichas detenido.'
