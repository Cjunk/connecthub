param(
    [string]$RootPath = "F:\connecthub",
    [string]$NestedPath = "F:\connecthub\connecthub",
    [switch]$ShowAll
)

$ErrorActionPreference = 'Stop'

$resolvedRoot = (Resolve-Path $RootPath).Path
$resolvedNested = (Resolve-Path $NestedPath).Path

function Get-HashMap {
    param(
        [string]$BasePath,
        [string]$SubPath,
        [string[]]$Extensions
    )

    $fullPath = Join-Path $BasePath $SubPath
    if (!(Test-Path $fullPath -PathType Container)) {
        return @{}
    }

    $files = Get-ChildItem -Path $fullPath -Recurse -File |
        Where-Object { $Extensions -contains $_.Extension.ToLowerInvariant() }

    $map = @{}
    foreach ($file in $files) {
        $relative = $file.FullName.Substring($fullPath.Length).TrimStart('\\')
        $hash = (Get-FileHash -Path $file.FullName -Algorithm SHA256).Hash
        $map[$relative] = $hash
    }

    return $map
}

$extensions = @('.php', '.js', '.css')

$mapping = @(
    @{ Name = 'config'; RootSub = 'config'; NestedSub = 'config' },
    @{ Name = 'src'; RootSub = 'src'; NestedSub = 'src' },
    @{ Name = 'public'; RootSub = ''; NestedSub = 'public' }
)

$differing = New-Object System.Collections.Generic.List[string]
$missingInNested = New-Object System.Collections.Generic.List[string]
$missingInRoot = New-Object System.Collections.Generic.List[string]

foreach ($entry in $mapping) {
    $rootMap = Get-HashMap -BasePath $resolvedRoot -SubPath $entry.RootSub -Extensions $extensions
    $nestedMap = Get-HashMap -BasePath $resolvedNested -SubPath $entry.NestedSub -Extensions $extensions

    $allKeys = @($rootMap.Keys + $nestedMap.Keys | Sort-Object -Unique)
    foreach ($key in $allKeys) {
        $rootKey = "$($entry.RootSub)\\$key".TrimStart('\\')
        $nestedKey = "$($entry.NestedSub)\\$key".TrimStart('\\')

        if (!$rootMap.ContainsKey($key)) {
            $missingInRoot.Add("root:$rootKey < nested:$nestedKey")
            continue
        }

        if (!$nestedMap.ContainsKey($key)) {
            $missingInNested.Add("root:$rootKey > nested:$nestedKey")
            continue
        }

        if ($rootMap[$key] -ne $nestedMap[$key]) {
            $differing.Add("root:$rootKey != nested:$nestedKey")
        }
    }
}

Write-Output "SUMMARY"
Write-Output "Differing: $($differing.Count)"
Write-Output "MissingInNested: $($missingInNested.Count)"
Write-Output "MissingInRoot: $($missingInRoot.Count)"

Write-Output ""
Write-Output "DIFFERING_TOP"
if ($differing.Count -eq 0) {
    Write-Output "(none)"
} else {
    ($differing | Select-Object -First 60) | ForEach-Object { Write-Output $_ }
}

Write-Output ""
Write-Output "MISSING_IN_NESTED_TOP"
if ($missingInNested.Count -eq 0) {
    Write-Output "(none)"
} else {
    ($missingInNested | Select-Object -First 60) | ForEach-Object { Write-Output $_ }
}

Write-Output ""
Write-Output "MISSING_IN_ROOT_TOP"
if ($missingInRoot.Count -eq 0) {
    Write-Output "(none)"
} else {
    ($missingInRoot | Select-Object -First 60) | ForEach-Object { Write-Output $_ }
}

if ($ShowAll) {
    Write-Output ""
    Write-Output "ALL_DIFFERING"
    $differing | ForEach-Object { Write-Output $_ }
    Write-Output ""
    Write-Output "ALL_MISSING_IN_NESTED"
    $missingInNested | ForEach-Object { Write-Output $_ }
    Write-Output ""
    Write-Output "ALL_MISSING_IN_ROOT"
    $missingInRoot | ForEach-Object { Write-Output $_ }
}
