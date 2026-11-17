Add-Type -AssemblyName System.Runtime.WindowsRuntime
[Windows.Storage.StorageFile,Windows.Storage,ContentType=WindowsRuntime] | Out-Null
[Windows.Data.Pdf.PdfDocument,Windows.Data.Pdf,ContentType=WindowsRuntime] | Out-Null
[Windows.Storage.Streams.InMemoryRandomAccessStream,Windows.Storage.Streams,ContentType=WindowsRuntime] | Out-Null
$taskOperation = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object { $_.Name -eq 'AsTask' -and $_.IsGenericMethod -and $_.GetGenericArguments().Count -eq 1 -and $_.GetParameters().Count -eq 1 } | Select-Object -First 1
$taskAction = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object { $_.Name -eq 'AsTask' -and -not $_.IsGenericMethod -and $_.GetParameters().Count -eq 1 } | Select-Object -First 1
function AwaitOperation($operation, [Type]$resultType) { $task = $taskOperation.MakeGenericMethod($resultType).Invoke($null,@($operation)); $task.GetAwaiter().GetResult() }
$sourceFile = AwaitOperation ([Windows.Storage.StorageFile]::GetFileFromPathAsync((Join-Path $PSScriptRoot 'reporte-qa.pdf'))) ([Windows.Storage.StorageFile])
$pdfDocument = AwaitOperation ([Windows.Data.Pdf.PdfDocument]::LoadFromFileAsync($sourceFile)) ([Windows.Data.Pdf.PdfDocument])
Write-Output "PDF pages: $($pdfDocument.PageCount)"
for ($index=0; $index -lt $pdfDocument.PageCount; $index++) {
 $page=$pdfDocument.GetPage($index)
 $stream=New-Object Windows.Storage.Streams.InMemoryRandomAccessStream
 $action=$taskAction.Invoke($null,@($page.RenderToStreamAsync($stream)))
 $action.GetAwaiter().GetResult() | Out-Null
 $inputStream=[System.IO.WindowsRuntimeStreamExtensions]::AsStreamForRead($stream)
 $outputStream=[System.IO.File]::Create((Join-Path $PSScriptRoot "page-$($index+1).png"))
 $inputStream.CopyTo($outputStream)
 $outputStream.Dispose(); $inputStream.Dispose(); $stream.Dispose(); $page.Dispose()
}
