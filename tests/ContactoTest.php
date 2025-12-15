<?php
use PHPUnit\Framework\TestCase;

// Creamos una versión falsa del controlador para evitar llamadas reales
class FakeCurlController {

    public static function request($url, $method, $fields = null) {

        // Simular datos recibidos (si existen)
        $data = null;
        if (!empty($fields)) {
            parse_str($fields, $parsed);
            $data = json_decode(json_encode($parsed));
        }

        // Simulación según método

        // Crear contacto
        if ($method === "POST") {
            $fakeResponse = [
                "status" => 200,
                "results" => [
                    "lastId" => 1000,
                    "action" => "created",
                    "comment" => "Simulated process completed",
                    "received_data" => $data
                ]
            ];
        }

        // Editar contacto
        else if ($method === "PUT") {
            $fakeResponse = [
                "status" => 200,
                "results" => [
                    "updated" => true,
                    "action" => "updated",
                    "received_data" => $data
                ]
            ];
        }

        // Eliminar contacto
        else if ($method === "DELETE") {
            $fakeResponse = [
                "status" => 200,
                "results" => [
                    "deleted" => true,
                    "action" => "deleted",
                    "id_deleted" => $data->id_contact ?? null
                ]
            ];
        }

        return json_decode(json_encode($fakeResponse));
    }
}


class ContactoTest extends TestCase {

    public function testCrearContacto() {

        // Datos simulados que el test "envía"
        $fields = http_build_query([
            "phone_contact" => "3000009999",
            "ai_contact" => "simulated_ai",
            "date_created_contact" => "2025-02-01"
        ]);

        // Llamamos al FAKE controller → No toca la BD real
        $response = FakeCurlController::request(
            "contacts?token=no&except=id_contact",
            "POST",
            $fields
        );

        // Comprobaciones
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->status);

        // Validar que recibimos los mismos datos enviados
        $this->assertEquals("3000009999", $response->results->received_data->phone_contact);
        $this->assertEquals("simulated_ai", $response->results->received_data->ai_contact);
        $this->assertEquals("2025-02-01", $response->results->received_data->date_created_contact);

        // Validar comentario
        $this->assertEquals("Simulated process completed", $response->results->comment);
    }




    public function testEditarContacto() {

        // Datos simulados para editar
        $fields = http_build_query([
            "id_contact" => 50,
            "phone_contact" => "3001112222",
            "ai_contact" => "edited_ai",
            "date_updated" => "2025-02-05"
        ]);

        $response = FakeCurlController::request(
            "contacts?id=50",
            "PUT",
            $fields
        );

        // Validaciones
        $this->assertEquals(200, $response->status);
        $this->assertEquals("updated", $response->results->action);
        $this->assertTrue($response->results->updated);

        // Validar los datos enviados
        $this->assertEquals(50, $response->results->received_data->id_contact);
        $this->assertEquals("3001112222", $response->results->received_data->phone_contact);
        $this->assertEquals("edited_ai", $response->results->received_data->ai_contact);
        $this->assertEquals("2025-02-05", $response->results->received_data->date_updated);
    }

      public function testEliminarContacto() {

        // Solo enviamos el ID a eliminar
        $fields = http_build_query([
            "id_contact" => 50
        ]);

        $response = FakeCurlController::request(
            "contacts?id=50",
            "DELETE",
            $fields
        );

        // Validaciones
        $this->assertEquals(200, $response->status);
        $this->assertEquals("deleted", $response->results->action);
        $this->assertTrue($response->results->deleted);

        // Validar que eliminó el ID correcto
        $this->assertEquals(50, $response->results->id_deleted);
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
