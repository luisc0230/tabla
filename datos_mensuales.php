<?php
declare(strict_types=1);
// datos_mensuales.php — devuelve JSON con todos los campos en español

// 1) URL completa de Railway (inyectada en prod y puedes exportarla localmente)
$mysqlUrl = getenv('MYSQL_URL');
if (!$mysqlUrl) {
    http_response_code(500);
    echo json_encode(['error' => 'Debe definir la variable de entorno MYSQL_URL']);
    exit;
}

// 2) Parseamos la URL para extraer host, puerto, user, pass y base
$parts = parse_url($mysqlUrl);
$host = $parts['host']   ?? '';
$port = $parts['port']   ?? 3306;
$user = $parts['user']   ?? '';
$pass = $parts['pass']   ?? '';
$db   = isset($parts['path']) ? ltrim($parts['path'], '/') : '';

// 3) Conexión PDO
$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Validar y obtener parámetros
$mes  = isset($_GET['mes'])  ? (int)$_GET['mes']  : date('n');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

// Validar que los valores estén en rangos correctos
if ($mes < 1 || $mes > 12) {
    $mes = date('n');
}
if ($anio < 2000 || $anio > 2100) {
    $anio = date('Y');
}

// Query mejorada con todos los campos disponibles
$sql = "
    SELECT
        operador AS Operador,
        placa AS Vehículo,
        propietario AS Propietario,
        tipo_combustible AS 'Tipo Combustible',
        centro_costos AS 'Centro de Costos',
        linea AS Línea,
        razon_social AS 'Razón Social',
        SUM(cantidad) AS 'Total Combustible',
        SUM(kms_kilometraje) AS 'Total Kilómetros',
        SUM(sub_total) AS 'Total Costo',
        AVG(precio_unitario) AS 'Precio Promedio',
        COUNT(*) AS 'Número de Cargas',
        MIN(fecha) AS 'Primera Carga',
        MAX(fecha) AS 'Última Carga',
        AVG(ratio_obtenido) AS 'Ratio Promedio',
        SUM(actual - anterior) AS 'Diferencia Odómetro',
        unidad AS 'Unidad Medida'
    FROM consumos
    WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio
    GROUP BY 
        operador, 
        placa, 
        propietario, 
        tipo_combustible, 
        centro_costos, 
        linea, 
        razon_social,
        unidad
    ORDER BY operador, placa
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['mes' => $mes, 'anio' => $anio]);
    
    $resultados = $stmt->fetchAll();
    
    // Procesar los resultados para formatear números correctamente
    foreach ($resultados as &$row) {
        // Formatear números a 2 decimales
        $row['Total Combustible'] = round((float)$row['Total Combustible'], 2);
        $row['Total Kilómetros'] = round((float)$row['Total Kilómetros'], 2);
        $row['Total Costo'] = round((float)$row['Total Costo'], 2);
        $row['Precio Promedio'] = round((float)$row['Precio Promedio'], 2);
        $row['Ratio Promedio'] = round((float)$row['Ratio Promedio'], 4);
        $row['Diferencia Odómetro'] = round((float)$row['Diferencia Odómetro'], 2);
        
        // Formatear fechas a formato más legible
        if ($row['Primera Carga']) {
            $row['Primera Carga'] = date('d/m/Y', strtotime($row['Primera Carga']));
        }
        if ($row['Última Carga']) {
            $row['Última Carga'] = date('d/m/Y', strtotime($row['Última Carga']));
        }
    }
    
    // Configurar headers para JSON
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    // Enviar respuesta JSON
    echo json_encode($resultados, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al ejecutar la consulta',
        'mensaje' => 'No se pudieron obtener los datos del mes seleccionado'
    ]);
    error_log('Error en datos_mensuales.php: ' . $e->getMessage());
}
?>
