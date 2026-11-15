# Start from PowerShell. Keep this window open while using the public QR.
$ErrorActionPreference = 'Stop'
$project = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$directory = Join-Path $env:LOCALAPPDATA 'StudioTicketTunnel'
$connector = Join-Path $directory 'cloudflared.exe'
$php = 'C:\xampp\php\php.exe'
$runtime = Join-Path $project 'storage\framework\ticket-public-url.txt'
if (!(Test-Path -LiteralPath $connector)) { throw 'Instala cloudflared.exe en %LOCALAPPDATA%\StudioTicketTunnel.' }
if (Get-NetTCPConnection -LocalPort 18764 -State Listen -ErrorAction SilentlyContinue) { throw 'El puerto local de tickets ya esta en uso.' }
$log = Join-Path $directory ('ticket-' + [guid]::NewGuid() + '.log')
$origin = $null
$tunnel = $null
$publicUrl = $null
try {
    $origin = Start-Process $php -ArgumentList '-S 127.0.0.1:18764 -t public tools/ticket-origin.php' -WorkingDirectory $project -WindowStyle Hidden -PassThru
    Start-Sleep -Milliseconds 600
    if ($origin.HasExited) { throw 'No se pudo iniciar el portal local.' }
    $tunnel = Start-Process $connector -ArgumentList 'tunnel --no-autoupdate --protocol http2 --url http://127.0.0.1:18764' -RedirectStandardError $log -WindowStyle Hidden -PassThru
    $deadline = (Get-Date).AddSeconds(90)
    do {
        Start-Sleep -Seconds 1
        if ($tunnel.HasExited) { throw "El tunel se detuvo. Consulta $log" }
        $match = [regex]::Match([string](Get-Content -Raw -LiteralPath $log),'https://[a-z0-9-]+\.trycloudflare\.com')
        if ($match.Success) {
            $candidate = $match.Value
            $body = & curl.exe --fail --silent --max-time 5 "$candidate/ticket/portal"
            if ($LASTEXITCODE -eq 0 -and ($body -join '') -match 'Portal de tickets') {
                $privateStatus = & curl.exe --silent --max-time 5 -o NUL -w '%{http_code}' "$candidate/home"
                if ($privateStatus -ne '404') { throw 'El acceso no supero la verificacion de rutas privadas.' }
                $publicUrl = $candidate
            }
        }
    } until ($publicUrl -or (Get-Date) -gt $deadline)
    if (!$publicUrl) { throw "No se pudo verificar el portal por HTTPS. Consulta $log" }
    [IO.File]::WriteAllText($runtime,$publicUrl)
    Write-Host "Tickets disponibles: $publicUrl/ticket/portal"
    Write-Host 'Genera un QR nuevo en Historial de ventas. Pulsa Ctrl+C para terminar.'
    while (!$tunnel.HasExited -and !$origin.HasExited) { Start-Sleep -Seconds 2 }
} finally {
    if ($publicUrl -and (Test-Path -LiteralPath $runtime) -and [IO.File]::ReadAllText($runtime) -eq $publicUrl) {
        Remove-Item -LiteralPath $runtime
    }
    foreach ($p in @($tunnel,$origin)) { if ($p -and !$p.HasExited) { $p.Kill() } }
}
