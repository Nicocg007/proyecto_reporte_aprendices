<?php

// clase mailconfig con los datos del correo que envia los mensajes
class MailConfig {

    // correo gmail que envia los mensajes
    public $correo = 'adso3234082@gmail.com';

    // contraseña de aplicacion creada en google, no es la contraseña normal
    public $password = 'rbxl qsyg merc afyt';

    // nombre que aparece como remitente
    public $nombre = 'SENA Control de Asistencia';

    // servidor smtp de gmail
    public $smtp = 'smtp.gmail.com';

    // puerto seguro para tls
    public $puerto = 587;
}

// funcion que envia un correo con phpmailer
// recibe el correo destino, el asunto y el mensaje
// devuelve true si se envio o false si fallo
function enviarCorreo($correo_destino, $asunto, $mensaje) {

    // cargar phpmailer
    require_once __DIR__ . '/../vendor/autoload.php';

    // datos del correo que envia
    $config = new MailConfig();

    // crear el objeto de phpmailer
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // usar smtp para el envio
        $mail->isSMTP();
        $mail->Host = $config->smtp;
        $mail->SMTPAuth = true;
        $mail->Username = $config->correo;
        $mail->Password = $config->password;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config->puerto;

        // datos del mensaje
        $mail->setFrom($config->correo, $config->nombre);
        $mail->addAddress($correo_destino);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;
        // version en texto plano por si el correo no soporta html
        $mail->AltBody = strip_tags($mensaje);

        // enviar y avisar que funciono
        $mail->send();
        return true;
    } catch (Exception $e) {
        // si fallo algo devuelve false
        return false;
    }
}

// plantilla para el correo del codigo de verificacion
// devuelve un html bonito con el codigo en grande
function mensajeHtml($codigo, $nombre) {
    return '
    <div style="font-family: Arial, sans-serif; max-width: 520px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden;">
        <div style="background-color: #004990; padding: 22px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 22px;">SENA Control de Asistencia</h1>
        </div>
        <div style="padding: 26px; background-color: #ffffff;">
            <p style="color: #334155; font-size: 15px; margin: 0 0 6px;">Hola ' . $nombre . ',</p>
            <p style="color: #334155; font-size: 15px; margin: 0;">Recibimos una solicitud para cambiar tu contraseña. Usa este código:</p>
            <div style="background-color: #f1f5f9; border-radius: 10px; padding: 18px; text-align: center; margin: 20px 0;">
                <span style="font-size: 34px; font-weight: bold; letter-spacing: 10px; color: #0f766e;">' . $codigo . '</span>
            </div>
            <p style="color: #64748b; font-size: 13px; margin: 0 0 6px;">El código expira en unos minutos.</p>
            <p style="color: #64748b; font-size: 13px; margin: 0;">Si no fuiste tú quien pidió el cambio, ignora este correo.</p>
        </div>
        <div style="background-color: #f8fafc; padding: 12px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="color: #94a3b8; font-size: 12px; margin: 0;">SENA - Sistema de Control de Asistencia</p>
        </div>
    </div>';
}