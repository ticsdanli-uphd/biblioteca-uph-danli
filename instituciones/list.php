<?php

include '../includes/session.php';
include '../config/db.php';


// =====================================================
// VERIFICAR SESIÓN
// =====================================================

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}


// =====================================================
// OBTENER INSTITUCIONES
// =====================================================

$sql = "
    SELECT
        id,
        nombre,
        descripcion,
        fecha_creacion
    FROM instituciones_externas
    ORDER BY nombre ASC
";

$result = $conn->query($sql);


// =====================================================
// CONTADOR
// =====================================================

$totalInstituciones = 0;

if ($result) {
    $totalInstituciones = $result->num_rows;
}


// =====================================================
// HEADER
// =====================================================

include '../includes/header.php';

?>


<style>

/* =====================================================
   CONTENEDOR
===================================================== */

.instituciones-container {

    max-width: 1200px;

    margin: 0 auto;

}


/* =====================================================
   ENCABEZADO
===================================================== */

.instituciones-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;

    gap: 20px;

}

.instituciones-titulo {

    margin: 0;

    color: #202b3c;

    font-size: 32px;

    font-weight: 700;

}

.instituciones-subtitulo {

    margin-top: 5px;

    color: #718096;

    font-size: 15px;

}


/* =====================================================
   BOTÓN NUEVA
===================================================== */

.btn-nueva {

    background: #3159d8;

    border: none;

    color: white;

    padding: 12px 22px;

    border-radius: 12px;

    font-weight: 600;

    text-decoration: none;

    box-shadow:
        0 6px 15px rgba(49,89,216,.25);

    white-space: nowrap;

    transition: .2s;

}

.btn-nueva:hover {

    background: #2649bd;

    color: white;

    transform: translateY(-1px);

}


/* =====================================================
   RESUMEN
===================================================== */

.resumen-card {

    background: white;

    border-radius: 16px;

    padding: 18px 22px;

    margin-bottom: 20px;

    box-shadow:
        0 8px 25px rgba(0,0,0,.07);

    display: flex;

    align-items: center;

    gap: 15px;

}

.resumen-icon {

    width: 52px;

    height: 52px;

    border-radius: 14px;

    background: #3159d8;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;

}

.resumen-numero {

    font-size: 26px;

    font-weight: 700;

    color: #202b3c;

    line-height: 1;

}

.resumen-texto {

    color: #718096;

    font-size: 14px;

    margin-top: 5px;

}


/* =====================================================
   TARJETA TABLA
===================================================== */

.tabla-card {

    background: white;

    border-radius: 18px;

    box-shadow:
        0 10px 30px rgba(0,0,0,.08);

    overflow: hidden;

}

.tabla-header {

    padding: 20px;

    border-bottom:
        1px solid #edf0f5;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

}

.tabla-header h5 {

    margin: 0;

    font-weight: 700;

    color: #202b3c;

}

.tabla-header p {

    margin: 4px 0 0;

    color: #718096;

    font-size: 13px;

}


/* =====================================================
   BUSCADOR
===================================================== */

.buscador {

    position: relative;

    width: 300px;

}

.buscador i {

    position: absolute;

    left: 13px;

    top: 50%;

    transform: translateY(-50%);

    color: #718096;

}

.buscador input {

    padding-left: 38px;

    border-radius: 10px;

    border: 1px solid #dce2ea;

}


/* =====================================================
   TABLA
===================================================== */

.tabla-wrapper {

    overflow-x: auto;

    padding: 0 15px 15px;

}

#tabla-instituciones {

    margin-bottom: 0 !important;

}

#tabla-instituciones thead th {

    background: #3159d8 !important;

    color: white !important;

    border: none !important;

    padding: 14px 12px;

    font-weight: 600;

    white-space: nowrap;

}

#tabla-instituciones tbody td {

    vertical-align: middle;

    padding: 13px 12px;

}


/* =====================================================
   ID
===================================================== */

.badge-id {

    background: #eef3ff;

    color: #3159d8;

    border-radius: 8px;

    padding: 6px 10px;

    font-weight: 700;

}


/* =====================================================
   NOMBRE
===================================================== */

.nombre-institucion {

    font-weight: 700;

    color: #202b3c;

}

.descripcion {

    color: #718096;

    max-width: 450px;

}


/* =====================================================
   FECHA
===================================================== */

.fecha {

    color: #4a5568;

    white-space: nowrap;

}


/* =====================================================
   ACCIONES
===================================================== */

.acciones {

    display: flex;

    gap: 8px;

}

.btn-editar {

    background: #ff9f1c;

    color: white;

    border: none;

    width: 44px;

    height: 40px;

    border-radius: 10px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    transition: .2s;

}

.btn-editar:hover {

    background: #e58b0c;

    color: white;

    transform: translateY(-1px);

}

.btn-eliminar {

    background: #f25f63;

    color: white;

    border: none;

    width: 44px;

    height: 40px;

    border-radius: 10px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    transition: .2s;

}

.btn-eliminar:hover {

    background: #dc4147;

    color: white;

    transform: translateY(-1px);

}


/* =====================================================
   SIN RESULTADOS
===================================================== */

.sin-resultados {

    text-align: center;

    padding: 50px 20px;

    color: #718096;

}

.sin-resultados i {

    font-size: 45px;

    color: #cbd5e1;

    margin-bottom: 15px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 800px) {

    .instituciones-header {

        flex-direction: column;

        align-items: flex-start;

    }

    .btn-nueva {

        width: 100%;

        text-align: center;

    }

    .tabla-header {

        flex-direction: column;

        align-items: stretch;

    }

    .buscador {

        width: 100%;

    }

}

</style>


<div class="instituciones-container">


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div class="instituciones-header">

        <div>

            <h1 class="instituciones-titulo">

                <i class="fas fa-university me-2"></i>

                Instituciones Externas

            </h1>

            <div class="instituciones-subtitulo">

                Administra las instituciones utilizadas
                en los registros de visitantes y préstamos.

            </div>

        </div>


        <a
            href="add.php"
            class="btn-nueva"
        >

            <i class="fas fa-plus me-1"></i>

            Nueva Institución

        </a>

    </div>


    <!-- =================================================
         RESUMEN
    ================================================== -->

    <div class="resumen-card">

        <div class="resumen-icon">

            <i class="fas fa-university"></i>

        </div>

        <div>

            <div class="resumen-numero">

                <?php

                echo number_format(
                    $totalInstituciones
                );

                ?>

            </div>

            <div class="resumen-texto">

                Instituciones registradas

            </div>

        </div>

    </div>


    <!-- =================================================
         TABLA
    ================================================== -->

    <div class="tabla-card">


        <div class="tabla-header">

            <div>

                <h5>

                    <i class="fas fa-list me-2"></i>

                    Listado de Instituciones

                </h5>

                <p>

                    Instituciones externas registradas
                    en Biblioteca UPH.

                </p>

            </div>


            <div class="buscador">

                <i class="fas fa-search"></i>

                <input
                    type="text"
                    id="buscarInstitucion"
                    class="form-control"
                    placeholder="Buscar institución..."
                >

            </div>

        </div>


        <div class="tabla-wrapper">


            <?php if ($result && $result->num_rows > 0): ?>


                <table
                    class="table table-striped table-hover"
                    id="tabla-instituciones"
                >

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Institución
                            </th>

                            <th>
                                Descripción
                            </th>

                            <th>
                                Fecha de Creación
                            </th>

                            <th>
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php while (
                            $institucion =
                            $result->fetch_assoc()
                        ): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <span
                                        class="badge-id"
                                    >

                                        #<?php
                                        echo (int)
                                            $institucion['id'];
                                        ?>

                                    </span>

                                </td>


                                <!-- NOMBRE -->

                                <td>

                                    <div
                                        class="nombre-institucion"
                                    >

                                        <i
                                            class="fas fa-building me-1"
                                        ></i>

                                        <?php

                                        echo htmlspecialchars(
                                            $institucion['nombre'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );

                                        ?>

                                    </div>

                                </td>


                                <!-- DESCRIPCIÓN -->

                                <td>

                                    <div
                                        class="descripcion"
                                    >

                                        <?php

                                        echo !empty(
                                            $institucion[
                                                'descripcion'
                                            ]
                                        )
                                            ? htmlspecialchars(
                                                $institucion[
                                                    'descripcion'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            : 'Sin descripción';

                                        ?>

                                    </div>

                                </td>


                                <!-- FECHA -->

                                <td>

                                    <span
                                        class="fecha"
                                    >

                                        <i
                                            class="far fa-calendar-alt me-1"
                                        ></i>

                                        <?php

                                        if (
                                            !empty(
                                                $institucion[
                                                    'fecha_creacion'
                                                ]
                                            )
                                        ) {

                                            echo date(
                                                'd/m/Y H:i',
                                                strtotime(
                                                    $institucion[
                                                        'fecha_creacion'
                                                    ]
                                                )
                                            );

                                        } else {

                                            echo 'No registrada';

                                        }

                                        ?>

                                    </span>

                                </td>


                                <!-- ACCIONES -->

                                <td>

                                    <div
                                        class="acciones"
                                    >


                                        <!-- EDITAR -->

                                        <a
                                            href="edit.php?id=<?php
                                                echo (int)
                                                    $institucion['id'];
                                            ?>"
                                            class="btn-editar"
                                            title="Editar institución"
                                        >

                                            <i
                                                class="fas fa-edit"
                                            ></i>

                                        </a>


                                        <!-- ELIMINAR -->

                                        <a
                                            href="delete.php?id=<?php
                                                echo (int)
                                                    $institucion['id'];
                                            ?>"
                                            class="btn-eliminar btn-eliminar-institucion"
                                            title="Eliminar institución"
                                        >

                                            <i
                                                class="fas fa-trash"
                                            ></i>

                                        </a>


                                    </div>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    </tbody>

                </table>


            <?php else: ?>


                <div class="sin-resultados">

                    <i
                        class="fas fa-university d-block"
                    ></i>

                    <h5>

                        No hay instituciones registradas

                    </h5>

                    <p>

                        Agrega la primera institución
                        utilizando el botón
                        <strong>Nueva Institución</strong>.

                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>


</div>


<script>

// =====================================================
// BUSCADOR
// =====================================================

document
    .getElementById('buscarInstitucion')
    ?.addEventListener(
        'keyup',
        function () {

            const texto =
                this.value.toLowerCase();

            const filas =
                document.querySelectorAll(
                    '#tabla-instituciones tbody tr'
                );


            filas.forEach(
                function (fila) {

                    const contenido =
                        fila.textContent
                            .toLowerCase();

                    fila.style.display =
                        contenido.includes(texto)
                            ? ''
                            : 'none';

                }
            );

        }
    );


// =====================================================
// CONFIRMACIÓN ELIMINAR
// =====================================================

document
    .querySelectorAll(
        '.btn-eliminar-institucion'
    )
    .forEach(
        function (boton) {

            boton.addEventListener(
                'click',
                function (evento) {

                    evento.preventDefault();

                    const url =
                        this.href;


                    if (
                        typeof Swal !== 'undefined'
                    ) {

                        Swal.fire({

                            icon: 'warning',

                            title:
                                '¿Eliminar institución?',

                            text:
                                'Esta acción no se puede deshacer.',

                            showCancelButton: true,

                            confirmButtonText:
                                'Sí, eliminar',

                            cancelButtonText:
                                'Cancelar',

                            confirmButtonColor:
                                '#f25f63',

                            cancelButtonColor:
                                '#6c757d'

                        }).then(
                            function (resultado) {

                                if (
                                    resultado.isConfirmed
                                ) {

                                    window.location.href =
                                        url;

                                }

                            }
                        );

                    } else {

                        if (
                            confirm(
                                '¿Desea eliminar esta institución?'
                            )
                        ) {

                            window.location.href =
                                url;

                        }

                    }

                }
            );

        }
    );

</script>


<?php

include '../includes/footer.php';

?>