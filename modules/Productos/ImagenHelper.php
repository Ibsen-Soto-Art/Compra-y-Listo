<?php
// Utilidad para procesar y guardar imagenes de productos.
// Si el archivo llega como JPEG ya comprimido por el cliente, solo lo mueve (rapido).
// Si llega como PNG/WebP o mayor a MAX_PX, aplica GD (lento pero necesario).

class ImagenHelper {

    private const MAX_BYTES    = 8 * 1024 * 1024; // 8 MB — limite de rechazo
    private const CLIENTE_MAX  = 2_000_000;        // 2 MB — umbral "ya viene comprimido"
    private const MAX_PX       = 1200;
    private const JPEG_QUALITY = 82;
    private const MIMES_OK     = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Procesa una imagen subida y la guarda en la carpeta indicada.
     * Retorna ['ok' => true, 'nombreArchivo' => string] o ['ok' => false, 'error' => string]
     */
    public static function procesarYGuardar(string $rutaTmp, string $rutaDestinoCarpeta): array {
        $bytes = filesize($rutaTmp);
        if ($bytes === false || $bytes > self::MAX_BYTES) {
            return ['ok' => false, 'error' => "Imagen demasiado grande (max 8 MB)"];
        }

        // Validar MIME real
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $rutaTmp);
        finfo_close($finfo);

        if (!in_array($mime, self::MIMES_OK, true)) {
            return ['ok' => false, 'error' => "Tipo de archivo no permitido: $mime"];
        }

        $nombreArchivo = bin2hex(random_bytes(16)) . '.jpg';
        $rutaFinal     = rtrim($rutaDestinoCarpeta, '/') . '/' . $nombreArchivo;

        // Camino rapido: el cliente ya envio un JPEG pequeño — solo moverlo
        if ($mime === 'image/jpeg' && $bytes <= self::CLIENTE_MAX) {
            if (move_uploaded_file($rutaTmp, $rutaFinal)) {
                return ['ok' => true, 'nombreArchivo' => $nombreArchivo];
            }
            return ['ok' => false, 'error' => "Error al mover el archivo"];
        }

        // Camino GD: PNG, WebP o JPEG grande que necesita conversion/resize
        return self::procesarConGD($rutaTmp, $rutaFinal, $mime, $nombreArchivo);
    }

    private static function procesarConGD(string $rutaTmp, string $rutaFinal, string $mime, string $nombreArchivo): array {
        $src = null;
        try {
            switch ($mime) {
                case 'image/jpeg': $src = @imagecreatefromjpeg($rutaTmp); break;
                case 'image/png':  $src = @imagecreatefrompng($rutaTmp);  break;
                case 'image/webp': $src = @imagecreatefromwebp($rutaTmp); break;
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => "Error al leer imagen: " . $e->getMessage()];
        }

        if (!$src) {
            return ['ok' => false, 'error' => "No se pudo decodificar la imagen"];
        }

        // Corregir orientacion EXIF solo en JPEG
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($rutaTmp);
            switch ($exif['Orientation'] ?? 1) {
                case 3: $src = imagerotate($src, 180, 0); break;
                case 6: $src = imagerotate($src, -90, 0); break;
                case 8: $src = imagerotate($src, 90, 0);  break;
            }
        }

        // Redimensionar si supera MAX_PX
        $w = imagesx($src);
        $h = imagesy($src);

        if ($w > self::MAX_PX || $h > self::MAX_PX) {
            if ($w >= $h) { $nw = self::MAX_PX; $nh = (int)round($h * self::MAX_PX / $w); }
            else           { $nh = self::MAX_PX; $nw = (int)round($w * self::MAX_PX / $h); }
            $dst    = imagecreatetruecolor($nw, $nh);
            $blanco = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $blanco);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        } elseif ($mime === 'image/png') {
            // Aplanar transparencia PNG sobre fondo blanco
            $dst    = imagecreatetruecolor($w, $h);
            $blanco = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $blanco);
            imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        $ok = imagejpeg($src, $rutaFinal, self::JPEG_QUALITY);
        imagedestroy($src);

        return $ok
            ? ['ok' => true, 'nombreArchivo' => $nombreArchivo]
            : ['ok' => false, 'error' => "Error al escribir el archivo en disco"];
    }
}
