<?php

function convertToWebP($sourcePath, $quality = 80): ?string
{
    $info = getimagesize($sourcePath);
    $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    $webpPath = sys_get_temp_dir() . '/' . uniqid('image_', true) . '.webp';

    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            // Gérer la transparence
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            break;
        default:
            return null;
    }

    imagewebp($image, $webpPath, $quality);
    imagedestroy($image);

    return $webpPath;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    if ($file['error'] === 0) {
        $tmpPath = $file['tmp_name'];
        $webpPath = convertToWebP($tmpPath);

        if ($webpPath && file_exists($webpPath)) {
            header('Content-Type: image/webp');
            header('Content-Disposition: attachment; filename="converted.webp"');
            readfile($webpPath);
            unlink($webpPath); // nettoyage
            exit;
        } else {
            $error = "Erreur lors de la conversion.";
        }
    } else {
        $error = "Erreur d'upload : " . $file['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Convertisseur JPG/PNG → WebP</title>
</head>
<body>
<h1>Convertir une image en WebP</h1>

<?php if (isset($error)): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="image" accept=".jpg,.jpeg,.png" required>
    <button type="submit">Convertir</button>
</form>
</body>
</html>
