<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../controllers/admins.controller.php';

class FakeTemplate
{
    public function genPassword($len)
    {
        return "ABC12345678";
    }
    public function sendEmail($subject, $email, $title, $msg, $url)
    {
        return "ok";
    }
}

class ResetPasswordTest extends TestCase{
    public function testResetPasswordExito(){
        $controller = new AdminsController();

        // --- MOCK REQUEST ---
        $fakeRequest = function ($method, $url, $fields) {
            if (str_contains($url, "linkTo=email_admin")) {
                return json_decode(json_encode([
                    "status" => 200,
                    "results" => [
                        (object) ["id_admin" => 10]
                    ]
                ]));
            }

            if ($method === "PUT") {
                return json_decode(json_encode(["status" => 200]));
            }
        };

        $fakeTemplate = new FakeTemplate();

        // Ejecutar prueba
        $response = $controller->resetPasswordTestable(
            "test@example.com",
            $fakeRequest,
            $fakeTemplate
        );

        // Validaciones
        $this->assertEquals("success", $response["status"]);
        $this->assertEquals("ABC12345678", $response["newPassword"]);
    }

    public function testResetPasswordCorreoNoExiste(){
        $controller = new AdminsController();

        $fakeRequest = function ($method, $url, $fields) {
            return json_decode(json_encode(["status" => 404]));
        };

        $fakeTemplate = new FakeTemplate();

        $response = $controller->resetPasswordTestable(
            "noexiste@test.com",
            $fakeRequest,
            $fakeTemplate
        );

        $this->assertEquals("error", $response["status"]);
        $this->assertEquals("no-existe", $response["message"]);
    }

    public function testResetPasswordFallaEnvioCorreo(){
        $controller = new AdminsController();

        // --- MOCK REQUEST ---
        $fakeRequest = function ($method, $url, $fields) {

            // Caso 1: Email sí existe en base de datos
            if (str_contains($url, "linkTo=email_admin")) {
                return json_decode(json_encode([
                    "status" => 200,
                    "results" => [
                        (object) ["id_admin" => 10]
                    ]
                ]));
            }

            // Caso 2: Contraseña actualizada correctamente
            if ($method === "PUT") {
                return json_decode(json_encode([
                    "status" => 200
                ]));
            }

            return null;
        };

        // --- MOCK TEMPLATE: falla envío de correo ---
        $fakeTemplate = new class {
            public function genPassword($len)
            {
                return "ABC12345678"; // Controlado para test
            }

            public function sendEmail($subject, $email, $title, $msg, $url)
            {
                return "SMTP ERROR: Invalid credentials"; // Fuerza fallo
            }
        };

        // Ejecutar función testable
        $response = $controller->resetPasswordTestable(
            "test@example.com",
            $fakeRequest,
            $fakeTemplate
        );

        // --- VALIDACIONES ---
        $this->assertEquals("error", $response["status"]);
        $this->assertEquals("email-error", $response["message"]);
    }
}
// Ruta del reporte recién generado
$latestReport = "reports/unit-report.html";

if (file_exists($latestReport)) {

    $timestamp = date("Y-m-d_H-i-s");
    $newName = "reports/history/report_$timestamp.html";

    // Copiar para crear historial
    copy($latestReport, $newName);
}