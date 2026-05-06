<?php
    session_start();
    include("../../config/conection.php");

    header("Content-Type: application/json");

    $con = conection();

    // ==============================
    // VALIDAR SESIÓN
    // ==============================
    if (!isset($_SESSION['usuarios'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Sesión no válida. Inicia sesión nuevamente."
        ]);
        exit();
    }

    // ==============================
    // VALIDAR MÉTODO POST
    // ==============================
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo json_encode([
            "status" => "error",
            "message" => "Solicitud no permitida."
        ]);
        exit();
    }

    // ==============================
    // RECIBIR Y LIMPIAR DATOS
    // ==============================
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : "";
    $imagen = $_POST['imagen'];

    // ==============================
    // VALIDAR DATOS VACÍOS
    // ==============================
    if ($id <= 0 || empty($nombre)) {
        echo json_encode([
            "status" => "error",
            "message" => "Todos los campos son obligatorios."
        ]);
        exit();
    }

    // ==============================
    // VALIDAR QUE NO SE REPITA EL NOMBRE
    // ==============================
    $sqlVerificar = "SELECT idCategoria 
                    FROM categoria 
                    WHERE nombreCategoria = ? 
                    AND idCategoria != ?";

    $stmt = $con->prepare($sqlVerificar);
    $stmt->bind_param("si", $nombre, $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Ya existe una categoría con ese nombre."
        ]);
        exit();
    }

    // ==============================
    // ACTUALIZAR CATEGORÍA
    // ==============================
    $sqlActualizar = "UPDATE categoria 
                    SET nombreCategoria = ?,
                    imagenCategoria='$imagen' 
                    WHERE idCategoria = ?";

    $stmtUpdate = $con->prepare($sqlActualizar);
    $stmtUpdate->bind_param("si", $nombre, $id);

    if ($stmtUpdate->execute()) {

        echo json_encode([
            "status" => "success",
            "message" => "Categoría actualizada correctamente."
        ]);

    } else {

        echo json_encode([
            "status" => "error",
            "message" => "Error al actualizar la categoría."
        ]);
    }

    // ==============================
    // CERRAR CONEXIÓN
    // ==============================
    $stmt->close();
    $stmtUpdate->close();
    $con->close();
?>
