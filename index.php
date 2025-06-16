<?php

function convertToWebP(string $sourcePath, string $originalName, string $outputDir, int $quality = 80): ?string {
    $mime = mime_content_type($sourcePath);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $name = pathinfo($originalName, PATHINFO_FILENAME);
    $webpFile = $outputDir . '/' . $name . '.webp';

    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            break;
        default:
            return null;
    }

    imagewebp($image, $webpFile, $quality);
    imagedestroy($image);

    return $webpFile;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $uploadDir = __DIR__ . '/uploads';
    $convertedFiles = [];

    // Créer un dossier temporaire pour cette session
    $sessionDir = $uploadDir . '/conv_' . uniqid();
    mkdir($sessionDir, 0777, true);

    foreach ($_FILES['images']['tmp_name'] as $index => $tmpPath) {
        $originalName = $_FILES['images']['name'][$index];
        $error = $_FILES['images']['error'][$index];

        if ($error === UPLOAD_ERR_OK) {
            $converted = convertToWebP($tmpPath, $originalName, $sessionDir);
            if ($converted) {
                $convertedFiles[] = $converted;
            }
        }
    }

    if (count($convertedFiles)) {
        $zipPath = $sessionDir . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            foreach ($convertedFiles as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();

            // Téléchargement du zip
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="converted_webp.zip"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);

            // Nettoyage
            foreach ($convertedFiles as $f) unlink($f);
            rmdir($sessionDir);
            unlink($zipPath);
            exit;
        } else {
            $error = "Impossible de créer l’archive ZIP.";
        }
    } else {
        $error = "Aucune image valide à convertir.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Convertisseur multiple JPG/PNG → WebP</title>
</head>
<body>
    <h1>Convertir plusieurs images en WebP</h1>

    <?php if (isset($error)): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="images[]" accept=".jpg,.jpeg,.png" multiple required>
        <button type="submit">Convertir en WebP</button>
    </form>
</body>
</html>
