<?php
declare(strict_types=1);
// index.php — CRUD de Consumos + Dashboard Mensual
$mysqlUrl = getenv('MYSQL_URL');
if (!$mysqlUrl) {
    die("🚨 Debes definir la variable de entorno MYSQL_URL");
}

// 2) Parseamos la URL
$parts = parse_url($mysqlUrl);
$host = $parts['host'] ?? '';
$port = $parts['port'] ?? 3306;
$user = $parts['user'] ?? '';
$pass = $parts['pass'] ?? '';
$db   = isset($parts['path']) ? ltrim($parts['path'], '/') : '';

// 3) Conectamos con PDO
$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// 2. Eliminar
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM consumos WHERE id = ?")
        ->execute([(int)$_GET['delete_id']]);
    header('Location: ' . basename(__FILE__));
    exit;
}
// 3. Insertar / Actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger campos
    $fecha            = $_POST['fecha'];
    $vale             = $_POST['vale'];
    $codigo           = $_POST['codigo'] ?: null;
    $descripcion      = $_POST['descripcion'];
    $operador         = $_POST['operador'];
    $placa            = $_POST['placa'];
    $propietario      = $_POST['propietario'];
    $razon_social     = $_POST['razon_social'];
    $tipo_combustible = $_POST['tipo_combustible'];
    $centro_costos    = $_POST['centro_costos'];
    $linea            = $_POST['linea'];
    $unidad           = $_POST['unidad'];
    $cantidad         = (float)$_POST['cantidad'];
    $precio_unitario  = (float)$_POST['precio_unitario'];
    $actual           = (float)$_POST['actual'];
    $anterior         = (float)$_POST['anterior'];
    // Cálculos
    $sub_total       = round($cantidad * $precio_unitario, 2);
    $kms_kilometraje = round($actual - $anterior, 2);
    if ($unidad === 'KILOMETROS / GALON') {
        $ratio = $cantidad > 0
            ? round($kms_kilometraje / $cantidad, 4)
            : 0;
    } else {
        $ratio = $kms_kilometraje > 0
            ? round($cantidad / $kms_kilometraje, 4)
            : 0;
    }
    // INSERT vs UPDATE
    if (!empty($_POST['update_id'])) {
        $sql = "UPDATE consumos SET
            fecha=:fecha, vale=:vale, codigo=:codigo, descripcion=:descripcion,
            operador=:operador, placa=:placa, propietario=:propietario,
            razon_social=:razon_social, tipo_combustible=:tipo_combustible,
            centro_costos=:centro_costos, linea=:linea, unidad=:unidad,
            cantidad=:cantidad, precio_unitario=:precio_unitario,
            sub_total=:sub_total, actual=:actual, anterior=:anterior,
            kms_kilometraje=:kms_kilometraje, ratio_obtenido=:ratio
          WHERE id=:id";
        $params = [
            'fecha'=>$fecha,'vale'=>$vale,'codigo'=>$codigo,
            'descripcion'=>$descripcion,'operador'=>$operador,
            'placa'=>$placa,'propietario'=>$propietario,
            'razon_social'=>$razon_social,'tipo_combustible'=>$tipo_combustible,
            'centro_costos'=>$centro_costos,'linea'=>$linea,
            'unidad'=>$unidad,'cantidad'=>$cantidad,
            'precio_unitario'=>$precio_unitario,'sub_total'=>$sub_total,
            'actual'=>$actual,'anterior'=>$anterior,
            'kms_kilometraje'=>$kms_kilometraje,'ratio'=>$ratio,
            'id'=> (int)$_POST['update_id']
        ];
    } else {
        $sql = "INSERT INTO consumos (
            fecha,vale,codigo,descripcion,operador,placa,propietario,
            razon_social,tipo_combustible,centro_costos,linea,unidad,
            cantidad,precio_unitario,sub_total,actual,anterior,
            kms_kilometraje,ratio_obtenido
        ) VALUES (
            :fecha,:vale,:codigo,:descripcion,:operador,:placa,:propietario,
            :razon_social,:tipo_combustible,:centro_costos,:linea,:unidad,
            :cantidad,:precio_unitario,:sub_total,:actual,:anterior,
            :kms_kilometraje,:ratio
        )";
        $params = [
            'fecha'=>$fecha,'vale'=>$vale,'codigo'=>$codigo,
            'descripcion'=>$descripcion,'operador'=>$operador,
            'placa'=>$placa,'propietario'=>$propietario,
            'razon_social'=>$razon_social,'tipo_combustible'=>$tipo_combustible,
            'centro_costos'=>$centro_costos,'linea'=>$linea,
            'unidad'=>$unidad,'cantidad'=>$cantidad,
            'precio_unitario'=>$precio_unitario,'sub_total'=>$sub_total,
            'actual'=>$actual,'anterior'=>$anterior,
            'kms_kilometraje'=>$kms_kilometraje,'ratio'=>$ratio
        ];
    }
    $pdo->prepare($sql)->execute($params);
    header('Location: ' . basename(__FILE__));
    exit;
}
// 4. Leer registros para el CRUD
$consumos = [];
try {
    $consumos = $pdo
        ->query("SELECT * FROM consumos ORDER BY fecha DESC, vale ASC")
        ->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la tabla no existe, la creamos
    if (strpos($e->getMessage(), 'consumos') !== false) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `consumos` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `fecha` date NOT NULL,
          `vale` int(11) NOT NULL,
          `codigo` varchar(50) DEFAULT NULL,
          `descripcion` varchar(255) NOT NULL,
          `operador` varchar(100) NOT NULL,
          `placa` varchar(20) NOT NULL,
          `propietario` varchar(100) NOT NULL,
          `razon_social` varchar(150) NOT NULL,
          `tipo_combustible` varchar(50) NOT NULL,
          `centro_costos` varchar(50) NOT NULL,
          `linea` varchar(50) NOT NULL,
          `unidad` varchar(50) NOT NULL,
          `cantidad` decimal(10,2) NOT NULL,
          `precio_unitario` decimal(10,2) NOT NULL,
          `sub_total` decimal(12,2) NOT NULL,
          `actual` int(11) NOT NULL,
          `anterior` int(11) NOT NULL,
          `kms_kilometraje` int(11) NOT NULL,
          `ratio_obtenido` decimal(8,4) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `fecha` (`fecha`),
          KEY `operador` (`operador`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        $consumos = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Consumos & Dashboard Mensual</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- GSAP -->
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- jQuery + jQuery UI -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link  rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css"/>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  <!-- PivotTable.js -->
  <link  rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pivottable@2.23.0/dist/pivot.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/pivottable@2.23.0/dist/pivot.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/pivottable@2.23.0/dist/pivot.es.min.js"></script>
  <!-- Chart.js + renderers -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/pivottable@2.23.0/dist/pivot.chartjs_renderers.min.js"></script>
  <style>
    /* Estilos personalizados para la tabla pivot */
    .pvtUi { margin: 20px 0; }
    .pvtAxisContainer { background: #f3f4f6; }
    .pvtTable { font-size: 14px; }
    .pvtTable thead th { background: #3b82f6; color: white; }
    .pvtTable tbody tr:hover { background: #f3f4f6; }
    .pvtVal { font-weight: bold; }
    /* Animación para gráficos */
    .chart-container {
      animation: fadeIn 0.5s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    /* Estilos para contenedor de gráficos */
    #dynamicCharts {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
      gap: 1.5rem;
      margin-top: 2rem;
    }
    @media (max-width: 768px) {
      #dynamicCharts {
        grid-template-columns: 1fr;
      }
    }
    /* Ajustes para canvas de Chart.js */
    canvas {
      max-width: 100% !important;
      height: auto !important;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased p-6">
  <div class="max-w-7xl mx-auto space-y-12">
    <!-- === 1) CRUD de Consumos === -->
    <div class="bg-white p-6 rounded-lg shadow">
      <!-- Header + Botón Agregar -->
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-4xl font-extrabold">Consumos de Combustible</h1>
        <button id="btnAdd" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
          + Agregar
        </button>
      </div>
      <!-- Modal Add/Edit -->
      <div id="modal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden flex items-center justify-center p-4">
        <form id="formConsumo" method="POST"
              class="bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 relative">
          <button type="button" id="closeModal"
                  class="absolute top-3 right-3 text-gray-500 hover:text-gray-800">✕</button>
          <h2 id="modalTitle" class="text-2xl font-bold mb-4">Nuevo Consumo</h2>
          <input type="hidden" name="update_id" id="update_id">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php
            $campos = [
              'fecha'            => ['type'=>'date','label'=>'Fecha'],
              'vale'             => ['type'=>'number','label'=>'Vale'],
              'codigo'           => ['type'=>'text','label'=>'Código'],
              'descripcion'      => ['type'=>'text','label'=>'Descripción'],
              'operador'         => ['type'=>'text','label'=>'Operador'],
              'placa'            => ['type'=>'text','label'=>'Placa'],
              'propietario'      => ['type'=>'text','label'=>'Propietario'],
              'razon_social'     => ['type'=>'text','label'=>'Razón Social'],
              'tipo_combustible' => ['type'=>'text','label'=>'Tipo Combustible'],
              'centro_costos'    => ['type'=>'text','label'=>'Centro Costos'],
              'linea'            => ['type'=>'text','label'=>'Línea'],
              'unidad'           => ['type'=>'select','label'=>'Unidad'],
              'cantidad'         => ['type'=>'number','step'=>'0.01','label'=>'Cantidad'],
              'precio_unitario'  => ['type'=>'number','step'=>'0.01','label'=>'Precio Unitario'],
              'sub_total'        => ['type'=>'text','label'=>'Sub Total','readonly'=>true],
              'actual'           => ['type'=>'number','step'=>'0.1','label'=>'Actual'],
              'anterior'         => ['type'=>'number','step'=>'0.1','label'=>'Anterior'],
              'kms_kilometraje'  => ['type'=>'text','label'=>'Kms / Kilometraje','readonly'=>true],
              'ratio_obtenido'   => ['type'=>'text','label'=>'Ratio Obtenido','readonly'=>true],
            ];
            foreach ($campos as $name => $cfg): ?>
              <label class="block">
                <span class="font-medium"><?= $cfg['label'] ?></span>
                <?php if ($cfg['type']==='select'): ?>
                  <select name="<?= $name ?>" id="<?= $name ?>" required
                          class="mt-1 w-full px-3 py-2 border rounded">
                    <option value="KILOMETROS / GALON">KILOMETROS / GALON</option>
                    <option value="GALONES / HORA">GALONES / HORA</option>
                  </select>
                <?php else: ?>
                  <input name="<?= $name ?>" id="<?= $name ?>"
                         type="<?= $cfg['type'] ?>"
                         <?= isset($cfg['step'])?"step=\"{$cfg['step']}\"":'' ?>
                         <?= isset($cfg['readonly'])?'readonly':'required' ?>
                         class="mt-1 w-full px-3 py-2 border rounded <?= isset($cfg['readonly'])?'bg-gray-100':'' ?>"/>
                <?php endif; ?>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="mt-6 flex justify-end space-x-2">
            <button type="button" id="cancelBtn"
                    class="px-4 py-2 rounded border hover:bg-gray-100">Cancelar</button>
            <button type="submit"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
              Guardar
            </button>
          </div>
        </form>
      </div>
      <!-- Filtro global -->
      <div class="mb-4">
        <input id="searchInput" type="text" placeholder="🔍 Buscar..."
               class="w-full md:w-1/2 px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400"/>
      </div>
      <!-- Tabla de consumos -->
      <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table id="tabla" class="min-w-full table-auto">
          <thead class="bg-blue-600 text-white sticky top-0 z-10">
            <tr>
              <?php foreach (array_keys($consumos[0] ?? []) as $col): ?>
                <th class="px-3 py-2 text-xs uppercase">
                  <?= str_replace('_',' ', ucfirst($col)) ?>
                </th>
              <?php endforeach; ?>
              <th class="px-3 py-2 text-xs">Ver</th>
              <th class="px-3 py-2 text-xs">Editar</th>
              <th class="px-3 py-2 text-xs">Eliminar</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php if (empty($consumos)): ?>
              <tr>
                <td colspan="100%" class="text-center py-4 text-gray-500">
                  No hay registros de consumos aún. Haga clic en "Agregar" para crear el primero.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($consumos as $fila): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                  <?php foreach ($fila as $val): ?>
                    <td class="px-2 py-1 text-xs whitespace-nowrap">
                      <?= htmlspecialchars($val ?? '') ?>
                    </td>
                  <?php endforeach; ?>
                  <td class="px-2 py-1 text-center">
                    <button class="viewBtn text-blue-600"
                            data-json='<?= json_encode($fila) ?>'>🔍</button>
                  </td>
                  <td class="px-2 py-1 text-center">
                    <button class="editBtn text-green-600"
                            data-json='<?= json_encode($fila) ?>'>✏️</button>
                  </td>
                  <td class="px-2 py-1 text-center">
                    <button class="delBtn text-red-600"
                            data-id='<?= $fila['id'] ?>'>🗑️</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <!-- Scripts CRUD -->
      <script>
        // Modal Toggle & Anim
        const modal      = document.getElementById('modal');
        const btnAdd     = document.getElementById('btnAdd');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn  = document.getElementById('cancelBtn');
        const modalTitle = document.getElementById('modalTitle');
        const updateId   = document.getElementById('update_id');
        const form       = document.getElementById('formConsumo');
        btnAdd.onclick = () => {
          form.reset();
          updateId.value = '';
          modalTitle.textContent = 'Nuevo Consumo';
          modal.classList.remove('hidden');
          gsap.from("#modal form", { scale: 0.8, opacity: 0, duration: 0.3 });
        };
        closeModal.onclick = cancelBtn.onclick = () => modal.classList.add('hidden');
        // Cálculos en tiempo real
        function updateCalculations() {
          const c = parseFloat(document.getElementById('cantidad').value) || 0;
          const p = parseFloat(document.getElementById('precio_unitario').value) || 0;
          document.getElementById('sub_total').value = (c * p).toFixed(2);
          const a = parseFloat(document.getElementById('actual').value) || 0;
          const b = parseFloat(document.getElementById('anterior').value) || 0;
          const km = (a - b).toFixed(2);
          document.getElementById('kms_kilometraje').value = km;
          const unit = document.getElementById('unidad').value;
          let r = 0;
          if (unit === 'KILOMETROS / GALON') {
            r = c > 0 ? (km / c) : 0;
          } else {
            r = km > 0 ? (c / km) : 0;
          }
          document.getElementById('ratio_obtenido').value = Number(r).toFixed(4);
        }
        ['cantidad','precio_unitario','actual','anterior','unidad']
          .forEach(id => document.getElementById(id)
            .addEventListener('input', updateCalculations)
          );
        // SweetAlert2: Ver y Eliminar
        document.querySelectorAll('.viewBtn').forEach(btn => {
          btn.onclick = () => {
            const data = JSON.parse(btn.dataset.json);
            let html = '<table class="w-full text-left text-sm">';
            for (let k in data) {
              html += `<tr><th class="pr-2 font-medium">${k}</th><td>${data[k]}</td></tr>`;
            }
            html += '</table>';
            Swal.fire({ title: 'Detalle Consumo', html, width: 600 });
          };
        });
        document.querySelectorAll('.delBtn').forEach(btn => {
          btn.onclick = () => {
            Swal.fire({
              icon: 'warning',
              title: '¿Eliminar este registro?',
              text: 'No podrás deshacer esta acción',
              showCancelButton: true,
              confirmButtonText: 'Sí, eliminar',
              cancelButtonText: 'Cancelar'
            }).then(res => {
              if (res.isConfirmed) {
                window.location = '?delete_id=' + btn.dataset.id;
              }
            });
          };
        });
        // Editar en modal
        document.querySelectorAll('.editBtn').forEach(btn => {
          btn.onclick = () => {
            const data = JSON.parse(btn.dataset.json);
            for (let field in data) {
              const el = document.getElementById(field);
              if (el) el.value = data[field];
            }
            updateId.value = data.id;
            modalTitle.textContent = 'Editar Consumo';
            modal.classList.remove('hidden');
            gsap.from("#modal form", { scale: 0.8, opacity: 0, duration: 0.3 });
          };
        });
        // Filtro búsqueda
        const searchInput = document.getElementById('searchInput');
        const rows = document.querySelectorAll('#tabla tbody tr');
        searchInput.oninput = () => {
          const term = searchInput.value.trim().toLowerCase();
          rows.forEach(r => {
            r.style.display = r.textContent.toLowerCase().includes(term) ? '' : 'none';
          });
        };
      </script>
    </div>
    <!-- === 2) Dashboard Mensual === -->
    <div class="bg-white p-6 rounded-lg shadow">
      <h2 class="text-2xl font-bold mb-4">📊 Dashboard Mensual</h2>
      <!-- Filtros -->
      <div class="flex flex-wrap gap-4 mb-6">
        <label>
          Mes:
          <input id="mesInput" type="month"
                 value="<?= date('Y-m') ?>"
                 class="border px-2 py-1 rounded"/>
        </label>
        <label>
          Operador:
          <select id="operadorFilter" class="border px-2 py-1 rounded">
            <option value="">— Todos —</option>
          </select>
        </label>
        <label>
          Vehículo:
          <select id="vehiculoFilter" class="border px-2 py-1 rounded">
            <option value="">— Todos —</option>
          </select>
        </label>
        <label>
          Propietario:
          <select id="propietarioFilter" class="border px-2 py-1 rounded">
            <option value="">— Todos —</option>
          </select>
        </label>
        <label>
          Tipo Combustible:
          <select id="tipoCombustibleFilter" class="border px-2 py-1 rounded">
            <option value="">— Todos —</option>
          </select>
        </label>
      </div>
      <!-- PivotTable.js -->
      <div id="pivot" class="overflow-auto mb-6 border rounded-lg p-4 bg-gray-50"></div>
      <!-- Contenedor dinámico para gráficos -->
      <div id="dynamicCharts">
        <!-- Los gráficos se generarán dinámicamente aquí -->
      </div>
    </div>
  </div>
  <script>
    // Variable global para almacenar los datos actuales
    let currentData = [];
    let pivotData = [];
    
    // Helper para llenar <select>
    function fillSelect(sel, arr, key) {
      const $s = $(sel), cur = $s.val()||'';
      const uniques = [...new Set(arr.map(r=>r[key]))].filter(v=>v).sort();
      $s.empty().append('<option value="">— Todos —</option>');
      uniques.forEach(v => {
        $s.append(`<option value="${v}"${v===cur?' selected':''}>${v}</option>`);
      });
    }
    
    // Función para crear gráficos dinámicos basados en los datos del pivot
    function createDynamicCharts() {
      const container = document.getElementById('dynamicCharts');
      container.innerHTML = ''; // Limpiar gráficos existentes
      
      // Analizar qué datos están disponibles en el pivot
      const pivotTable = document.querySelector('.pvtTable');
      if (!pivotTable || pivotData.length === 0) return;
      
      // Crear diferentes tipos de gráficos según los datos disponibles
      const chartConfigs = [
        {
          id: 'chartCombustible',
          title: 'Total Combustible por Vehículo',
          type: 'bar',
          dataKey: 'Total Combustible',
          groupBy: 'Vehículo',
          color: 'rgba(54, 162, 235, 0.8)'
        },
        {
          id: 'chartKilometros',
          title: 'Total Kilómetros por Operador',
          type: 'line',
          dataKey: 'Total Kilómetros',
          groupBy: 'Operador',
          color: 'rgba(255, 99, 132, 0.8)'
        },
        {
          id: 'chartCombustiblePropietario',
          title: 'Combustible por Propietario',
          type: 'doughnut',
          dataKey: 'Total Combustible',
          groupBy: 'Propietario',
          color: null // Múltiples colores para doughnut
        },
        {
          id: 'chartRendimiento',
          title: 'Rendimiento (Km/Galón) por Vehículo',
          type: 'bar',
          dataKey: 'rendimiento',
          groupBy: 'Vehículo',
          color: 'rgba(75, 192, 192, 0.8)',
          calculate: true
        }
      ];
      
      chartConfigs.forEach((config, index) => {
        // Crear contenedor para el gráfico con tamaño fijo
        const chartDiv = document.createElement('div');
        chartDiv.className = 'bg-gray-100 p-4 rounded-lg shadow-md chart-container';
        chartDiv.style.cssText = 'min-height: 300px; max-height: 400px; position: relative;';
        
        // Crear estructura interna del contenedor
        const chartContent = `
          <h3 class="font-semibold mb-3 text-center text-gray-700">${config.title}</h3>
          <div class="relative" style="height: 250px;">
            <canvas id="${config.id}"></canvas>
          </div>
        `;
        chartDiv.innerHTML = chartContent;
        container.appendChild(chartDiv);
        
        // Preparar datos para el gráfico
        let chartData = {};
        
        if (config.calculate && config.dataKey === 'rendimiento') {
          // Calcular rendimiento
          pivotData.forEach(row => {
            const key = row[config.groupBy];
            if (key) {
              const combustible = row['Total Combustible'] || 0;
              const kilometros = row['Total Kilómetros'] || 0;
              const rendimiento = combustible > 0 ? kilometros / combustible : 0;
              chartData[key] = (chartData[key] || 0) + rendimiento;
            }
          });
        } else {
          // Agrupar datos normalmente
          pivotData.forEach(row => {
            const key = row[config.groupBy];
            if (key && row[config.dataKey] !== undefined) {
              chartData[key] = (chartData[key] || 0) + row[config.dataKey];
            }
          });
        }
        
        const labels = Object.keys(chartData);
        const data = labels.map(l => chartData[l]);
        
        // Configurar colores
        let backgroundColor = config.color;
        if (config.type === 'doughnut' || config.type === 'pie') {
          backgroundColor = [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(199, 199, 199, 0.8)',
            'rgba(83, 102, 255, 0.8)',
            'rgba(255, 99, 255, 0.8)',
            'rgba(99, 255, 132, 0.8)'
          ];
        }
        
        // Crear gráfico
        const ctx = document.getElementById(config.id).getContext('2d');
        new Chart(ctx, {
          type: config.type,
          data: {
            labels: labels,
            datasets: [{
              label: config.title,
              data: data,
              backgroundColor: backgroundColor,
              borderColor: config.type === 'line' ? config.color : undefined,
              borderWidth: config.type === 'line' ? 2 : 1,
              fill: config.type === 'line' ? false : true,
              tension: config.type === 'line' ? 0.3 : 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
              padding: {
                top: 10,
                bottom: 10,
                left: 10,
                right: 10
              }
            },
            plugins: {
              legend: {
                display: config.type === 'doughnut' || config.type === 'pie',
                position: 'bottom',
                labels: {
                  boxWidth: 12,
                  padding: 8,
                  font: {
                    size: 11
                  }
                }
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    let label = context.label || '';
                    if (label) label += ': ';
                    if (config.dataKey === 'rendimiento') {
                      label += context.parsed.toFixed(2) + ' km/gal';
                    } else if (config.dataKey === 'Total Combustible') {
                      label += context.parsed.toFixed(2) + ' galones';
                    } else if (config.dataKey === 'Total Kilómetros') {
                      label += context.parsed.toFixed(2) + ' km';
                    } else {
                      label += context.parsed.toFixed(2);
                    }
                    return label;
                  }
                }
              }
            },
            scales: config.type === 'bar' || config.type === 'line' ? {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: function(value) {
                    if (config.dataKey === 'rendimiento') return value.toFixed(1) + ' km/gal';
                    if (config.dataKey === 'Total Combustible') return value.toFixed(0) + ' gal';
                    if (config.dataKey === 'Total Kilómetros') return value.toFixed(0) + ' km';
                    return value;
                  },
                  font: {
                    size: 10
                  }
                }
              },
              x: {
                ticks: {
                  font: {
                    size: 10
                  },
                  maxRotation: 45,
                  minRotation: 45
                }
              }
            } : {}
          }
        });
      });
    }
    
    // Función para crear agregadores en español
    function createSpanishAggregators() {
      const aggs = $.pivotUtilities.aggregators;
      return {
        "Contar": aggs["Count"],
        "Contar Valores Únicos": aggs["Count Unique Values"],
        "Listar Valores Únicos": aggs["List Unique Values"],
        "Suma": aggs["Sum"],
        "Suma Entera": aggs["Integer Sum"],
        "Promedio": aggs["Average"],
        "Mediana": aggs["Median"],
        "Varianza Muestral": aggs["Sample Variance"],
        "Desviación Estándar Muestral": aggs["Sample Standard Deviation"],
        "Mínimo": aggs["Minimum"],
        "Máximo": aggs["Maximum"],
        "Primero": aggs["First"],
        "Último": aggs["Last"],
        "Suma sobre Suma": aggs["Sum over Sum"],
        "Límite Superior 80%": aggs["80% Upper Bound"],
        "Límite Inferior 80%": aggs["80% Lower Bound"],
        "Suma como Fracción del Total": aggs["Sum as Fraction of Total"],
        "Suma como Fracción de Filas": aggs["Sum as Fraction of Rows"],
        "Suma como Fracción de Columnas": aggs["Sum as Fraction of Columns"],
        "Contar como Fracción del Total": aggs["Count as Fraction of Total"],
        "Contar como Fracción de Filas": aggs["Count as Fraction of Rows"],
        "Contar como Fracción de Columnas": aggs["Count as Fraction of Columns"]
      };
    }
    
    // Carga y refresco completo
    function cargarDashboard() {
      const [anio, mes] = $('#mesInput').val().split('-');
      $.getJSON(`datos_mensuales.php?mes=${mes}&anio=${anio}`)
        .done(data => {
          // Verificar si hay datos
          if (!data || data.length === 0) {
            $('#pivot').html('<div class="text-center p-8 text-gray-500">No hay datos para el mes seleccionado</div>');
            $('#dynamicCharts').empty();
            return;
          }
          
          currentData = data;
          pivotData = data;
          
          // filtros dinámicos
          fillSelect('#operadorFilter', data, 'Operador');
          fillSelect('#vehiculoFilter', data, 'Vehículo');
          fillSelect('#propietarioFilter', data, 'Propietario');
          fillSelect('#tipoCombustibleFilter', data, 'Tipo Combustible');
          
          // aplicar filtros
          aplicarFiltros();
        })
        .fail((jqXHR, textStatus, errorThrown) => {
          console.error('Error al cargar datos:', textStatus, errorThrown);
          $('#pivot').html('<div class="text-center p-8 text-red-500">Error al cargar los datos</div>');
          $('#dynamicCharts').empty();
          Swal.fire({
            icon: 'error',
            title: 'Error al cargar datos',
            text: 'No se pudieron cargar los datos del mes seleccionado'
          });
        });
    }
    
    // Función para aplicar filtros
    function aplicarFiltros() {
      if (!currentData || currentData.length === 0) {
        $('#pivot').html('<div class="text-center p-8 text-gray-500">No hay datos disponibles</div>');
        $('#dynamicCharts').empty();
        return;
      }
      
      let filtered = currentData;
      
      const op = $('#operadorFilter').val();
      const vh = $('#vehiculoFilter').val();
      const pr = $('#propietarioFilter').val();
      const tc = $('#tipoCombustibleFilter').val();
      
      if (op) filtered = filtered.filter(r => r.Operador === op);
      if (vh) filtered = filtered.filter(r => r.Vehículo === vh);
      if (pr) filtered = filtered.filter(r => r.Propietario === pr);
      if (tc) filtered = filtered.filter(r => r['Tipo Combustible'] === tc);
      
      pivotData = filtered;
      
      // Verificar si hay datos después del filtrado
      if (filtered.length === 0) {
        $('#pivot').html('<div class="text-center p-8 text-gray-500">No hay datos que coincidan con los filtros seleccionados</div>');
        $('#dynamicCharts').empty();
        return;
      }
      
      // Renderizadores en español
      const renderers = $.extend(
        $.pivotUtilities.renderers,
        $.pivotUtilities.chartjs_renderers
      );
      
      // Renombrar renderizadores a español
      const renderersEspanol = {
        "Tabla": renderers["Table"],
        "Tabla con Barras": renderers["Table Barchart"],
        "Mapa de Calor": renderers["Heatmap"],
        "Mapa de Calor por Filas": renderers["Row Heatmap"],
        "Mapa de Calor por Columnas": renderers["Col Heatmap"],
        "Gráfico de Barras": renderers["Bar Chart"],
        "Gráfico de Barras Apiladas": renderers["Stacked Bar Chart"],
        "Gráfico de Barras Horizontales": renderers["Horizontal Bar Chart"],
        "Gráfico de Barras Horizontales Apiladas": renderers["Horizontal Stacked Bar Chart"],
        "Gráfico de Líneas": renderers["Line Chart"],
        "Gráfico de Área": renderers["Area Chart"],
        "Gráfico de Dispersión": renderers["Scatter Chart"]
      };
      
      // PivotTable.js con configuración en español
      $('#pivot').empty().pivotUI(
        filtered,
        {
          rows: ["Operador"],
          cols: ["Vehículo"],
          vals: ["Total Combustible"],
          aggregatorName: "Suma",
          rendererName: "Tabla",
          renderers: renderersEspanol,
          aggregators: createSpanishAggregators(),
          hiddenAttributes: [],
          onRefresh: function(config) {
            // Callback cuando se actualiza el pivot
            setTimeout(createDynamicCharts, 100);
          },
          localeStrings: {
            renderError: "Ocurrió un error al renderizar los resultados de la tabla dinámica.",
            computeError: "Ocurrió un error al calcular los resultados de la tabla dinámica.",
            uiRenderError: "Ocurrió un error al renderizar la interfaz de tabla dinámica.",
            selectAll: "Seleccionar Todo",
            selectNone: "Deseleccionar Todo",
            tooMany: "(demasiados valores para mostrar)",
            filterResults: "Filtrar valores",
            totals: "Totales",
            vs: "vs",
            by: "por"
          }
        }
      );
      
      // Crear gráficos dinámicos
      setTimeout(createDynamicCharts, 100);
    }
    
    // Init
    $(function(){
      // Configurar locale para pivottable
      $.pivotUtilities.locales.es = {
        localeStrings: {
          renderError: "Ocurrió un error al renderizar los resultados.",
          computeError: "Ocurrió un error al calcular los resultados.",
          uiRenderError: "Ocurrió un error al renderizar la interfaz.",
          selectAll: "Seleccionar Todo",
          selectNone: "Deseleccionar Todo",
          tooMany: "(demasiados valores)",
          filterResults: "Filtrar valores",
          totals: "Totales",
          vs: "vs",
          by: "por"
        }
      };
      
      cargarDashboard();
      
      // Event listeners para filtros
      $('#mesInput, #operadorFilter, #vehiculoFilter, #propietarioFilter, #tipoCombustibleFilter')
        .on('change', function() {
          if (this.id === 'mesInput') {
            cargarDashboard();
          } else {
            aplicarFiltros();
          }
        });
    });
  </script>
</body>
</html>
