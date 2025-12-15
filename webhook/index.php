<?php

/*=============================================
Depurar Errores
=============================================*/

define('DIR',__DIR__);

ini_set("display_errors", 1);
ini_set("log_errors", 1);
ini_set("error_log", DIR."/php_error_log");

/*=============================================
Controladores
=============================================*/

require_once "../controllers/curl.controller.php"; 
require_once "../controllers/clients.controller.php";
require_once "../controllers/bots.controller.php";

/*=============================================
TOKEN que configuras en la plataforma de Meta
=============================================*/

$token = "aasdaag4stg3214654as";

if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["hub_verify_token"])){

	if($_GET["hub_verify_token"] == $token){

		echo $_GET["hub_challenge"];

		exit;
	
	}else{

		echo "Token inválido";
        exit;
	}

}

/*=============================================
Recibir respuesta de la API de WS
=============================================*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	date_default_timezone_set("America/Bogota");

	/*=============================================
    // Obtén el contenido JSON
    =============================================*/
   
    $input = file_get_contents('php://input');

    file_put_contents("webhook_log.txt", $input."\n\n", FILE_APPEND); 

  	/*=============================================
	Convierte el JSON a array asociativo
	=============================================*/

	$data = json_decode($input);
	// echo '<pre>$data '; print_r($data); echo '</pre>';

	// return;

	/*=============================================
	Variables iniciales
	=============================================*/

	$type_message = null;
	$status_message = null;
	$id_whatsapp_message = null;
	$client_message	= null;
	$phone_message	= null;
	$order_message = 0;
	$type_conversation = null;

	/*=============================================
	Tipo de mensajes
	=============================================*/

	if(isset($data->entry[0]->changes[0]->value->messages)){

		$type_message = "client";

	}

	if(isset($data->entry[0]->changes[0]->value->statuses)){

		$type_message = "business";
		$status_message = $data->entry[0]->changes[0]->value->statuses[0]->status;
		
		
	}

	// echo '<pre>$status_message '; print_r($status_message); echo '</pre>';
	// echo '<pre>$type_message '; print_r($type_message); echo '</pre>';

	// return;

	/*=============================================
	Capturar la API Cloud
	=============================================*/

	$url = "whatsapps?linkTo=id_number_whatsapp&equalTo=".$data->entry[0]->changes[0]->value->metadata->phone_number_id;
	$method = "GET";
	$fields = array();
	$curl = new CurlController();
	$getApiWS = $curl->request($url, $method, $fields);

	

	if($getApiWS->status == 200){

		$getApiWS = $getApiWS->results[0];
		$id_whatsapp_message = $getApiWS->id_whatsapp;
		
	}

	echo '<pre>$id_whatsapp_message '; print_r($id_whatsapp_message); echo '</pre>';

	/*=============================================
	Capturar mensaje del cliente
	=============================================*/

	if($type_message == "client"){

		$phone_message = $data->entry[0]->changes[0]->value->messages[0]->from;

		/*=============================================
		Cuando capturamos un texto
		=============================================*/

		if(isset($data->entry[0]->changes[0]->value->messages[0]->text)){
			
			$client_message = $data->entry[0]->changes[0]->value->messages[0]->text->body;
			
			$type_conversation = "text";
		}

		/*=============================================
		Capturando una imagen
		=============================================*/

		if(isset($data->entry[0]->changes[0]->value->messages[0]->image)){

			if(isset($data->entry[0]->changes[0]->value->messages[0]->image->caption)){

				$caption = $data->entry[0]->changes[0]->value->messages[0]->image->caption;
			
			}else{

				$caption = "";
			}

			$client_message = '{"type":"image","mime":"'.$data->entry[0]->changes[0]->value->messages[0]->image->mime_type.'","id":"'.$data->entry[0]->changes[0]->value->messages[0]->image->id.'","caption":"'.$caption.'"}';
			
			$type_conversation = "image";
		}

		/*=============================================
		Capturando un video
		=============================================*/

		if(isset($data->entry[0]->changes[0]->value->messages[0]->video)){

			if(isset($data->entry[0]->changes[0]->value->messages[0]->video->caption)){

				$caption = $data->entry[0]->changes[0]->value->messages[0]->video->caption;
			
			}else{
				
				$caption = "";
			}

			$client_message = '{"type":"video","mime":"'.$data->entry[0]->changes[0]->value->messages[0]->video->mime_type.'","id":"'.$data->entry[0]->changes[0]->value->messages[0]->video->id.'","caption":"'.$caption.'"}';

			$type_conversation = "video";
		}

		/*=============================================
		Capturando un audio
		=============================================*/

		if(isset($data->entry[0]->changes[0]->value->messages[0]->audio)){

			$client_message = '{"type":"audio","mime":"'.$data->entry[0]->changes[0]->value->messages[0]->audio->mime_type.'","id":"'.$data->entry[0]->changes[0]->value->messages[0]->audio->id.'"}';
			
			$type_conversation = "audio";

		}

		/*=============================================
		Capturando un documento
		=============================================*/

		if(isset($data->entry[0]->changes[0]->value->messages[0]->document)){

			if(isset($data->entry[0]->changes[0]->value->messages[0]->document->caption)){

				$caption = $data->entry[0]->changes[0]->value->messages[0]->document->caption;
			
			}else{
				
				$caption = "";
			}

			$client_message = '{"type":"document","mime":"'.$data->entry[0]->changes[0]->value->messages[0]->document->mime_type.'","id":"'.$data->entry[0]->changes[0]->value->messages[0]->document->id.'","caption":"'.$caption.'"}';

			$type_conversation = "document";
		}
		
		
		
		echo '<pre>$client_message '; print_r($client_message); echo '</pre>';
		echo '<pre>$phone_message '; print_r($phone_message); echo '</pre>';

		/*=============================================
		Llevar el orden de los mensajes
		=============================================*/

		$url = "messages?linkTo=phone_message&equalTo=".$phone_message."&startAt=0&endAt=1&orderBy=id_message&orderMode=DESC";
		$curl = new CurlController();
		$getMessages = $curl->request($url, $method, $fields);

		
		
		if($getMessages->status == 200){

			$order_message = $getMessages->results[0]->order_message + 1;
		
		}

		/*=============================================
		Guardar mensaje del cliente
		=============================================*/

		$url = "messages?token=no&except=id_message";
		$method = "POST";
		$fields = array(
			"type_message" => $type_message,
			"id_whatsapp_message" => $id_whatsapp_message,
			"client_message" => $client_message,
			"phone_message" => $phone_message,
			"order_message" => $order_message,
			"date_created_message" => date("Y-m-d")
		);
		$curl = new CurlController();
		$saveMessage = $curl->request($url, $method, $fields);

		
		if($saveMessage->status == 200){

			/*=============================================
			Responder al cliente
			=============================================*/

			$responseClients = ClientsController::responseClients($getApiWS,$phone_message,$order_message,$type_conversation);
			echo '<pre>$responseClients '; print_r($responseClients); echo '</pre>';
			
		}
		
	}

	/*=============================================
	Capturar mensaje del negocio
	=============================================*/

	if($type_message == "business" && $status_message == "sent"){

		/*=============================================
		Capturar el número de teléfono
		=============================================*/

		$phone_message = $data->entry[0]->changes[0]->value->statuses[0]->recipient_id;
		echo '<pre>$phone_message '; print_r($phone_message); echo '</pre>';

		/*=============================================
		Capturar id conversación
		=============================================*/

		$idConversation = $data->entry[0]->changes[0]->value->statuses[0]->conversation->id;
		echo '<pre>$idConversation '; print_r($idConversation); echo '</pre>';

		/*=============================================
		Capturar fecha de vencimiento
		=============================================*/

		$expireConversation = $data->entry[0]->changes[0]->value->statuses[0]->conversation->expiration_timestamp;
		$expireConversation = new DateTime("@$expireConversation");
		$expireConversation = $expireConversation->format('Y-m-d H:i:s');
		echo '<pre>$expireConversation '; print_r($expireConversation); echo '</pre>';

		/*=============================================
		Traer la última respuesta del negocio
		=============================================*/

		$url = "messages?linkTo=type_message,phone_message&equalTo=business,".$phone_message."&orderBy=id_message&orderMode=DESC&startAt=0&endAt=1";
		$method  = "GET";
		$fields = array();
		$curl = new CurlController();
		$getMessage = $curl->request($url, $method, $fields);


		if($getMessage->status == 200){

			$getMessage = $getMessage->results[0];
			
			/*=============================================
			Actualizar última respuesta del negocio
			=============================================*/

			$url = "messages?id=".$getMessage->id_message."&nameId=id_message&token=no&except=id_message";
	        $method = "PUT";
	        $fields = array(
	        	"id_conversation_message" => $idConversation,
	        	"expiration_message" => $expireConversation
	        );

	        $fields = http_build_query($fields);
			$curl = new CurlController();
			$updateMessage = $curl->request($url, $method, $fields);

	        

	        if($updateMessage->status == 200){

	        	echo "todo Ok";
	        }
		}

	}

}

?>