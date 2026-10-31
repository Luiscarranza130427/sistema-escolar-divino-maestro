<?php
declare(strict_types=1);

namespace App\Soporte;

use App\Http\HttpException;

/**
 * Fotos de perfil: se reciben como data URL (JPEG, PNG o WebP), se recortan al centro,
 * se reducen a 256×256 y se vuelven a codificar en WebP. Al recodificar se descartan los
 * metadatos (ubicación, cámara) y cualquier contenido que no sea la imagen.
 */
final class FotosPerfil
{
    public const URL = '/uploads/perfiles/';
    private const TAMANO = 256;
    private const MAXIMO_BYTES = 3 * 1024 * 1024;
    private const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private string $carpeta)
    {
    }

    /** Guarda la imagen y devuelve el nombre del archivo creado. */
    public function guardar(string $dataUrl): string
    {
        if (preg_match('#^data:(image/[a-z]+);base64,(.+)$#s', $dataUrl, $partes) !== 1 || !in_array($partes[1], self::TIPOS, true)) {
            throw HttpException::regla('Sube una imagen JPG, PNG o WebP.', 'imagen');
        }
        $bytes = base64_decode($partes[2], true);
        if ($bytes === false || $bytes === '') {
            throw HttpException::regla('La imagen está dañada.', 'imagen');
        }
        if (strlen($bytes) > self::MAXIMO_BYTES) {
            throw HttpException::regla('La imagen pesa más de 3 MB.', 'imagen');
        }
        $info = @getimagesizefromstring($bytes);
        if ($info === false || !in_array($info['mime'], self::TIPOS, true)) {
            throw HttpException::regla('El archivo no es una imagen válida.', 'imagen');
        }
        $origen = @imagecreatefromstring($bytes);
        if ($origen === false) {
            throw HttpException::regla('No se pudo leer la imagen.', 'imagen');
        }

        $lado = min(imagesx($origen), imagesy($origen));
        $destino = imagecreatetruecolor(self::TAMANO, self::TAMANO);
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        imagecopyresampled(
            $destino, $origen, 0, 0,
            intdiv(imagesx($origen) - $lado, 2), intdiv(imagesy($origen) - $lado, 2),
            self::TAMANO, self::TAMANO, $lado, $lado
        );

        if (!is_dir($this->carpeta) && !mkdir($this->carpeta, 0755, true) && !is_dir($this->carpeta)) {
            throw new \RuntimeException('No se pudo crear la carpeta de fotos de perfil.');
        }
        $nombre = bin2hex(random_bytes(16)) . '.webp';
        if (!imagewebp($destino, $this->carpeta . '/' . $nombre, 85)) {
            throw new \RuntimeException('No se pudo guardar la foto de perfil.');
        }
        return $nombre;
    }

    public function eliminar(?string $nombre): void
    {
        // El nombre lo genera guardar(); se valida el formato para no borrar nada fuera de la carpeta.
        if ($nombre !== null && preg_match('/^[a-f0-9]{32}\.webp$/', $nombre) === 1 && is_file($this->carpeta . '/' . $nombre)) {
            unlink($this->carpeta . '/' . $nombre);
        }
    }
}
