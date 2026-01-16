<?php
/**
 * Cerbero-PHP v2.0 - Bunker Edition
 * Protegido contra VoidLink, WebShells y Symlink Attacks
 */

// --- CONFIGURACIÓN BLINDADA ---
// Guardamos los archivos FUERA del /html público.
// Aunque un hacker suba un virus, no hay URL para ejecutarlo.
$config = [
    'storageDir'   => '/var/www/cerbero_boveda', 
    'password'     => '', // OPCIONAL: Pon tu contraseña aquí
    'enableDelete' => true,                  
];

// --- FUNCIONES DE SEGURIDAD ---

// 1. Saneamiento estricto de nombres (Anti-VoidLink)
function sanitizeName($filename) {
    // Elimina todo lo que no sea letra, numero, punto, guion o guion bajo
    $clean = preg_replace('/[^a-zA-Z0-9\.\-\_]/', '', basename($filename));
    // Evita nombres vacíos o demasiado largos
    if (empty($clean) || strlen($clean) > 250) return 'archivo_renombrado_' . time() . '.dat';
    return $clean;
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

// 2. Detector de Malware por Magic Bytes (No confía en la extensión)
function isMalware($tmpPath) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realType = $finfo->file($tmpPath);
    
    // Lista negra de ejecutables
    $forbidden = [
        'application/x-httpd-php', // PHP Script
        'application/x-php',
        'text/x-php',
        'application/x-executable', // Linux Binary (ELF)
        'application/x-dosexec',    // Windows Exe/Dll
        'application/x-sh'          // Bash Script
    ];
    
    return in_array($realType, $forbidden);
}

// --- ROUTER ---
$action = $_GET['action'] ?? 'index';

// SUBIDA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload') {
    $pass = $_POST['password'] ?? '';
    if (!checkPassword($pass, $config['password'])) die("Error 401: Contraseña incorrecta");

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        
        // A. Chequeo de Malware
        if (isMalware($_FILES['file']['tmp_name'])) {
            die(" ALERTA DE SEGURIDAD: Se ha detectado un archivo ejecutable/script. Subida rechazada.");
        }

        // B. Saneamiento de nombre
        $safeName = sanitizeName($_FILES['file']['name']);
        $dest = $config['storageDir'] . DIRECTORY_SEPARATOR . $safeName;

        // C. Movimiento final
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            header("Location: index.php"); exit;
        } else {
            die("Error 500: Fallo de escritura. Verifica permisos en " . $config['storageDir']);
        }
    } else {
        $cod = $_FILES['file']['error'] ?? 4;
        die("Error de subida (Código $cod). ¿Disco lleno o límite PHP excedido?");
    }
}

// BORRADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    if (!$config['enableDelete']) die("Borrado deshabilitado");
    if (!checkPassword($_POST['password'] ?? '', $config['password'])) die("Clave incorrecta");
    
    $safeName = sanitizeName($_POST['path'] ?? '');
    $path = $config['storageDir'] . DIRECTORY_SEPARATOR . $safeName;
    
    if (file_exists($path) && is_file($path)) unlink($path);
    header("Location: index.php"); exit;
}

// DESCARGA (Proxy seguro: El usuario nunca toca el archivo real)
if ($action === 'download') {
    $safeName = sanitizeName($_GET['file'] ?? '');
    $path = $config['storageDir'] . DIRECTORY_SEPARATOR . $safeName;

    if (file_exists($path) && is_file($path)) {
        // Limpiamos buffer para evitar corrupción
        if (ob_get_level()) ob_end_clean();
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$safeName.'"');
        header('Content-Length: ' . filesize($path));
        header('Pragma: public');
        readfile($path); exit;
    } else {
        die("Archivo no encontrado o acceso denegado.");
    }
}

// LISTADO
$files = [];
if (is_dir($config['storageDir'])) {
    foreach (scandir($config['storageDir']) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $config['storageDir'] . '/' . $f;
        $files[] = ['n'=>$f, 's'=>filesize($p), 'h'=>humanSize(filesize($p)), 't'=>filemtime($p)];
    }
    usort($files, function($a, $b) { return $b['t'] - $a['t']; });
} else {
    die("Error Crítico: La carpeta 'cerbero_boveda' no existe o no tiene permisos.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cerbero Bunker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; background: #2c3e50; padding: 20px; color:#ecf0f1; }
        .container { max-width: 800px; margin: auto; background: #34495e; padding: 25px; border-radius: 12px; box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        h1 { color: #e74c3c; border-bottom: 2px solid #7f8c8d; padding-bottom: 15px; margin-top: 0; }
        .upload { background: #2c3e50; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px dashed #e74c3c; }
        table { width: 100%; border-collapse: collapse; margin-top:10px; }
        th { text-align: left; color: #bdc3c7; padding: 10px; border-bottom: 1px solid #7f8c8d; }
        td { padding: 12px; border-bottom: 1px solid #7f8c8d; vertical-align: middle; }
        .btn { padding: 8px 15px; border-radius: 6px; text-decoration: none; border: none; cursor: pointer; color: white; font-weight: bold; font-size: 0.9em; display:inline-block; }
        .btn-up { background: #27ae60; } .btn-up:hover { background: #2ecc71; }
        .btn-dl { background: #3498db; } .btn-dl:hover { background: #2980b9; }
        .btn-del { background: #c0392b; } .btn-del:hover { background: #e74c3c; }
        input { padding: 8px; border-radius: 4px; border: 1px solid #bdc3c7; background: #ecf0f1; color: #333; }
        .meta { font-size: 0.8em; color: #bdc3c7; }
    </style>
</head>
<body>
    <div class="container">
        <h1> Cerbero <span style="font-size:0.6em; color:#bdc3c7; font-weight:normal;">Bunker Edition v2.0</span></h1>
        
        <div class="upload">
            <form method="POST" action="?action=upload" enctype="multipart/form-data">
                <label style="display:block; margin-bottom:10px; font-weight:bold;">Subir Archivo Seguro (Máx 10GB):</label>
                <input type="file" name="file" required style="width:100%; margin-bottom:10px;">
                <div style="display:flex; gap:10px; align-items:center;">
                    <?php if($config['password']): ?>
                        <input type="password" name="password" placeholder="Contraseña de acceso">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-up"> Iniciar Subida</button>
                </div>
            </form>
        </div>

        <table>
            <thead><tr><th>Archivo</th><th style="width:130px; text-align:right;">Acciones</th></tr></thead>
            <tbody>
                <?php foreach($files as $f): ?>
                <tr>
                    <td>
                        <div style="font-weight:bold; color:#ecf0f1;"><?= $f['n'] ?></div>
                        <div class="meta"><?= $f['h'] ?> - <?= date("d/m/Y H:i", $f['t']) ?></div>
                    </td>
                    <td style="text-align:right;">
                        <a href="?action=download&file=<?= urlencode($f['n']) ?>" class="btn btn-dl">▼</a>
                        <?php if($config['enableDelete']): ?>
                        <form method="POST" action="?action=delete" style="display:inline">
                            <input type="hidden" name="path" value="<?= $f['n'] ?>">
                            <?php if($config['password']): ?><input type="password" name="password" placeholder="Clave" style="width:50px; padding:6px;"><?php endif; ?>
                            <button class="btn btn-del" onclick="return confirm('¿Borrar?')">✕</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
