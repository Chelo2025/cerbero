<?php
/**
 * Cerbero-PHP v1.2 - Hardened Edition
 * Servidor de archivos seguro para Debian 13 + Apache
 */

// --- CONFIGURACIÓN ---
$config = [
    'rootDir'      => __DIR__ . '/archivos', 
    'password'     => '', // DEJALO VACÍO, EL USUARIO LO CONFIGURA EN EL CÓDIGO SI QUIERE
    'enableDelete' => true,                  
];

// --- SEGURIDAD ---
if (!is_dir($config['rootDir'])) { mkdir($config['rootDir'], 0755, true); }

function securePath($filename, $root) {
    $filename = basename($filename);
    $target = $root . DIRECTORY_SEPARATOR . $filename;
    if (strpos(realpath($root) ?: $root, $root) !== 0) die("Acceso Denegado (Path Traversal)");
    return $target;
}

function checkPassword($input, $real) {
    if (empty($real)) return true;
    if (empty($input)) return false;
    return hash_equals($real, $input);
}

function humanSize($bytes) {
    $si = ['B', 'KB', 'MB', 'GB', 'TB'];
    $exp = $bytes ? floor(log($bytes, 1024)) : 0;
    return sprintf('%.2f %s', $bytes / pow(1024, $exp), $si[$exp]);
}

// --- ROUTER ---
$action = $_GET['action'] ?? 'index';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload') {
    $pass = $_POST['password'] ?? '';
    if (!checkPassword($pass, $config['password'])) die("Error 401: Contraseña incorrecta");

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $dest = securePath($_FILES['file']['name'], $config['rootDir']);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            header("Location: index.php"); exit;
        } else {
            die("Error 500: Fallo de escritura. Verifica permisos y espacio en disco.");
        }
    } else {
        $cod = $_FILES['file']['error'] ?? 4;
        die("Error de subida (Código $cod). Posible causa: Límite de PHP excedido o disco lleno.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    if (!$config['enableDelete']) die("Borrado deshabilitado");
    if (!checkPassword($_POST['password'] ?? '', $config['password'])) die("Clave incorrecta");
    $path = securePath($_POST['path'] ?? '', $config['rootDir']);
    if (file_exists($path)) unlink($path);
    header("Location: index.php"); exit;
}

if ($action === 'download') {
    $path = securePath($_GET['file'] ?? '', $config['rootDir']);
    if (file_exists($path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($path).'"');
        header('Content-Length: ' . filesize($path));
        readfile($path); exit;
    }
}

$files = [];
foreach (scandir($config['rootDir']) as $f) {
    if ($f === '.' || $f === '..') continue;
    $p = $config['rootDir'] . '/' . $f;
    $files[] = ['n'=>$f, 's'=>filesize($p), 'h'=>humanSize(filesize($p)), 't'=>filemtime($p)];
}
usort($files, function($a, $b) { return $b['t'] - $a['t']; });
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cerbero Files</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: sans-serif; background: #f0f2f5; padding: 20px; color:#333; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #d63384; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .upload { background: #fce4ec; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px dashed #d63384; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .btn { padding: 8px 12px; border-radius: 4px; text-decoration: none; border: none; cursor: pointer; color: white; font-weight: bold; }
        .btn-up { background: #d63384; }
        .btn-del { background: #dc3545; }
        input { padding: 5px; margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cerbero <small style="color:#666; font-size:0.6em">PHP Edition</small></h1>
        <div class="upload">
            <form method="POST" action="?action=upload" enctype="multipart/form-data">
                <b>Subir Archivo:</b><br>
                <input type="file" name="file" required><br>
                <?php if($config['password']): ?><input type="password" name="password" placeholder="Contraseña"><?php endif; ?>
                <button type="submit" class="btn btn-up">Subir</button>
            </form>
        </div>
        <table>
            <?php foreach($files as $f): ?>
            <tr>
                <td><b><?= $f['n'] ?></b><br><small><?= $f['h'] ?> - <?= date("d/m H:i", $f['t']) ?></small></td>
                <td style="text-align:right;">
                    <a href="?action=download&file=<?= urlencode($f['n']) ?>" class="btn btn-up">▼</a>
                    <?php if($config['enableDelete']): ?>
                    <form method="POST" action="?action=delete" style="display:inline">
                        <input type="hidden" name="path" value="<?= $f['n'] ?>">
                        <?php if($config['password']): ?><input type="password" name="password" placeholder="Clave" size="5"><?php endif; ?>
                        <button class="btn btn-del">X</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
