$ErrorActionPreference = 'Stop'

$defaultDir = Join-Path $PSScriptRoot "app\data"
$input = Read-Host "Enter PDF folder path (press Enter for default: $defaultDir)"
$dir = if ($input.Trim()) { $input.Trim() } else { $defaultDir }

if (-not (Test-Path $dir)) {
    Write-Host "ERROR: Folder not found: $dir"
    exit 1
}

$pdfs = Get-ChildItem -Path $dir -File -Filter *.pdf
Write-Host "Found $($pdfs.Count) PDF(s) in $dir"
Write-Host ""

foreach ($f in $pdfs) {
    $name = $f.Name
    Write-Host "=== INSERTING: $name ==="
    $resp = (curl.exe -s -X POST "http://localhost:5000/insert/pdf" -F "file=@$($f.FullName)") -join ""
    $m = [regex]::Match($resp, '"task_id":"([^"]+)"')
    if (-not $m.Success) {
        Write-Host "  NO TASK ID: $resp"
        Start-Sleep -Seconds 3
        continue
    }
    $tid = $m.Groups[1].Value
    $done = $false
    while (-not $done) {
        Start-Sleep -Seconds 5
        $prog = curl.exe -s "http://localhost:5000/insert/progress/$tid"
        if ($prog -match '"status":"done"' -or $prog -match '"status":"error"' -or $prog -match 'Task not found') {
            Write-Host "  FINISHED: $name -> $prog"
            $done = $true
        }
    }
    Start-Sleep -Seconds 1
}
Write-Host "ALL DONE"
