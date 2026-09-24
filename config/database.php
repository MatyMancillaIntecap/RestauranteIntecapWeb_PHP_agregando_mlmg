<?php
declare(strict_types=1);
/**
 * Proveedor de la conexion PDO a MySQL.
 *
 * Mantiene una unica conexion por solicitud y centraliza las opciones de PDO
 * para que todas las consultas usen excepciones y resultados asociativos.
 */

// Conexión única a MySQL mediante PDO. La configuración es propia del proyecto PHP y no depende del C#.
class Database
{
    private static ?PDO $instance = null;

    /**
     * Devuelve la conexion compartida a la base de datos.
     *
     * @return PDO Conexion configurada con las variables DB_*.
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: DB_HOST;
            $dbName = getenv('DB_NAME') ?: DB_NAME;
            $user = getenv('DB_USER') ?: DB_USER;
            $pass = getenv('DB_PASS') ?: DB_PASS;
            $charset = getenv('DB_CHARSET') ?: DB_CHARSET;

            $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            self::$instance = new PDO($dsn, $user, $pass, $options);
        }

        return self::$instance;
    }
}
