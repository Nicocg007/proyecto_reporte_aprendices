<?php

require_once __DIR__ . '/env_loader.php';

// clase mailconfig con los datos del correo que envia los mensajes
class MailConfig {

    public $correo;
    public $password;
    public $nombre;
    public $smtp;
    public $puerto;

    public function __construct() {
        $env = cargarEnv();
        $this->correo = $env['mail_correo'] ?? '';
        $this->password = $env['mail_password'] ?? '';
        $this->nombre = $env['mail_nombre'] ?? 'SENA Control de Asistencia';
        $this->smtp = $env['mail_smtp'] ?? 'smtp.gmail.com';
        $this->puerto = (int)($env['mail_puerto'] ?? 587);
    }
}

// funcion que envia un correo con phpmailer
// recibe el correo destino, el asunto y el mensaje
// devuelve true si se envio o false si fallo
function enviarCorreo($correo_destino, $asunto, $mensaje) {

    require_once __DIR__ . '/../vendor/autoload.php';

    $config = new MailConfig();

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config->smtp;
        $mail->SMTPAuth = true;
        $mail->Username = $config->correo;
        $mail->Password = $config->password;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config->puerto;

        $mail->setFrom($config->correo, $config->nombre);
        $mail->addAddress($correo_destino);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;
        $mail->AltBody = strip_tags($mensaje);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// plantilla para el correo del codigo de verificacion
function mensajeHtml($codigo, $nombre) {
    $nombre_seguro = htmlspecialchars((string)$nombre, ENT_QUOTES, 'UTF-8');
    $codigo_seguro = htmlspecialchars((string)$codigo, ENT_QUOTES, 'UTF-8');
    return '
    <div style="font-family: Arial, sans-serif; max-width: 520px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden;">
        <div style="background-color: #004990; padding: 22px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 22px;">SENA Control de Asistencia</h1>
        </div>
        <div style="padding: 26px; background-color: #ffffff;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 6px;">Hola ' . $nombre_seguro . ',</p>
            <p style="color: #334155; font-size: 15px; margin: 0;">Recibimos una solicitud para cambiar tu contraseña. Usa este código:</p>
            <div style="background-color: #f1f5f9; border-radius: 10px; padding: 18px; text-align: center; margin: 20px 0;">
                <span style="font-size: 34px; font-weight: bold; letter-spacing: 10px; color: #0f766e;">' . $codigo_seguro . '</span>
            </div>
            <p style="color: #64748b; font-size: 13px; margin: 0 0 6px;">El código expira en unos minutos.</p>
            <p style="color: #64748b; font-size: 13px; margin: 0;">Si no fuiste tú quien pidió el cambio, ignora este correo.</p>
        </div>
        <div style="background-color: #f8fafc; padding: 12px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="color: #94a3b8; font-size: 12px; margin: 0;">SENA - Sistema de Control de Asistencia</p>
        </div>
    </div>';
}
