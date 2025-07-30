<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MasiveEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $mensaje;
    public $asunto;
    public $investorId;

    public function __construct($mensaje, $asunto, $investorId)
    {
        $this->mensaje = $mensaje;
        $this->asunto = $asunto;
        $this->investorId = $investorId;
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject($this->asunto)
                    ->html($this->buildEmailHtml());
    }

    private function buildEmailHtml()
    {
        $editUrl = 'https://zuma.com.pe/inversionistas/editar/' . $this->investorId;
        
        return '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($this->asunto) . '</title>
            <style>
                .btn-completar {
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white !important;
                    padding: 15px 30px;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: bold;
                    font-size: 16px;
                    text-align: center;
                    margin: 20px 0;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                    transition: all 0.3s ease;
                }
                .btn-completar:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
                }
                .content-section {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <table style="background-color: #f0f1f9; max-width: 780px; width: 100%; padding: 2% 4%; margin: 0 auto;">
                <tr>
                    <td>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <td style="width: 20%;"></td>
                                    <td style="text-align: center; width: 60%;">
                                        <img src="https://zuma.com.pe/factoring/email/autenthication/log-zuma.png" alt="Zuma" width="30%">
                                    </td>
                                    <td style="width: 20%;"></td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="width: 20%;">
                                        <img src="https://zuma.com.pe/factoring/email/autenthication/dola-2.png" alt="" width="70%">
                                    </td>
                                    <td style="width: 60%; padding: 20px 0;">
                                        <div class="content-section">
                                            <table style="text-align: center; width: 100%;">
                                                <tr>
                                                    <td style="text-align: center; padding: 20px 0;">
                                                        <a href="' . $editUrl . '" class="btn-completar" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white !important; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; display: inline-block; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
                                                            🔗 Presiona aquí para completar tus datos
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="font-size: 14px; color: #777; padding-top: 20px; text-align: center;">
                                                        <p style="margin: 5px 0;">Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
                                                        <p style="margin: 5px 0; word-break: break-all;">
                                                            <a href="' . $editUrl . '" style="color: #667eea; text-decoration: underline;">' . $editUrl . '</a>
                                                        </p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                    <td style="text-align: end;">
                                        <img src="https://zuma.com.pe/factoring/email/autenthication/simbolo-dolar-varios.png" alt="" width="70%">
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="padding-top: 30px;">
                                        <table style="width: 100%; text-align: center;">
                                            <tr>
                                                <td style="font-size: 13px; color: #555; padding-bottom: 10px;">
                                                    Si recibiste este correo por error, simplemente ignóralo.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 14px; color: black; padding-top: 10px;">
                                                    Atentamente,<br>El equipo de ZUMA
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 12px; color: #888; padding-top: 20px; border-top: 1px solid #ddd; margin-top: 20px;">
                                                    <p>Este es un correo automático, por favor no responder directamente.</p>
                                                    <p>Para soporte técnico, contacta a: soporte@zuma.com.pe</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }
}