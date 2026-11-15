param(
    [string]$filePath,
    [string]$content
)
[System.IO.File]::AppendAllText($filePath, $content, [System.Text.Encoding]::UTF8)
