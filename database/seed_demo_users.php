<?php
declare(strict_types=1);
/**
 * Script CLI para crear las cuentas de prueba de Cocina y Empleado.
 *
 * Comprueba cada correo antes de insertar y genera hashes bcrypt para que
 * pueda ejecutarse varias veces sin duplicar usuarios.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();

$usuarios = [
    [
        'nombre' => 'Usuario Cocina',
        'email' => 'cocina@intecap.edu.gt',
        'password' => '12345678',
        'rol_id' => 2,
    ],
    [
        'nombre' => 'Empleado de Prueba',
        'email' => 'empleado@intecap.edu.gt',
        'password' => '12345678',
        'rol_id' => 3,
    ],
];

$buscar = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
$insertar = $pdo->prepare(
    'INSERT INTO usuarios (nombre, email, password, rol_id, activo, nit_facturacion)
     VALUES (:nombre, :email, :password, :rol_id, 1, :nit)'
);

foreach ($usuarios as $usuario) {
    $buscar->execute(['email' => $usuario['email']]);

    if ($buscar->fetch()) {
        echo "Ya existe: {$usuario['email']}\n";
        continue;
    }

    $insertar->execute([
        'nombre' => $usuario['nombre'],
        'email' => $usuario['email'],
        'password' => password_hash($usuario['password'], PASSWORD_BCRYPT),
        'rol_id' => $usuario['rol_id'],
        'nit' => 'C/F',
    ]);

    echo "Creado: {$usuario['email']} ({$usuario['password']})\n";
}