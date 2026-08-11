<?php
include '../includes/session.php';
include '../config/db.php';
include '../includes/header.php';

// Recoger parámetros de búsqueda: término (por alumno o título) y carrera
$busqueda = "";
$carrera_selected = "";
if (isset($_GET['busqueda'])) {
    $busqueda = $conn->real_escape_string(trim($_GET['busqueda']));
}
if (isset($_GET['carrera'])) {
    $carrera_selected = $conn->real_escape_string(trim($_GET['carrera']));
}

// Obtener la lista de carreras desde la tabla "carreras"
$sqlCarreras = "SELECT * FROM carreras ORDER BY nombre ASC";
$resCarreras = $conn->query($sqlCarreras);
$career_options = [];
while ($row = $resCarreras->fetch_assoc()) {
    $career_options[] = $row;
}

// Construir la consulta SQL para tesis, uniendo con la tabla sedes para obtener el nombre
$sql = "SELECT t.*, s.nombre AS sede_nombre 
        FROM tesis t 
        LEFT JOIN sedes s ON t.sede_id = s.id";
$condiciones = [];

if (!empty($busqueda)) {
    $condiciones[] = "(alumno LIKE '%$busqueda%' OR titulo LIKE '%$busqueda%')";
}
if (!empty($carrera_selected)) {
    $condiciones[] = "carrera = '$carrera_selected'";
}
if (count($condiciones) > 0) {
    $sql .= " WHERE " . implode(" AND ", $condiciones);
}
$sql .= " ORDER BY alumno ASC";

$result = $conn->query($sql);
?>
<div class="card shadow-sm mb-4">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0">Lista de Tesis</h5>
  </div>
  <div class="card-body py-2">
    <!-- Formulario de búsqueda compacto -->
    <form method="get" action="list.php" class="row g-2 align-items-center mb-2">
      <div class="col-md-5">
        <input type="text" name="busqueda" class="form-control form-control-sm" placeholder="Buscar por alumno o título" value="<?php echo htmlspecialchars($busqueda); ?>">
      </div>
      <div class="col-md-3">
        <select name="carrera" class="form-select form-select-sm">
          <option value="">Carrera</option>
          <?php foreach ($career_options as $option): ?>
            <option value="<?php echo $option['nombre']; ?>" <?php if ($carrera_selected == $option['nombre']) echo "selected"; ?>>
              <?php echo $option['nombre']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-sm btn-primary w-100">Buscar</button>
      </div>
      <div class="col-md-2">
        <a href="list.php" class="btn btn-secondary btn-sm w-100">Ver Todos</a>
      </div>
    </form>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
      <div class="mb-2">
        <a href="add.php" class="btn btn-success btn-sm">
          <i class="fas fa-plus"></i> Agregar Tesis
        </a>
        <a href="upload_excel.php" class="btn btn-info btn-sm">
          <i class="fas fa-file-upload"></i> Subir tesis desde Excel
        </a>
        <a href="download_template.php" class="btn btn-secondary btn-sm">
          <i class="fas fa-download"></i> Descargar plantilla en blanco
        </a>
      </div>
    <?php endif; ?>

    <div class="table-responsive">
      <table id="tablaTesis" class="table table-sm table-striped table-hover align-middle small">
        <thead class="table-secondary">
          <tr>
            <th>N° Tesis</th>
            <th>N° Cuenta</th>
            <th>Alumno</th>
            <th>Carrera</th>
            <th>Título</th>
            <th>Año Egresado</th>
            <th>Asesor Metodológico</th>
            <th>Asesor Temático</th>
            <th>Cantidad</th>
            <th>Sede</th>
            <th style="width:110px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['numero']; ?></td>
            <td><?php echo $row['cuenta']; ?></td>
            <td><?php echo $row['alumno']; ?></td>
            <td><?php echo $row['carrera']; ?></td>
            <td><?php echo $row['titulo']; ?></td>
            <td><?php echo $row['anio_egresado']; ?></td>
            <td><?php echo $row['asesor_metodologico']; ?></td>
            <td><?php echo $row['asesor_tematico']; ?></td>
            <td><?php echo $row['cantidad']; ?></td>
            <td><?php echo $row['sede_nombre']; ?></td>
            <td>
              <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm py-0" title="Ver">
                <i class="fas fa-eye"></i>
              </a>
              <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm py-0" title="Editar">
                  <i class="fas fa-edit"></i>
                </a>
                <a href="javascript:void(0);" onclick="return confirmarEliminar('delete.php?id=<?php echo $row['id']; ?>');" class="btn btn-danger btn-sm py-0" title="Eliminar">
                  <i class="fas fa-trash-alt"></i>
                </a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  $('#tablaTesis').DataTable({
    language: { url: "//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json" }
  });
});

function confirmarEliminar(url) {
  Swal.fire({
    title: '¿Estás seguro?',
    text: "Esta acción eliminará el registro permanentemente.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-secondary' },
    buttonsStyling: false
  }).then((result) => {
    if(result.isConfirmed) window.location.href = url;
  });
  return false;
}
</script>

<?php include '../includes/footer.php'; ?>
