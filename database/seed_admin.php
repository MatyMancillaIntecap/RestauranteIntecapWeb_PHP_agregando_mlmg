<?php
declare(strict_types=1);
/**
 * Script CLI para crear el administrador inicial.
 *
 * Es idempotente: comprueba el correo antes de insertar y almacena la clave
 * usando bcrypt. Ejecutar desde la raiz con `php database/seed_admin.php`.
 */

// Script de consola para crear el usuario Administrador inicial con contraseña hasheada correctamente.
// Ejecutar una sola vez desde XAMPP con: php database/seed_admin.php

// Cargar config primero para definir las constantes DB_* y arrancar la sesión
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();

$nombre = 'Administrador General';
$email = 'admin@intecap.edu.gt';
$passwordPlano = '12345678';
$rolId = 1; // Administrador

$hash = password_hash($passwordPlano, PASSWORD_BCRYPT);

$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
$stmt->execute(['email' => $email]);

if ($stmt->fetch()) {
    echo "El usuario administrador ya existe ({$email}).\n";
    exit;
}

$insert = $pdo->prepare(
    'INSERT INTO usuarios (nombre, email, password, rol_id, activo, nit_facturacion)
     VALUES (:nombre, :email, :password, :rol_id, 1, :nit)'
);

$insert->execute([
    'nombre' => $nombre,
    'email' => $email,
    'password' => $hash,
    'rol_id' => $rolId,
    'nit' => 'C/F',
]);

echo "Usuario administrador creado correctamente.\n";
echo "Correo: {$email}\n";
echo "Contraseña: {$passwordPlano}\n";
