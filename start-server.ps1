$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$logsDirectory = Join-Path $projectRoot 'journaux'
$outputLog = Join-Path $logsDirectory 'server-output.log'
$errorLog = Join-Path $logsDirectory 'server-error.log'

if (-not (Test-Path $logsDirectory)) {
    New-Item -ItemType Directory -Path $logsDirectory | Out-Null
}

Write-Host "Starting PHP server for Projet_echec2 on http://127.0.0.1:8000"
Write-Host "Output log: $outputLog"
Write-Host "Error log: $errorLog"

Push-Location $projectRoot
try {
    php -S 127.0.0.1:8000 -t $projectRoot router.php 2>> $errorLog | Tee-Object -FilePath $outputLog -Append
}
finally {
    Pop-Location
}
