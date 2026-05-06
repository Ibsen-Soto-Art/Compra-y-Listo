<?php
session_start();
include("../config/conection.php");
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode(["status"=>"error","message"=>"Método no permitido"]);
    exit;
}

$correo = trim($_POST['correo'] ?? '');

if(empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)){
    echo json_encode(["status"=>"error","message"=>"Correo inválido"]);
    exit;
}

$con  = conection();
$stmt = mysqli_prepare($con, "SELECT idUsuario, nombreUsuario FROM usuarios WHERE correo = ?");
mysqli_stmt_bind_param($stmt, "s", $correo);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if(!$row){
    echo json_encode(["status"=>"error","message"=>"No existe ninguna cuenta registrada con ese correo."]);
    exit;
}

// Generar código de 6 dígitos
$codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Guardar en sesión con expiración de 10 minutos
$_SESSION['reset_correo']     = $correo;
$_SESSION['reset_codigo']     = $codigo;
$_SESSION['reset_expiry']     = time() + 600;
$_SESSION['reset_nombre']     = $row['nombreUsuario'];
$_SESSION['reset_verificado'] = false;

// Enviar correo
include('../config/mailer.php');

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USUARIO;
    $mail->Password   = MAIL_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(MAIL_FROM, MAIL_NOMBRE);
    $mail->addAddress($correo, $row['nombreUsuario']);

    $mail->isHTML(true);
    $mail->Subject = 'Código de verificación — Compra y Listo';
    $mail->Body    = '
    <!DOCTYPE html>
    <html>
    <body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr><td align="center" style="padding:40px 20px;">
          <table width="520" cellpadding="0" cellspacing="0"
                 style="background:#fff;border-radius:16px;overflow:hidden;
                        box-shadow:0 4px 24px rgba(0,0,0,.08);">
            <!-- Header -->
            <tr><td style="background:linear-gradient(135deg,#0f2d0a,#2E8B57);
                            padding:32px 40px;text-align:center;">
              <h1 style="margin:0;color:#fff;font-size:22px;font-weight:800;">
                🔐 Restablecer contraseña
              </h1>
              <p style="margin:8px 0 0;color:rgba(255,255,255,.8);font-size:14px;">
                Compra y Listo
              </p>
            </td></tr>
            <!-- Body -->
            <tr><td style="padding:36px 40px;">
              <p style="margin:0 0 12px;color:#374151;font-size:15px;">
                Hola <strong>' . htmlspecialchars($row['nombreUsuario']) . '</strong>,
              </p>
              <p style="margin:0 0 28px;color:#6b7280;font-size:14px;line-height:1.6;">
                Recibimos una solicitud para restablecer tu contraseña.
                Usa el siguiente código de verificación. Expira en <strong>10 minutos</strong>.
              </p>
              <!-- Código -->
              <div style="background:#f0fdf4;border:2px dashed #86efac;border-radius:12px;
                          padding:28px;text-align:center;margin-bottom:28px;">
                <span style="font-size:42px;font-weight:900;letter-spacing:14px;
                             color:#15803d;font-family:monospace;">' . $codigo . '</span>
              </div>
              <p style="margin:0;color:#9ca3af;font-size:12px;text-align:center;">
                Si no solicitaste esto, ignora este correo. Tu contraseña no cambiará.
              </p>
            </td></tr>
            <!-- Footer -->
            <tr><td style="background:#f8fafc;padding:16px 40px;text-align:center;
                            border-top:1px solid #f1f5f9;">
              <p style="margin:0;color:#9ca3af;font-size:12px;">
                © 2026 Compra y Listo · Florencia, Caquetá
              </p>
            </td></tr>
          </table>
        </td></tr>
      </table>
    </body>
    </html>';

    $mail->AltBody = "Hola {$row['nombreUsuario']},\n\nTu código de verificación es: $codigo\n\nExpira en 10 minutos.\n\nSi no solicitaste esto, ignora este correo.";

    $mail->send();
    echo json_encode(["status"=>"success","message"=>"Código enviado a tu correo"]);

} catch(Exception $e){
    echo json_encode(["status"=>"error","message"=>"Error al enviar correo: ".$mail->ErrorInfo]);
}
?>
