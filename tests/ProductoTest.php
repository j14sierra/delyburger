<?php
use PHPUnit\Framework\TestCase;

class FakeeCurlController {

    public static function request($url, $method, $fields) {

        // Para UPDATE y DELETE, los datos pueden venir como string.
        parse_str($fields, $parsed);
        $parsed = json_decode(json_encode($parsed));

        if ($method === "POST") {

            return json_decode(json_encode([
                "status" => 200,
                "results" => [
                    "lastId" => 230,
                    "comment" => "Producto creado correctamente",
                    "received_data" => $parsed
                ]
            ]));
        }

        if ($method === "PUT") {

            return json_decode(json_encode([
                "status" => 200,
                "results" => [
                    "updated" => true,
                    "comment" => "Producto actualizado correctamente",
                    "received_data" => $parsed
                ]
            ]));
        }

        if ($method === "DELETE") {

            return json_decode(json_encode([
                "status" => 200,
                "results" => [
                    "deleted" => true,
                    "comment" => "Producto eliminado correctamente",
                    "id" => $parsed->id ?? null
                ]
            ]));
        }

        return null;
    }
}


class ProductoTest extends TestCase {

    /** ========================
     *   TEST → CREAR PRODUCTO
     *  =======================*/
    public function testCrearProducto() {

        $fields = http_build_query([
            "name_product" => "Hamburguesa Doble",
            "price_product" => "25000",
            "stock_product" => "10"
        ]);

        $response = FakeeCurlController::request(
            "products?token=no&except=id_product",
            "POST",
            $fields
        );

        $this->assertNotNull($response);
        $this->assertEquals(200, $response->status);

        $this->assertEquals("Hamburguesa Doble", $response->results->received_data->name_product);
        $this->assertEquals("25000", $response->results->received_data->price_product);
        $this->assertEquals("10", $response->results->received_data->stock_product);

        $this->assertEquals(230, $response->results->lastId);
        $this->assertEquals("Producto creado correctamente", $response->results->comment);
    }



    /** ========================
     *   TEST → EDITAR PRODUCTO
     *  =======================*/
    public function testEditarProducto() {

        $fields = http_build_query([
            "name_product" => "Hamburguesa Triple",
            "price_product" => "32000",
            "stock_product" => "5"
        ]);

        $response = FakeeCurlController::request(
            "products?id=230&nameId=id_product",
            "PUT",
            $fields
        );

        $this->assertEquals(200, $response->status);
        $this->assertTrue($response->results->updated);

        $this->assertEquals("Hamburguesa Triple", $response->results->received_data->name_product);
        $this->assertEquals("32000", $response->results->received_data->price_product);
        $this->assertEquals("5", $response->results->received_data->stock_product);
    }



    /** ========================
     *   TEST → ELIMINAR PRODUCTO
     *  =======================*/
    public function testEliminarProducto() {

        $fields = "id=230";

        $response = FakeeCurlController::request(
            "products?id=230&nameId=id_product",
            "DELETE",
            $fields
        );

        $this->assertEquals(200, $response->status);
        $this->assertTrue($response->results->deleted);

        $this->assertEquals(230, $response->results->id);
        $this->assertEquals("Producto eliminado correctamente", $response->results->comment);
    }
}
