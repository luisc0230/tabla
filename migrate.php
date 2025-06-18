<?php
declare(strict_types=1);

// migrate.php — crea la tabla consumos si no existe

// Lee las credenciales que Railway ya inyecta
$host = getenv('MYSQL_HOST');
$port = getenv('MYSQL_PORT') ?: 3306;
$db   = getenv('MYSQL_DATABASE');
$user = getenv('MYSQL_USER');
$pass = getenv('MYSQL_PASSWORD');

// Conecta vía PDO
$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// DDL para tu tabla
$sql = <<<SQL
CREATE TABLE IF NOT EXISTS consumos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  vale INT NOT NULL,
  codigo VARCHAR(255),
  descripcion TEXT NOT NULL,
  operador VARCHAR(255) NOT NULL,
  placa VARCHAR(255) NOT NULL,
  propietario VARCHAR(255) NOT NULL,
  razon_social VARCHAR(255) NOT NULL,
  tipo_combustible VARCHAR(255) NOT NULL,
  centro_costos VARCHAR(255) NOT NULL,
  linea VARCHAR(255) NOT NULL,
  unidad VARCHAR(50) NOT NULL,
  cantidad DECIMAL(10,2) NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  sub_total DECIMAL(10,2) NOT NULL,
  actual DECIMAL(10,2) NOT NULL,
  anterior DECIMAL(10,2) NOT NULL,
  kms_kilometraje DECIMAL(10,2) NOT NULL,
  ratio_obtenido DECIMAL(10,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

// Ejecuta la migración
$pdo->exec($sql);
echo "✅ Tabla consumos creada o ya existente.\n";
