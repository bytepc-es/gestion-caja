<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require 'db.php';
$config = $pdo->query("SELECT clave, valor FROM configuracion")->fetchAll(PDO::FETCH_KEY_PAIR);
$nombreEmpresa = $config['nombre_empresa'] ?? 'Gestión de Caja'; $logoBase64 = $config['logo_base64'] ?? '';
$h_manana = $config['horario_manana'] ?? '6:00 - 13:00'; $h_tarde = $config['horario_tarde'] ?? '17:00 - 21:00';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nombreEmpresa) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .kpi-card { background: white; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; height: 100%; transition: transform 0.2s; }
        .kpi-card:hover { transform: translateY(-3px); }
        .kpi-valor { font-size: 26px; font-weight: 800; letter-spacing: -1px; margin-top: 5px;}
        .kpi-icon { font-size: 22px; opacity: 0.8; }
        .text-ingreso { color: #198754; } .text-gasto { color: #dc3545; } .text-tarjeta { color: #0d6efd; } .text-efectivo { color: #fd7e14; }
        .card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; margin-bottom: 20px; }
        .card-header { background-color: white; border-bottom: 1px solid #f0f0f0; border-radius: 12px 12px 0 0 !important; padding: 15px 20px; }
        .table th { background-color: #f8f9fa; font-weight: 600; color: #555; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;}
        .table td { font-size: 0.9rem; vertical-align: middle; }
        .btn-periodo.active { background-color: #0d6efd; color: white; border-color: #0d6efd; }
        .descuadre-ok { color: #198754; font-weight: bold; background-color: #d1e7dd; padding: 4px 8px; border-radius: 20px; font-size: 0.85rem;}
        .descuadre-fallo { color: #dc3545; font-weight: bold; background-color: #f8d7da; padding: 4px 8px; border-radius: 20px; font-size: 0.85rem;}
        .header-logo { max-height: 45px; margin-right: 15px; border-radius: 8px; }
        .chart-container { position: relative; height: 300px; width: 100%; }
        
        @media (max-width: 768px) {
            .header-actions { flex-direction: column; width: 100%; margin-top: 15px; } .header-actions .btn { width: 100%; margin-bottom: 5px; }
            .header-filtros { width: 100%; justify-content: space-between; margin-top: 15px; }
        }
        @media print {
            body { background-color: white !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .kpi-card { border: 1px solid #ddd !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="p-3 p-md-4">
    <div class="container-fluid" id="reporte-caja">
        
        <!-- IFRAME OCULTO PARA FORZAR DESCARGAS -->
        <iframe id="downloadFrame" style="display:none;"></iframe>

        <!-- CONTENEDOR DE NOTIFICACIONES TOAST DE BOOTSTRAP -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1055;">
            <div id="liveToast" class="toast align-items-center text-bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body fs-6" id="toast-mensaje">
                        Archivo generado.
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>

        <!-- Cabecera -->
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center mb-4 pb-3 border-bottom gap-3">
            <div class="d-flex align-items-center">
                <?php if (!empty($logoBase64)): ?><img src="<?= $logoBase64 ?>" alt="Logo" class="header-logo"><?php endif; ?>
                <div><h4 class="text-dark fw-bold mb-0"><?= htmlspecialchars($nombreEmpresa) ?></h4><small class="text-muted"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['usuario']) ?></small></div>
            </div>
            <div class="d-flex align-items-center gap-2 header-filtros">
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-primary btn-periodo active" data-periodo="diario">Diario</button>
                    <button class="btn btn-sm btn-outline-primary btn-periodo" data-periodo="semanal">Semanal</button>
                    <button class="btn btn-sm btn-outline-primary btn-periodo" data-periodo="mensual">Mensual</button>
                </div>
                <input type="date" id="fechaReferencia" class="form-control form-control-sm w-auto">
            </div>
            <div class="d-flex gap-1 header-actions">
                <button class="btn btn-sm btn-success" id="btnExcel"><i class="bi bi-file-earmark-excel"></i> Excel</button>
                <button class="btn btn-sm btn-danger" id="btnPdf"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                <button class="btn btn-sm btn-info text-white" id="btnCopia"><i class="bi bi-hdd"></i> Copia</button>
                <a href="ajustes.php" class="btn btn-sm btn-secondary"><i class="bi bi-gear"></i></a>
                <a href="logout.php" class="btn btn-sm btn-dark"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>

        <!-- KPIs -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-icon text-tarjeta"><i class="bi bi-credit-card"></i></div><div class="text-muted small text-uppercase fw-bold mt-1">Tarjeta</div><div class="kpi-valor text-tarjeta" id="kpi-tarjeta">0.00 €</div></div></div>
            <div class="col-12 col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-icon text-efectivo"><i class="bi bi-cash-coin"></i></div><div class="text-muted small text-uppercase fw-bold mt-1">Efectivo</div><div class="kpi-valor text-efectivo" id="kpi-efectivo">0.00 €</div></div></div>
            <div class="col-12 col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-icon text-gasto"><i class="bi bi-cart-dash"></i></div><div class="text-muted small text-uppercase fw-bold mt-1">Gastos</div><div class="kpi-valor text-gasto" id="kpi-gastos">0.00 €</div></div></div>
            <div class="col-12 col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-icon" id="icono-descuadre"><i class="bi bi-check-circle"></i></div><div class="text-muted small text-uppercase fw-bold mt-1">Descuadre</div><div class="kpi-valor" id="kpi-descuadre">0.00 €</div></div></div>
        </div>

        <!-- Formulario (ARRIBA) -->
        <div class="card mb-4 no-print" id="formRegistroCard">
            <div class="card-header"><h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle-fill text-primary"></i> Nuevo Cierre de Caja</h5></div>
            <div class="card-body">
                <form id="formTurno">
                    <div class="row g-3">
                        <div class="col-12 col-md-6 col-lg-3"><label class="form-label fw-bold">Fecha</label><input type="date" name="fecha" id="fechaTurno" class="form-control" required></div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label fw-bold">Turno</label>
                            <select name="turno" class="form-select" required>
                                <option value="Mañana">Mañana (<?= htmlspecialchars($h_manana) ?>)</option>
                                <option value="Tarde">Tarde (<?= htmlspecialchars($h_tarde) ?>)</option>
                                <option value="Jornada Completa" selected>Jornada Completa</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3"><label class="form-label text-primary fw-bold">Facturado Tarjeta</label><input type="number" step="0.01" name="tarjeta" class="form-control" required placeholder="0.00"></div>
                        <div class="col-12 col-md-6 col-lg-3"><label class="form-label text-warning fw-bold">Facturado Efectivo</label><input type="number" step="0.01" name="efectivo" class="form-control" required placeholder="0.00"></div>
                        <div class="col-12 col-md-6 col-lg-3"><label class="form-label text-dark fw-bold">Fondo Inicial</label><input type="number" step="0.01" name="fondo_inicial" class="form-control" value="0.00" required></div>
                        <div class="col-12 col-md-6 col-lg-3"><label class="form-label text-danger fw-bold">Gastos Efectivo</label><input type="number" step="0.01" name="gastos" class="form-control" value="0.00" required></div>
                        <div class="col-12 col-md-12 col-lg-6">
                            <label class="form-label text-danger fw-bold">Proveedor / Motivo</label>
                            <!-- TEXTO DE EJEMPLO CORREGIDO AQUÍ -->
                            <input type="text" name="proveedor" class="form-control" placeholder="Ej. Harina, Aceite, Bombonas...">
                        </div>
                        <div class="col-12 col-md-6 col-lg-4"><label class="form-label fw-bold text-dark">💵 Efectivo Contado</label><input type="number" step="0.01" name="contado" class="form-control" required placeholder="0.00"></div>
                        <div class="col-12 col-md-6 col-lg-8 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100 fw-bold py-2">Guardar y Calcular Cuadre</button></div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0 fw-bold"><i class="bi bi-list-ul"></i> Historial de Turnos</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead><tr><th class="ps-4">Fecha</th><th>Turno</th><th>Fondo</th><th>Tarjeta</th><th>Efectivo TPV</th><th>Gastos</th><th>Proveedor</th><th>Contado</th><th>Descuadre</th><th class="text-center">Acción</th></tr></thead>
                        <tbody id="tablaTurnos"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Gráfico (ABAJO) -->
        <div class="card mb-4 no-print">
            <div class="card-header"><h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-graph-up-arrow"></i> Evolución del Periodo</h6></div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="graficoCaja"></canvas>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3 mb-2 text-muted no-print" style="font-size: 0.8rem;">Creado por <a href="https://bytespc.es" target="_blank" class="text-decoration-none fw-bold">Bytespc.es</a></div>
    </div>

    <!-- Modales -->
    <div class="modal fade" id="modalConfirmarBorrado" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Eliminar Turno</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">¿Seguro que quieres eliminar este cierre?</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-danger" id="btnConfirmarBorrado">Sí, eliminar</button></div></div></div></div>

    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white"><h5 class="modal-title">✏️ Editar Cierre de Caja</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <form id="formEditar">
                <input type="hidden" name="id" id="edit-id">
                <div class="row g-3">
                    <div class="col-12 col-md-4 mb-2"><label class="form-label fw-bold">Fecha</label><input type="date" name="fecha" id="edit-fecha" class="form-control" required></div>
                    <div class="col-12 col-md-4 mb-2"><label class="form-label fw-bold">Turno</label><select name="turno" id="edit-turno" class="form-select" required><option value="Mañana">Mañana (<?= htmlspecialchars($h_manana) ?>)</option><option value="Tarde">Tarde (<?= htmlspecialchars($h_tarde) ?>)</option><option value="Jornada Completa">Jornada Completa</option></select></div>
                    <div class="col-12 col-md-4 mb-2"><label class="form-label fw-bold">Fondo Inicial (€)</label><input type="number" step="0.01" name="fondo_inicial" id="edit-fondo" class="form-control" required></div>
                    <div class="col-12 col-md-4 mb-2"><label class="form-label text-primary fw-bold">Tarjeta</label><input type="number" step="0.01" name="tarjeta" id="edit-tarjeta" class="form-control" required></div>
                    <div class="col-12 col-md-4 mb-2"><label class="form-label text-warning fw-bold">Efectivo</label><input type="number" step="0.01" name="efectivo" id="edit-efectivo" class="form-control" required></div>
                    <div class="col-12 col-md-4 mb-2"><label class="form-label text-danger fw-bold">Gastos</label><input type="number" step="0.01" name="gastos" id="edit-gastos" class="form-control" required></div>
                    <div class="col-12 col-md-8 mb-2"><label class="form-label text-danger fw-bold">Proveedor / Motivo</label><input type="text" name="proveedor" id="edit-proveedor" class="form-control"></div>
                    <div class="col-12 col-md-4 mb-2"><label class="form-label fw-bold text-dark">💵 Contado</label><input type="number" step="0.01" name="contado" id="edit-contado" class="form-control" required></div>
                </div>
            </form>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btnGuardarEdicion">Guardar Cambios</button></div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let idParaBorrar = null; let periodoActual = 'diario'; let turnosGlobal = []; 
        let miGrafico = null; 

        document.addEventListener('DOMContentLoaded', () => {
            const hoy = new Date().toISOString().split('T')[0];
            document.getElementById('fechaReferencia').value = hoy;
            document.getElementById('fechaTurno').value = hoy;
            document.getElementById('fechaReferencia').setAttribute('max', hoy);
            document.getElementById('fechaTurno').setAttribute('max', hoy);
            cargarDatos();
        });

        document.querySelectorAll('.btn-periodo').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.btn-periodo').forEach(b => b.classList.remove('active'));
                this.classList.add('active'); periodoActual = this.dataset.periodo; cargarDatos();
            });
        });

        document.getElementById('fechaReferencia').addEventListener('change', cargarDatos);

        document.getElementById('formTurno').addEventListener('submit', function(e) {
            e.preventDefault(); const formData = new FormData(this);
            fetch('api.php?action=add_turno', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
                if(data.status === 'success') { this.reset(); document.getElementById('fechaTurno').value = new Date().toISOString().split('T')[0]; cargarDatos(); }
            });
        });

        function cargarDatos() {
            const fechaRef = document.getElementById('fechaReferencia').value; if (!fechaRef) return;
            document.getElementById('formRegistroCard').style.display = (periodoActual === 'diario') ? 'block' : 'none';
            let startDate, endDate; const fecha = new Date(fechaRef + 'T00:00:00');
            if (periodoActual === 'diario') { startDate = fechaRef; endDate = fechaRef; }
            else if (periodoActual === 'semanal') {
                const day = fecha.getDay(); const diff = fecha.getDate() - day + (day === 0 ? -6 : 1);
                const lunes = new Date(fecha.setDate(diff)); startDate = lunes.toISOString().split('T')[0];
                const domingo = new Date(lunes); domingo.setDate(lunes.getDate() + 6); endDate = domingo.toISOString().split('T')[0];
            } else if (periodoActual === 'mensual') {
                const año = fecha.getFullYear(); const mes = fecha.getMonth();
                startDate = new Date(año, mes, 1).toISOString().split('T')[0]; endDate = new Date(año, mes + 1, 0).toISOString().split('T')[0];
            }
            
            fetch(`api.php?action=get_data&start=${startDate}&end=${endDate}`).then(res => res.json()).then(data => {
                turnosGlobal = data.turnos; 
                
                document.getElementById('kpi-tarjeta').textContent = data.totales.tarjeta + ' €';
                document.getElementById('kpi-efectivo').textContent = data.totales.efectivo + ' €';
                document.getElementById('kpi-gastos').textContent = data.totales.gastos + ' €';
                const kpiDesc = document.getElementById('kpi-descuadre'); const numDesc = parseFloat(data.totales.descuadre);
                kpiDesc.textContent = data.totales.descuadre + ' €'; 
                
                const iconoDesc = document.getElementById('icono-descuadre');
                if (numDesc === 0) { kpiDesc.className = 'kpi-valor text-ingreso'; iconoDesc.className = 'kpi-icon text-ingreso'; iconoDesc.innerHTML = '<i class="bi bi-check-circle-fill"></i>'; } 
                else { kpiDesc.className = 'kpi-valor text-gasto'; iconoDesc.className = 'kpi-icon text-gasto'; iconoDesc.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>'; }

                const tbody = document.getElementById('tablaTurnos'); let html = '';
                if (data.turnos.length === 0) { html = '<tr><td colspan="10" class="text-center text-muted py-4">No hay cierres en este periodo</td></tr>'; }
                else {
                    data.turnos.forEach(t => {
                        const numDesc = parseFloat(t.descuadre); const claseDesc = numDesc === 0 ? 'descuadre-ok' : 'descuadre-fallo'; const signoDesc = numDesc > 0 ? '+' : '';
                        let botones = '-'; if (periodoActual === 'diario') { botones = `<div class="d-flex justify-content-center gap-1"><button class="btn btn-sm btn-outline-primary btn-editar" data-id="${t.id}"><i class="bi bi-pencil-square"></i></button><button class="btn btn-sm btn-outline-danger btn-borrar" data-id="${t.id}"><i class="bi bi-trash"></i></button></div>`; }
                        const txtProveedor = t.proveedor ? t.proveedor : '-';
                        html += `<tr><td class="ps-4">${t.fecha}</td><td class="fw-bold">${t.turno}</td><td class="text-muted">${t.fondo_inicial} €</td><td class="text-tarjeta">${t.total_tarjeta} €</td><td class="text-efectivo">${t.total_efectivo} €</td><td class="text-gasto">${t.gastos_efectivo} €</td><td class="text-muted small">${txtProveedor}</td><td class="fw-bold">${t.efectivo_contado} €</td><td><span class="${claseDesc}">${signoDesc}${t.descuadre} €</span></td><td class="text-center">${botones}</td></tr>`;
                    });
                }
                tbody.innerHTML = html;

                actualizarGrafico(data.chart);
            });
        }

        function actualizarGrafico(chartData) {
            const ctx = document.getElementById('graficoCaja').getContext('2d');
            if (miGrafico) { miGrafico.destroy(); }
            if (chartData.labels.length === 0) {
                chartData.labels = ['Sin datos']; chartData.tarjeta = [0]; chartData.efectivo = [0]; chartData.gastos = [0];
            }
            miGrafico = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        { label: 'Tarjeta (€)', data: chartData.tarjeta, borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, 0.1)', tension: 0.3, fill: true, pointRadius: 4 },
                        { label: 'Efectivo (€)', data: chartData.efectivo, borderColor: '#fd7e14', backgroundColor: 'rgba(253, 126, 20, 0.1)', tension: 0.3, fill: true, pointRadius: 4 },
                        { label: 'Gastos (€)', data: chartData.gastos, borderColor: '#dc3545', backgroundColor: 'rgba(220, 53, 69, 0.1)', tension: 0.3, fill: true, pointRadius: 4 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: function(context) { return context.dataset.label + ': ' + context.raw.toFixed(2) + ' €'; } } } },
                    scales: { y: { beginAtZero: true, grid: { color: '#f0f0f0' } }, x: { grid: { display: false } } }
                }
            });
        }

        document.addEventListener('click', function(e) {
            const btnBorrar = e.target.closest('.btn-borrar'); if (btnBorrar) { idParaBorrar = btnBorrar.getAttribute('data-id'); bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmarBorrado')).show(); }
            const btnEditar = e.target.closest('.btn-editar'); if (btnEditar) { const id = btnEditar.getAttribute('data-id'); abrirModalEditar(id); }
        });

        function abrirModalEditar(id) {
            const turno = turnosGlobal.find(t => t.id == id); if (!turno) return;
            document.getElementById('edit-id').value = turno.id; document.getElementById('edit-fecha').value = turno.fecha; document.getElementById('edit-turno').value = turno.turno;
            document.getElementById('edit-fondo').value = turno.fondo_inicial; document.getElementById('edit-tarjeta').value = turno.total_tarjeta; document.getElementById('edit-efectivo').value = turno.total_efectivo;
            document.getElementById('edit-gastos').value = turno.gastos_efectivo; document.getElementById('edit-proveedor').value = turno.proveedor || ''; document.getElementById('edit-contado').value = turno.efectivo_contado;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditar')).show();
        }

        document.getElementById('btnGuardarEdicion').addEventListener('click', function() {
            const form = document.getElementById('formEditar'); if (!form.checkValidity()) { form.reportValidity(); return; }
            fetch('api.php?action=update_turno', { method: 'POST', body: new FormData(form) }).then(res => res.json()).then(data => {
                if (data.status === 'success') { bootstrap.Modal.getInstance(document.getElementById('modalEditar')).hide(); cargarDatos(); } else { alert("Error al actualizar"); }
            });
        });

        document.getElementById('btnConfirmarBorrado').addEventListener('click', function() {
            if (idParaBorrar) { const formData = new FormData(); formData.append('id', idParaBorrar);
                fetch('api.php?action=delete_turno', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if(data.status === 'success') cargarDatos(); }).finally(() => { bootstrap.Modal.getInstance(document.getElementById('modalConfirmarBorrado')).hide(); idParaBorrar = null; });
            }
        });

        document.getElementById('btnPdf').addEventListener('click', function() { window.print(); });

        function mostrarNotificacion(mensaje, esError = false) {
            const toastEl = document.getElementById('liveToast');
            const toastBody = document.getElementById('toast-mensaje');
            
            if (esError) { toastEl.classList.remove('text-bg-success'); toastEl.classList.add('text-bg-danger'); } 
            else { toastEl.classList.remove('text-bg-danger'); toastEl.classList.add('text-bg-success'); }
            
            toastBody.textContent = mensaje;
            const toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3000 });
            toast.show();
        }

        document.getElementById('btnExcel').addEventListener('click', function() {
            fetch('api.php?action=get_csv_data')
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        document.getElementById('downloadFrame').src = data.url;
                        mostrarNotificacion("✅ ¡Excel generado! Revisa tu carpeta de Descargas.");
                    } else { mostrarNotificacion("❌ Error al generar Excel", true); }
                }).catch(() => mostrarNotificacion("❌ Error de conexión", true));
        });

        document.getElementById('btnCopia').addEventListener('click', function() {
            fetch('backup.php')
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        document.getElementById('downloadFrame').src = data.url;
                        mostrarNotificacion("✅ ¡Copia de seguridad generada! Revisa tu carpeta de Descargas.");
                    } else { mostrarNotificacion("❌ Error: " + data.message, true); }
                }).catch(() => mostrarNotificacion("❌ Error de conexión", true));
        });
    </script>
</body>
</html>