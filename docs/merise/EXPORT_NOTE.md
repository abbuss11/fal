# Export SVG -> PNG

## Option 1 - Inkscape (recommandé)

```powershell
inkscape docs\merise\mcd.svg --export-type=png --export-filename=docs\merise\mcd.png
```

Même principe pour chaque fichier SVG.

## Option 2 - Batch PowerShell (si Inkscape installé)

```powershell
Get-ChildItem docs\merise\*.svg | ForEach-Object {
  $png = [System.IO.Path]::ChangeExtension($_.FullName, '.png')
  inkscape $_.FullName --export-type=png --export-filename=$png
}
```

## Option 3 - Outils GUI

- ouvrir le SVG dans draw.io / Figma / navigateur compatible
- exporter en PNG selon la résolution voulue
