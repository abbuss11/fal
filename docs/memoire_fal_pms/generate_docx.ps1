param(
    [string]$MarkdownPath = 'docs/memoire_fal_pms/MEMOIRE_FAL_PMS_LICENCE.md',
    [string]$OutputDocx = 'docs/memoire_fal_pms/MEMOIRE_FAL_PMS_LICENCE.docx'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Escape-XmlText {
    param([string]$Text)
    if ($null -eq $Text) { return '' }
    return [System.Security.SecurityElement]::Escape($Text)
}

function New-RunXml {
    param(
        [string]$Text,
        [bool]$Bold = $false
    )

    $escaped = Escape-XmlText $Text
    if ($escaped.Length -eq 0) {
        return '<w:r><w:t xml:space="preserve"></w:t></w:r>'
    }

    $rPr = ''
    if ($Bold) {
        $rPr = '<w:rPr><w:b/></w:rPr>'
    }

    return "<w:r>$rPr<w:t xml:space=`"preserve`">$escaped</w:t></w:r>"
}

function Convert-InlineRuns {
    param([string]$Text)

    if ($null -eq $Text) { $Text = '' }

    # [label](url) -> label (url)
    $t = [regex]::Replace($Text, '\[([^\]]+)\]\(([^\)]+)\)', '$1 ($2)')

    $parts = [regex]::Split($t, '(\*\*.+?\*\*)')
    $runs = New-Object System.Collections.Generic.List[string]

    foreach ($part in $parts) {
        if ([string]::IsNullOrEmpty($part)) { continue }

        if ($part -match '^\*\*(.+)\*\*$') {
            $runs.Add((New-RunXml -Text $Matches[1] -Bold $true))
        } else {
            $runs.Add((New-RunXml -Text $part -Bold $false))
        }
    }

    if ($runs.Count -eq 0) {
        return (New-RunXml -Text '' -Bold $false)
    }

    return ($runs -join '')
}

function New-ParagraphXml {
    param(
        [string]$Text,
        [string]$StyleId = ''
    )

    $pPr = ''
    if ($StyleId) {
        $pPr = "<w:pPr><w:pStyle w:val=`"$StyleId`"/></w:pPr>"
    }

    $runs = Convert-InlineRuns -Text $Text
    return "<w:p>$pPr$runs</w:p>"
}

function Get-SvgDimensions {
    param([string]$Path)

    $defaultWidth = 960
    $defaultHeight = 540

    try {
        [xml]$svg = Get-Content -Path $Path -Raw
        $root = $svg.DocumentElement
        $w = $root.GetAttribute('width')
        $h = $root.GetAttribute('height')

        $numW = 0.0
        $numH = 0.0

        if ($w -match '([0-9]+(?:\.[0-9]+)?)') {
            $numW = [double]$Matches[1]
        }
        if ($h -match '([0-9]+(?:\.[0-9]+)?)') {
            $numH = [double]$Matches[1]
        }

        if ($numW -le 0 -or $numH -le 0) {
            $vb = $root.GetAttribute('viewBox')
            if ($vb -match '^\s*[-0-9\.]+\s+[-0-9\.]+\s+([0-9\.]+)\s+([0-9\.]+)\s*$') {
                $numW = [double]$Matches[1]
                $numH = [double]$Matches[2]
            }
        }

        if ($numW -le 0) { $numW = $defaultWidth }
        if ($numH -le 0) { $numH = $defaultHeight }

        return @{ Width = $numW; Height = $numH }
    } catch {
        return @{ Width = $defaultWidth; Height = $defaultHeight }
    }
}

function Write-Utf8File {
    param(
        [string]$Path,
        [string]$Content
    )

    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($Path, $Content, $encoding)
}

if (-not (Test-Path -Path $MarkdownPath)) {
    throw "Markdown introuvable: $MarkdownPath"
}

$baseDir = Split-Path -Parent $OutputDocx
if (-not (Test-Path $baseDir)) {
    New-Item -ItemType Directory -Path $baseDir -Force | Out-Null
}

$workDir = Join-Path $baseDir '_docx_build'
if (Test-Path $workDir) {
    Remove-Item -Recurse -Force $workDir
}

New-Item -ItemType Directory -Path $workDir | Out-Null
New-Item -ItemType Directory -Path (Join-Path $workDir '_rels') | Out-Null
New-Item -ItemType Directory -Path (Join-Path $workDir 'word') | Out-Null
New-Item -ItemType Directory -Path (Join-Path $workDir 'word\_rels') | Out-Null
New-Item -ItemType Directory -Path (Join-Path $workDir 'word\media') | Out-Null
New-Item -ItemType Directory -Path (Join-Path $workDir 'docProps') | Out-Null

$lines = Get-Content -Path $MarkdownPath

$bodyParts = New-Object System.Collections.Generic.List[string]
$images = New-Object System.Collections.Generic.List[hashtable]
$imageRidStart = 3
$docPrId = 1

# Title page spacing and first title
$bodyParts.Add('<w:p><w:pPr><w:spacing w:before="120" w:after="120"/></w:pPr><w:r><w:t xml:space="preserve"></w:t></w:r></w:p>')

# Auto TOC section near top
$bodyParts.Add('<w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr><w:r><w:t xml:space="preserve">Table des matières</w:t></w:r></w:p>')
$bodyParts.Add('<w:p><w:r><w:fldChar w:fldCharType="begin"/></w:r><w:r><w:instrText xml:space="preserve"> TOC \\o "1-3" \\h \\z \\u </w:instrText></w:r><w:r><w:fldChar w:fldCharType="separate"/></w:r><w:r><w:t xml:space="preserve">Clique droit puis "Mettre à jour le champ" dans Word.</w:t></w:r><w:r><w:fldChar w:fldCharType="end"/></w:r></w:p>')
$bodyParts.Add('<w:p><w:r><w:br w:type="page"/></w:r></w:p>')

foreach ($line in $lines) {
    $trim = $line.Trim()

    if ($trim.Length -eq 0) {
        $bodyParts.Add('<w:p><w:r><w:t xml:space="preserve"></w:t></w:r></w:p>')
        continue
    }

    if ($trim -eq '---') {
        $bodyParts.Add('<w:p><w:r><w:br w:type="page"/></w:r></w:p>')
        continue
    }

    if ($trim -match '^!\[([^\]]*)\]\(([^\)]+)\)$') {
        $alt = $Matches[1]
        $imgRelPath = $Matches[2]
        $imgSource = Join-Path (Split-Path -Parent $MarkdownPath) $imgRelPath

        if (Test-Path -Path $imgSource) {
            $imgIndex = $images.Count + 1
            $targetName = "image$imgIndex.svg"
            $targetPath = Join-Path $workDir "word\media\$targetName"
            Copy-Item -Path $imgSource -Destination $targetPath -Force

            $dim = Get-SvgDimensions -Path $imgSource
            $pxW = [double]$dim.Width
            $pxH = [double]$dim.Height

            $maxWidthPx = 620.0
            if ($pxW -gt $maxWidthPx) {
                $ratio = $maxWidthPx / $pxW
                $pxW = $maxWidthPx
                $pxH = [math]::Round($pxH * $ratio, 2)
            }

            $cx = [int64]([math]::Round($pxW * 9525))
            $cy = [int64]([math]::Round($pxH * 9525))

            $rid = "rId" + ($imageRidStart + $images.Count)
            $img = @{
                Rid = $rid
                Target = "media/$targetName"
                Alt = $alt
                Cx = $cx
                Cy = $cy
                DocPrId = $docPrId
                Name = "Image $imgIndex"
            }
            $images.Add($img)
            $docPrId++

            if ($alt) {
                $bodyParts.Add((New-ParagraphXml -Text $alt -StyleId 'Caption'))
            }

            $drawing = @"
<w:p>
  <w:r>
    <w:drawing>
      <wp:inline distT="0" distB="0" distL="0" distR="0" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">
        <wp:extent cx="$cx" cy="$cy"/>
        <wp:effectExtent l="0" t="0" r="0" b="0"/>
        <wp:docPr id="$($img.DocPrId)" name="$($img.Name)" descr="$(Escape-XmlText $alt)"/>
        <wp:cNvGraphicFramePr>
          <a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/>
        </wp:cNvGraphicFramePr>
        <a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
          <a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
            <pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
              <pic:nvPicPr>
                <pic:cNvPr id="0" name="$($img.Name)"/>
                <pic:cNvPicPr/>
              </pic:nvPicPr>
              <pic:blipFill>
                <a:blip r:embed="$($img.Rid)"/>
                <a:stretch><a:fillRect/></a:stretch>
              </pic:blipFill>
              <pic:spPr>
                <a:xfrm>
                  <a:off x="0" y="0"/>
                  <a:ext cx="$cx" cy="$cy"/>
                </a:xfrm>
                <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
              </pic:spPr>
            </pic:pic>
          </a:graphicData>
        </a:graphic>
      </wp:inline>
    </w:drawing>
  </w:r>
</w:p>
"@
            $bodyParts.Add($drawing.Trim())
        } else {
            $bodyParts.Add((New-ParagraphXml -Text "[Image introuvable] $imgRelPath" -StyleId 'Normal'))
        }

        continue
    }

    if ($trim -match '^####\s+(.+)$') {
        $bodyParts.Add((New-ParagraphXml -Text $Matches[1] -StyleId 'Heading3'))
        continue
    }

    if ($trim -match '^###\s+(.+)$') {
        $bodyParts.Add((New-ParagraphXml -Text $Matches[1] -StyleId 'Heading2'))
        continue
    }

    if ($trim -match '^##\s+(.+)$') {
        $bodyParts.Add((New-ParagraphXml -Text $Matches[1] -StyleId 'Heading1'))
        continue
    }

    if ($trim -match '^#\s+(.+)$') {
        $bodyParts.Add((New-ParagraphXml -Text $Matches[1] -StyleId 'Title'))
        continue
    }

    if ($trim -match '^-\s+(.+)$') {
        $bodyParts.Add((New-ParagraphXml -Text ("• " + $Matches[1]) -StyleId 'Normal'))
        continue
    }

    $bodyParts.Add((New-ParagraphXml -Text $trim -StyleId 'Normal'))
}

$bodyXml = $bodyParts -join "`n"

$documentXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" mc:Ignorable="w14 wp14">
  <w:body>
$bodyXml
    <w:sectPr>
      <w:pgSz w:w="11906" w:h="16838"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/>
      <w:cols w:space="708"/>
      <w:docGrid w:linePitch="360"/>
    </w:sectPr>
  </w:body>
</w:document>
"@

$stylesXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
        <w:sz w:val="22"/>
      </w:rPr>
    </w:rPrDefault>
    <w:pPrDefault>
      <w:pPr>
        <w:spacing w:after="160"/>
      </w:pPr>
    </w:pPrDefault>
  </w:docDefaults>

  <w:style w:type="paragraph" w:default="1" w:styleId="Normal">
    <w:name w:val="Normal"/>
    <w:qFormat/>
  </w:style>

  <w:style w:type="paragraph" w:styleId="Title">
    <w:name w:val="Title"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:pPr>
      <w:spacing w:before="120" w:after="240"/>
    </w:pPr>
    <w:rPr>
      <w:b/>
      <w:sz w:val="36"/>
    </w:rPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="Heading1">
    <w:name w:val="heading 1"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:pPr>
      <w:spacing w:before="280" w:after="120"/>
    </w:pPr>
    <w:rPr>
      <w:b/>
      <w:sz w:val="30"/>
    </w:rPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="Heading2">
    <w:name w:val="heading 2"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:pPr>
      <w:spacing w:before="220" w:after="100"/>
    </w:pPr>
    <w:rPr>
      <w:b/>
      <w:sz w:val="26"/>
    </w:rPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="Heading3">
    <w:name w:val="heading 3"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:pPr>
      <w:spacing w:before="180" w:after="80"/>
    </w:pPr>
    <w:rPr>
      <w:b/>
      <w:sz w:val="24"/>
    </w:rPr>
  </w:style>

  <w:style w:type="paragraph" w:styleId="Caption">
    <w:name w:val="Caption"/>
    <w:basedOn w:val="Normal"/>
    <w:pPr>
      <w:jc w:val="left"/>
      <w:spacing w:before="120" w:after="60"/>
    </w:pPr>
    <w:rPr>
      <w:i/>
      <w:sz w:val="20"/>
    </w:rPr>
  </w:style>
</w:styles>
"@

$settingsXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:updateFields w:val="true"/>
</w:settings>
"@

$contentTypes = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Default Extension="svg" ContentType="image/svg+xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
"@

$rootRels = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
"@

$docRelsList = New-Object System.Collections.Generic.List[string]
$docRelsList.Add('<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>')
$docRelsList.Add('<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>')
foreach ($img in $images) {
    $docRelsList.Add("<Relationship Id=`"$($img.Rid)`" Type=`"http://schemas.openxmlformats.org/officeDocument/2006/relationships/image`" Target=`"$($img.Target)`"/>")
}
$docRelsXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
$($docRelsList -join "`n")
</Relationships>
"@

$now = (Get-Date).ToUniversalTime().ToString('s') + 'Z'
$coreXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>Mémoire FAL-PMS Licence</dc:title>
  <dc:subject>Développement d'une plateforme de gestion de projets collaboratifs</dc:subject>
  <dc:creator>Étudiant</dc:creator>
  <cp:keywords>FAL-PMS;MERISE;Laravel;Gestion de projet</cp:keywords>
  <dc:description>Mémoire de fin de cycle licence professionnelle</dc:description>
  <cp:lastModifiedBy>Codex</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">$now</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">$now</dcterms:modified>
</cp:coreProperties>
"@

$appXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Microsoft Office Word</Application>
  <DocSecurity>0</DocSecurity>
  <ScaleCrop>false</ScaleCrop>
  <SharedDoc>false</SharedDoc>
  <HyperlinksChanged>false</HyperlinksChanged>
  <AppVersion>16.0000</AppVersion>
</Properties>
"@

Write-Utf8File -Path (Join-Path $workDir '[Content_Types].xml') -Content $contentTypes
Write-Utf8File -Path (Join-Path $workDir '_rels\.rels') -Content $rootRels
Write-Utf8File -Path (Join-Path $workDir 'word\document.xml') -Content $documentXml
Write-Utf8File -Path (Join-Path $workDir 'word\styles.xml') -Content $stylesXml
Write-Utf8File -Path (Join-Path $workDir 'word\settings.xml') -Content $settingsXml
Write-Utf8File -Path (Join-Path $workDir 'word\_rels\document.xml.rels') -Content $docRelsXml
Write-Utf8File -Path (Join-Path $workDir 'docProps\core.xml') -Content $coreXml
Write-Utf8File -Path (Join-Path $workDir 'docProps\app.xml') -Content $appXml

$zipPath = [System.IO.Path]::ChangeExtension($OutputDocx, '.zip')
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
if (Test-Path $OutputDocx) { Remove-Item $OutputDocx -Force }

Compress-Archive -Path (Join-Path $workDir '*') -DestinationPath $zipPath -Force
Move-Item -Path $zipPath -Destination $OutputDocx -Force

# Quick integrity test
try {
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zip = [System.IO.Compression.ZipFile]::OpenRead((Resolve-Path $OutputDocx))
    $entries = $zip.Entries.Count
    $zip.Dispose()
    Write-Output "DOCX_CREATED: $OutputDocx"
    Write-Output "ZIP_ENTRIES: $entries"
    Write-Output "IMAGES_EMBEDDED: $($images.Count)"
} catch {
    Write-Output "DOCX_CREATED_BUT_VALIDATION_FAILED: $($_.Exception.Message)"
}

# Optional cleanup
if (Test-Path $workDir) {
    Remove-Item -Recurse -Force $workDir
}

