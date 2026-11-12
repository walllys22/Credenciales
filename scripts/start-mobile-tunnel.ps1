param([int]$Port = 8789)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$runtimePath = Join-Path $projectRoot 'storage/app/private/credencial-tunnel'
$statePath = Join-Path $runtimePath 'active.json'
$connectorPath = Join-Path $runtimePath 'cloudflared.exe'
New-Item -ItemType Directory -Path $runtimePath -Force | Out-Null
function Test-MobileOrigin([string]$Origin) {
    try {
        $response = Invoke-WebRequest -Uri ($Origin.TrimEnd('/') + '/css/credencial-movil.css') -UseBasicParsing -TimeoutSec 5 -MaximumRedirection 0
        return $response.StatusCode -eq 200 -and $response.Headers['Content-Type'] -match 'text/css' -and $response.Content.Contains('.mobile-ficha')
    } catch { return $false }
}
if (Test-Path -LiteralPath $statePath) {
    $state = Get-Content -LiteralPath $statePath -Raw | ConvertFrom-Json
    $existingGateway = Get-Process -Id $state.gateway_pid -ErrorAction SilentlyContinue
    $existingConnector = Get-Process -Id $state.tunnel_pid -ErrorAction SilentlyContinue
    if ($existingGateway -and $existingConnector -and $existingGateway.StartTime.Ticks -eq $state.gateway_started -and $existingConnector.StartTime.Ticks -eq $state.tunnel_started -and (Test-MobileOrigin $state.url)) {
        Write-Output ('Tunel HTTPS de fichas activo: ' + $state.url)
        exit 0
    }
    foreach ($kind in @('gateway', 'tunnel')) {
        $ownedProcess = Get-Process -Id $state.($kind + '_pid') -ErrorAction SilentlyContinue
        if ($ownedProcess -and $ownedProcess.StartTime.Ticks -eq $state.($kind + '_started')) { Stop-Process -Id $ownedProcess.Id }
    }
    Remove-Item -LiteralPath $statePath
}
if (-not (Test-Path -LiteralPath $connectorPath)) { throw 'Falta cloudflared.exe en storage/app/private/credencial-tunnel (distribucion oficial de Cloudflare).' }
$signature = Get-AuthenticodeSignature -LiteralPath $connectorPath
if ($signature.Status -ne 'Valid' -or $signature.SignerCertificate.Subject -notmatch 'Cloudflare') { throw 'La firma de cloudflared no es valida.' }
$probe = New-Object Net.Sockets.TcpClient
$portBusy = $false
try { $probe.Connect('127.0.0.1', $Port); $portBusy = $true } catch { } finally { $probe.Dispose() }
if ($portBusy) { throw ('El puerto ' + $Port + ' ya esta en uso.') }
$gateway = $null
$connector = $null
try {
    $phpPath = (Get-Command php).Source
    $gateway = Start-Process -FilePath $phpPath -ArgumentList @('-S', "127.0.0.1:$Port", '-t', ('"' + $projectRoot + '/public"'), ('"' + $PSScriptRoot + '/mobile-router.php"')) -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $runtimePath 'gateway.out.log') -RedirectStandardError (Join-Path $runtimePath 'gateway.err.log')
    $connector = Start-Process -FilePath $connectorPath -ArgumentList @('--no-autoupdate', 'tunnel', '--protocol', 'http2', '--url', "http://127.0.0.1:$Port") -WorkingDirectory $runtimePath -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $runtimePath 'tunnel.out.log') -RedirectStandardError (Join-Path $runtimePath 'tunnel.err.log')
    $deadline = (Get-Date).AddSeconds(40)
    $publicUrl = $null
    while ((Get-Date) -lt $deadline -and -not $publicUrl) {
        Start-Sleep -Milliseconds 500
        if ($gateway.HasExited -or $connector.HasExited) { throw 'Uno de los procesos termino; revisa los logs privados del tunel.' }
        $logText = Get-Content -LiteralPath (Join-Path $runtimePath 'tunnel.err.log') -Raw -ErrorAction SilentlyContinue
        if ($logText -match 'https://[a-z0-9-]+\.trycloudflare\.com') { $publicUrl = $Matches[0] }
    }
    if (-not $publicUrl) { throw 'No se obtuvo una URL publica HTTPS.' }
    $verified = $false
    $deadline = (Get-Date).AddSeconds(30)
    while ((Get-Date) -lt $deadline -and -not $verified) {
        $verified = Test-MobileOrigin $publicUrl
        if (-not $verified) { Start-Sleep -Milliseconds 500 }
    }
    if (-not $verified) { throw 'El tunel no responde por HTTPS. No se guardara como activo.' }
    $state = @{url=$publicUrl; gateway_pid=$gateway.Id; gateway_started=$gateway.StartTime.Ticks; tunnel_pid=$connector.Id; tunnel_started=$connector.StartTime.Ticks; port=$Port}
    [IO.File]::WriteAllText($statePath, ($state | ConvertTo-Json), (New-Object Text.UTF8Encoding($false)))
    Write-Output ('Tunel HTTPS de fichas verificado: ' + $publicUrl)
    Write-Output 'Vuelve a abrir la credencial y escanea su QR actualizado. Mantenga este equipo, MySQL e Internet encendidos.'
} catch {
    if ($connector -and -not $connector.HasExited) { Stop-Process -Id $connector.Id }
    if ($gateway -and -not $gateway.HasExited) { Stop-Process -Id $gateway.Id }
    throw
}
