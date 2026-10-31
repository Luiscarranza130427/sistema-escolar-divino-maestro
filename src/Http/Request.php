<?php
declare(strict_types=1);

namespace App\Http;

/** Petición HTTP. Los datos del cuerpo se leen como JSON. */
final class Request
{
    /** @var array<string, mixed>|null */
    private ?array $cuerpo = null;
    /** @var array<string, int> */
    private array $parametrosRuta = [];

    /**
     * @param array<string, mixed> $consulta
     * @param array<string, string> $cabeceras claves en minúsculas
     */
    public function __construct(
        public readonly string $metodo,
        public readonly string $ruta,
        private array $consulta = [],
        private string $cuerpoCrudo = '',
        private array $cabeceras = [],
        public readonly string $ip = ''
    ) {
    }

    public static function desdeGlobales(): self
    {
        $cabeceras = [];
        foreach ($_SERVER as $clave => $valor) {
            if (str_starts_with($clave, 'HTTP_')) {
                $cabeceras[strtolower(str_replace('_', '-', substr($clave, 5)))] = (string) $valor;
            } elseif ($clave === 'CONTENT_TYPE') {
                $cabeceras['content-type'] = (string) $valor;
            }
        }
        // Apache puede ocultar Authorization; se recupera de la variable de reescritura.
        if (!isset($cabeceras['authorization']) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $cabeceras['authorization'] = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            self::rutaDesdeServidor(),
            $_GET,
            (string) file_get_contents('php://input'),
            $cabeceras,
            (string) ($_SERVER['REMOTE_ADDR'] ?? '')
        );
    }

    private static function rutaDesdeServidor(): string
    {
        $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        // chr(92) es la barra invertida que Windows puede dejar en SCRIPT_NAME.
        // El servidor de desarrollo de PHP pone la URL pedida en SCRIPT_NAME; solo se usa si apunta a un .php.
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $base = str_ends_with($script, '.php') ? rtrim(str_replace(chr(92), '/', dirname($script)), '/') : '';
        if ($base !== '' && str_starts_with($ruta, $base . '/')) {
            $ruta = substr($ruta, strlen($base));
        }
        $ruta = (string) preg_replace('#^/index[.]php#', '', $ruta);
        return '/' . trim(rawurldecode($ruta), '/');
    }

    public function cabecera(string $nombre): ?string
    {
        return $this->cabeceras[strtolower($nombre)] ?? null;
    }

    /** Token de la cabecera "Authorization: Bearer <token>". */
    public function tokenBearer(): ?string
    {
        $valor = $this->cabecera('authorization') ?? '';
        return preg_match('/^Bearer\s+(\S+)$/i', $valor, $coincidencia) === 1 ? $coincidencia[1] : null;
    }

    /** @return array<string, mixed> */
    public function consulta(): array
    {
        return $this->consulta;
    }

    /** @return array<string, mixed> */
    public function cuerpo(): array
    {
        if ($this->cuerpo !== null) {
            return $this->cuerpo;
        }
        if (trim($this->cuerpoCrudo) === '') {
            return $this->cuerpo = [];
        }
        $datos = json_decode($this->cuerpoCrudo, true);
        if (!is_array($datos)) {
            throw HttpException::solicitudInvalida('El cuerpo de la petición debe ser un objeto JSON válido.');
        }
        return $this->cuerpo = $datos;
    }

    /** @param array<string, int|string> $parametros */
    public function conParametros(array $parametros): self
    {
        $copia = clone $this;
        $copia->parametrosRuta = array_map('intval', $parametros);
        return $copia;
    }

    public function parametro(string $nombre): int
    {
        return $this->parametrosRuta[$nombre] ?? 0;
    }
}
