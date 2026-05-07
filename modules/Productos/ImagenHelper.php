<?php
// Utilidad para procesar y guardar imagenes de productos.
// Valida MIME, corrige orientacion EXIF, redimensiona y guarda como JPG.

class ImagenHelper {

    private const MAX_BYTES  = 8 * 1024 * 1024; // 8 MB
    private const MAX_PX     = 1200;
    private const JPEG_QUALITY = 82;
    private const MIMES_OK   = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Procesa una imagen subida y la guarda en la carpeta indicada.
     *
     * @param string $rutaTmp          Ruta temporal del archivo subido ($_FILES[...]['tmp_name'])
     * @param string $rutaDestinoCarpeta Ruta de la carpeta destino (con barra final)
     * @return array ['ok' => bool, 'nombreArchivo' => string] o ['ok' => false, 'error' => string]
     */
    public static function procesarYGuardar(string $rutaTmp, string $rutaDestinoCarpeta): array {
        // Validar tamaño antes de leer el archivo completo
        $bytes = filesize($rutaTmp);
        if ($bytes === false || $bytes > self::MAX_BYTES) {
            return ['ok' => false, 'error' => "Imagen demasiado grande (max 8 MB)"];
        }

        // Validar MIME real con finfo (no confiar en extension ni Content-Type del navegador)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $rutaTmp);
        finfo_close($finfo);

        if (!in_array($mime, self::MIMES_OK, true)) {
            return ['ok' => false, 'error' => "Tipo de archivo no permitido: $mime"];
        }

        // Cargar imagen segun MIME
        $src = null;
        try {
            switch ($mime) {
                case 'image/jpeg':
                    $src = @imagecreatefromjpeg($rutaTmp);
                    break;
                case 'image/png':
                    $src = @imagecreatefrompng($rutaTmp);
                    break;
                case 'image/webp':
                    $src = @imagecreatefromwebp($rutaTmp);
                    break;
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => "Error al leer imagen: " . $e->getMessage()];
        }

        if (!$src) {
            return ['ok' => false, 'error' => "No se pudo decodificar la imagen"];
        }

        // Corregir orientacion EXIF (fotos de celular rotadas)
        // Solo aplica a JPEG; en PNG/WebP no existe EXIF de orientacion
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($rutaTmp);
            $orientacion = $exif['Orientation'] ?? 1;
            switch ($orientacion) {
                case 3:
                    $src = imagerotate($src, 180, 0);
                    break;
                case 6:
                    $src = imagerotate($src, -90, 0);
                    break;
                case 8:
                    $src = imagerotate($src, 90, 0);
                    break;
            }
        }

        // Redimensionar si el lado mayor supera MAX_PX
        $w = imagesx($src);
        $h = imagesy($src);

        if ($w > self::MAX_PX || $h > self::MAX_PX) {
            if ($w >= $h) {
                $nw = self::MAX_PX;
                $nh = (int)round($h * self::MAX_PX / $w);
            } else {
                $nh = self::MAX_PX;
                $nw = (int)round($w * self::MAX_PX / $h);
            }
            $dst = imagecreatetruecolor($nw, $nh);

            // Fondo blanco (necesario para PNG con transparencia al convertir a JPG)
            $blanco = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $blanco);

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        } elseif ($mime === 'image/png') {
            // PNG sin redimension: igual aplica fondo blanco antes de guardar como JPG
            $dst = imagecreatetruecolor($w, $h);
            $blanco = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $blanco);
            imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        // Guardar como JPG con nombre aleatorio seguro
        $nombreArchivo = bin2hex(random_bytes(16)) . '.jpg';
        $rutaFinal     = rtrim($rutaDestinoCarpeta, '/') . '/' . $nombreArchivo;

        $ok = imagejpeg($src, $rutaFinal, self::JPEG_QUALITY);
        imagedestroy($src);

        if (!$ok) {
            return ['ok' => false, 'error' => "Error al escribir el archivo en disco"];
        }

        return ['ok' => true, 'nombreArchivo' => $nombreArchivo];
    }
}
