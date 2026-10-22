<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require 'db.php';

$config = $pdo->query("SELECT clave, valor FROM configuracion")->fetchAll(PDO::FETCH_KEY_PAIR);
$nombreEmpresa = $config['nombre_empresa'] ?? 'Gestión de Caja';
$logoBase64 = $config['logo_base64'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($nombreEmpresa) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .kpi-card { background: white; border-radius: 8px; padding: 20px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .kpi-valor { font-size: 28px; font-weight: bold; }
        .text-ingreso { color: #198754; }
        .text-gasto { color: #dc3545; }
        .card { border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .table th { background-color: #f8f9fa; font-weight: 600; color: #555; font-size: 0.9rem;}
        .table td { font-size: 0.9rem;}
        .btn-periodo.active { background-color: #0d6efd; color: white; }
        .descuadre-ok { color: #198754; font-weight: bold; }
        .descuadre-fallo { color: #dc3545; font-weight: bold; background-color: #f8d7da; }
        .header-logo { max-height: 45px; margin-right: 15px; }
        @media print {
            body { background-color: white !important; padding: 0 !important; }
            .btn, #formRegistroCard, .modal, .btn-close, .btn-editar, .btn-borrar, .footer-creditos { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .kpi-card { border: 1px solid #ddd !important; }
        }
    </style>
</head>
<body class="p-4">
    <div class="container-fluid" id="reporte-caja">
        <!-- Cabecera -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div class="d-flex align-items-center">
                <?php if (!empty($logoBase64)): ?>
                    <img src="<?= $logoBase64 ?>" alt="Logo" class="header-logo">
                <?php endif; ?>
                <div>
                    <h3 class="text-secondary fw-bold mb-0"><?= htmlspecialchars($nombreEmpresa) ?></h3>
                    <small class="text-muted">Hola, <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong></small>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <button class="btn btn-outline-primary btn-periodo active" data-periodo="diario">Diario</button>
                <button class="btn btn-outline-primary btn-periodo" data-periodo="semanal">Semanal</button>
                <button class="btn btn-outline-primary btn-periodo" data-periodo="mensual">Mensual</button>
                <input type="date" id="fechaReferencia" class="form-control w-auto">
                <button class="btn btn-danger" id="btnPdf">📄 PDF</button>
                <a href="backup.php" class="btn btn-info text-white" title="Descargar copia de seguridad">💾 Copia</a>
                <a href="ajustes.php" class="btn btn-secondary">⚙️ Ajustes</a>
                <a href="logout.php" class="btn btn-dark">🚪 Salir</a>
            </div>
        </div>

        <!-- KPIs -->
        <div class="row mb-4">
            <div class="col-md-3"><div class="kpi-card"><div class="text-muted small text-uppercase fw-bold">T. Tarjeta</div><div class="kpi-valor text-secondary" id="kpi-tarjeta">0.00 €</div></div></div>
            <div class="col-md-3"><div class="kpi-card"><div class="text-muted small text-uppercase fw-bold">T. Efectivo</div><div class="kpi-valor text-ingreso" id="kpi-efectivo">0.00 €</div></div></div>
            <div class="col-md-3"><div class="kpi-card"><div class="text-muted small text-uppercase fw-bold">T. Gastos</div><div class="kpi-valor text-gasto" id="kpi-gastos">0.00 €</div></div></div>
            <div class="col-md-3"><div class="kpi-card"><div class="text-muted small text-uppercase fw-bold">Descuadre Total</div><div class="kpi-valor" id="kpi-descuadre">0.00 €</div></div></div>
        </div>

        <!-- Formulario -->
        <div class="card mb-4" id="formRegistroCard">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">🔒 Nuevo Cierre de Caja / Arqueo</h5>
                <form id="formTurno">
                    <div class="row">
                        <div class="col-md-3 mb-3"><label class="form-label">Fecha</label><input type="date" name="fecha" id="fechaTurno" class="form-control" required></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Turno</label><select name="turno" class="form-select" required><option value="Mañana">Mañana (6:00 - 13:00)</option><option value="Tarde">Tarde (17:00 - 21:00)</option><option value="Jornada Completa" selected>Jornada Completa</option></select></div>
                        <div class="col-md-3 mb-3"><label class="form-label text-primary fw-bold">Facturado Tarjeta (TPV)</label><input type="number" step="0.01" name="tarjeta" class="form-control" required placeholder="0.00"></div>
                        <div class="col-md-3 mb-3"><label class="form-label text-success fw-bold">Facturado Efectivo (TPV)</label><input type="number" step="0.01" name="efectivo" class="form-control" required placeholder="0.00"></div>
                        <div class="col-md-3 mb-3"><label class="form-label text-dark fw-bold">Fondo Inicial (Cambio)</label><input type="number" step="0.01" name="fondo_inicial" class="form-control" value="0.00" required></div>
                        <div class="col-md-3 mb-3"><label class="form-label text-danger fw-bold">Gastos en Efectivo</label><input type="number" step="0.01" name="gastos" class="form-control" value="0.00" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label text-danger fw-bold">Proveedor / Motivo del Gasto</label><input type="text" name="proveedor" class="form-control" placeholder="Ej. Harina (Paco), Aceite, Bombonas..."></div>
                        <div class="col-md-4 mb-3"><label class="form-label fw-bold text-dark">💵 Efectivo Contado en Cajón</label><input type="number" step="0.01" name="contado" class="form-control" required placeholder="0.00"></div>
                        <div class="col-md-8 d-flex align-items-end mb-3"><button type="submit" class="btn btn-primary px-4 py-2">Guardar y Calcular Cuadre</button></div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">📋 Historial de Turnos</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Fecha</th><th>Turno</th><th>Fondo</th><th>Tarjeta</th><th>Efectivo TPV</th><th>Gastos</th><th>Proveedor</th><th>Contado</th><th>Descuadre</th><th>Acción</th></tr></thead>
                        <tbody id="tablaTurnos"></tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- FOOTER DE AUTORÍA -->
        <div class="text-center mt-4 mb-2 text-muted footer-creditos" style="font-size: 0.85rem;">
            Creado por <a href="https://bytespc.es" target="_blank" class="text-decoration-none">Bytespc.es</a>
        </div>
    </div>

    <!-- Modales -->
    <div class="modal fade" id="modalConfirmarBorrado" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Eliminar Turno</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">¿Seguro que quieres eliminar este cierre de caja?</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-danger" id="btnConfirmarBorrado">Sí, eliminar</button></div></div></div></div>

    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">✏️ Editar Cierre de Caja</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <form id="formEditar">
                <input type="hidden" name="id" id="edit-id">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Fecha</label><input type="date" name="fecha" id="edit-fecha" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Turno</label><select name="turno" id="edit-turno" class="form-select" required><option value="Mañana">Mañana</option><option value="Tarde">Tarde</option><option value="Jornada Completa">Jornada Completa</option></select></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Fondo Inicial (€)</label><input type="number" step="0.01" name="fondo_inicial" id="edit-fondo" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label text-primary fw-bold">Facturado Tarjeta</label><input type="number" step="0.01" name="tarjeta" id="edit-tarjeta" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label text-success fw-bold">Facturado Efectivo</label><input type="number" step="0.01" name="efectivo" id="edit-efectivo" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label text-danger fw-bold">Gastos en Efectivo</label><input type="number" step="0.01" name="gastos" id="edit-gastos" class="form-control" required></div>
                    <div class="col-md-8 mb-3"><label class="form-label text-danger fw-bold">Proveedor / Motivo</label><input type="text" name="proveedor" id="edit-proveedor" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label class="form-label fw-bold text-dark">💵 Efectivo Contado</label><input type="number" step="0.01" name="contado" id="edit-contado" class="form-control" required></div>
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
                kpiDesc.textContent = data.totales.descuadre + ' €'; kpiDesc.className = (numDesc === 0) ? 'kpi-valor text-ingreso' : 'kpi-valor text-gasto';
                const tbody = document.getElementById('tablaTurnos'); let html = '';
                if (data.turnos.length === 0) { html = '<tr><td colspan="10" class="text-center text-muted">No hay cierres en este periodo</td></tr>'; }
                else {
                    data.turnos.forEach(t => {
                        const numDesc = parseFloat(t.descuadre); const claseDesc = numDesc === 0 ? 'descuadre-ok' : 'descuadre-fallo'; const signoDesc = numDesc > 0 ? '+' : '';
                        let botones = '-'; if (periodoActual === 'diario') { botones = `<div class="d-flex gap-1"><button class="btn btn-sm btn-outline-primary btn-editar" data-id="${t.id}">✏️</button><button class="btn btn-sm btn-outline-danger btn-borrar" data-id="${t.id}">🗑️</button></div>`; }
                        const txtProveedor = t.proveedor ? t.proveedor : '-';
                        html += `<tr><td>${t.fecha}</td><td class="fw-bold">${t.turno}</td><td class="text-muted">${t.fondo_inicial} €</td><td>${t.total_tarjeta} €</td><td>${t.total_efectivo} €</td><td class="text-gasto">${t.gastos_efectivo} €</td><td class="text-muted small">${txtProveedor}</td><td class="fw-bold">${t.efectivo_contado} €</td><td class="${claseDesc}">${signoDesc}${t.descuadre} €</td><td>${botones}</td></tr>`;
                    });
                }
                tbody.innerHTML = html;
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
            const formData = new FormData(form);
            fetch('api.php?action=update_turno', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
                if (data.status === 'success') { bootstrap.Modal.getInstance(document.getElementById('modalEditar')).hide(); cargarDatos(); } else { alert("Error al actualizar: " + data.message); }
            });
        });
        document.getElementById('btnConfirmarBorrado').addEventListener('click', function() {
            if (idParaBorrar) { const formData = new FormData(); formData.append('id', idParaBorrar);
                fetch('api.php?action=delete_turno', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if(data.status === 'success') cargarDatos(); }).finally(() => { bootstrap.Modal.getInstance(document.getElementById('modalConfirmarBorrado')).hide(); idParaBorrar = null; });
            }
        });
        document.getElementById('btnPdf').addEventListener('click', function() { window.print(); });
    </script>
</body>
</html>