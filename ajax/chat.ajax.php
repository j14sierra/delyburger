<?php 

define('DIR', __DIR__);

ini_set("display_errors",1);
ini_set("log_errors",1);
ini_set("error_log", DIR."/php_error_log");

date_default_timezone_set("America/Bogota");

require_once "../controllers/template.controller.php";
require_once "../controllers/curl.controller.php";

class ChatAjax{

	/*=============================================
	Revisar si hay mensajes nuevos en el chat
	=============================================*/

	public $phoneMessage;
	public $orderMessage;

	public function ajaxLastMessage(){

		/*=============================================
		Revisar si hay nuevas respuestas
		=============================================*/

		$url = "messages?linkTo=phone_message&equalTo=".$this->phoneMessage."&startAt=".$this->orderMessage."&endAt=2";
		$method = "GET";
		$fields = array();

		$getMessages = CurlController::request($url,$method,$fields);
		
		if($getMessages->status == 200){

			if($getMessages->total > 1){

				/*=============================================
				Validar si el mensaje es del cliente
				=============================================*/

				if($getMessages->results[1]->type_message == "client"){


					$message = '<div class="msg user">
									'.$getMessages->results[1]->client_message.'<br>
									<span class="small text-muted float-end mt-2">
										'.TemplateController::formatDate(6,$getMessages->results[1]->date_updated_message).'	
									</span>
								</div>';

					$response = array(
						"type" => "client",
						"message" => base64_encode($message),
						"lastOrder" => $getMessages->results[1]->order_message
					);

					echo json_encode($response);

				}

				/*=============================================
				Validar si el mensaje es del negocio
				=============================================*/

				if($getMessages->results[1]->type_message == "business"){

					$message = '<div class="msg bot">
									'.$getMessages->results[1]->business_message.'<br>
									<span class="small text-muted float-end mt-2">
										'.TemplateController::formatDate(6,$getMessages->results[1]->date_updated_message).'	
									</span>
								</div>';

					$response = array(
						"type" => "business",
						"message" => base64_encode($message),
						"lastOrder" => $getMessages->results[1]->order_message
					);

					echo json_encode($response);


				}
			}
		}

	}

	/*=============================================
	Conversaciones manuales por parte del negocio
	=============================================*/

	public $conversation;
	public $phone;
	public $token;

	public function ajaxSendMessage(){

		/*=============================================
		JSON para enviar a WS
		=============================================*/

		$json = '{
				  "messaging_product": "whatsapp",
				  "recipient_type": "individual",
				  "to": "'.$this->phone.'",
				  "type": "text",
				  "text": {
				    "preview_url": true,
				    "body": "'.$this->conversation.'"
				  }
				}';

		$business_message = $this->conversation;
		$template_message = '{"type":"manual","title":""}';

		/*=============================================
		Llevar el orden de los mensajes
		=============================================*/

		$url = "messages?linkTo=phone_message&equalTo=".$this->phone."&startAt=0&endAt=1&orderBy=id_message&orderMode=DESC";
		$method = "GET";
		$fields = array(); 

		$getMessages = CurlController::request($url,$method,$fields);
		
		if($getMessages->status == 200){

			$order_message = $getMessages->results[0]->order_message + 1;
		
		}

		/*=============================================
		Capturar API WS activa
		=============================================*/
		
		$url = "whatsapps?linkTo=status_whatsapp&equalTo=1&orderBy=id_whatsapp&orderMode=DESC&startAt=0&endAt=1";

		$getApiWS = CurlController::request($url,$method,$fields);

		if($getApiWS->status == 200){

			$getApiWS = $getApiWS->results[0];
		}


		/*=============================================
      	Guardamos la respuesta del negocio
      	=============================================*/

		$url = "messages?token=".$this->token."&table=admins&suffix=admin";
		$method = "POST";
		$fields = array(
			"type_message" => "business",
			"id_whatsapp_message" => $getApiWS->id_whatsapp,
			"business_message" => $business_message,
			"phone_message" => $this->phone,
			"order_message" => $order_message,
			"template_message" => $template_message,
			"date_created_message" => date("Y-m-d")
		);

		$saveMessage = CurlController::request($url,$method,$fields);

		if($saveMessage->status == 200){

			/*=============================================
      		Enviamos datos JSON a la API de WhatsApp
      		=============================================*/

      		$apiWS = CurlController::apiWS($getApiWS,$json);
      		echo '<pre>$apiWS '; print_r($apiWS); echo '</pre>';
		
		}

	}

	/*=============================================
	Traer último chat
	=============================================*/

	public $lastIdMessage;
	public $borderChat;

	public function ajaxLastChat(){

		/*=============================================
		Validamos que el último ID no esté en la vista
		=============================================*/
		$url = "messages?orderBy=id_message&orderMode=DESC&startAt=0&endAt=1&select=id_message";
		$method = "GET";
		$fields = array();

		$getMessage = CurlController::request($url,$method,$fields);

		if($getMessage->status == 200){

			if($this->lastIdMessage == $getMessage->results[0]->id_message){

				// echo "ya está en la vista";
				return;
			}else{

				$lastIdMessage = $getMessage->results[0]->id_message;
				// echo '<pre>'; print_r($lastIdMessage); echo '</pre>';
			}
		}

		/*=============================================
		Traemos los contactos
		=============================================*/

		$url = "contacts?orderBy=date_updated_contact&orderMode=DESC&startAt=0&endAt=10";
		$method = "GET";
		$fields = array();

		$getContacts = CurlController::request($url,$method,$fields);

		if($getContacts->status == 200){

		  $contacts = $getContacts->results;

		  foreach ($contacts as $key => $value) {

		  	/*=============================================
  		  	Traemos la conversación más reciente
  		  	=============================================*/

		  	$url = "messages?linkTo=phone_message&equalTo=".$value->phone_contact."&orderBy=id_message&orderMode=DESC&startAt=0&endAt=1";

		  	$getMessages = CurlController::request($url,$method,$fields);

		  	if($getMessages->status == 200){

			  $messages = $getMessages->results[0];

		  	}else{

		  	  $messages = null;
		  	}

		  	/*=============================================
		  	Todos los Mensajes
		  	=============================================*/

		  	if (!empty($messages)){

            	$date_updated_message = $messages->date_updated_message;

                if ($messages->type_message == "business"){

                	$textMessage = "...";
                	$bellNotification = "";

                }else{

                	$textMessage = mb_substr($messages->client_message,0,17)."...";
                	$bellNotification = "ok";

                	if($key == 0){

                		$textSuccess = "text-success";
                	
                	}else{

                		$textSuccess = "";
                	}
                    
                }

            /*=============================================
		  	Cuando no hay mensajes
		  	=============================================*/

            }else{

              $date_updated_message = $value->date_updated_contact;
              $textMessage = "";
              $bellNotification = "";
            
            }


		  	$chats = '<a href="/chat?phone='.$value->phone_contact.'_'.$value->ai_contact.'_'.$value->id_contact.'" class="text-dark" onclick="alertClick(1)">';

		  			if($this->borderChat != "" && $this->borderChat == $value->phone_contact){

		  				$chats .= '<div class="contact-item pb-0" style="border:1px solid #090">';	
		  			
		  			}else{

		  				$chats .= '<div class="contact-item pb-0">';	
		  			}

	
			        $chats .= '<div class="d-flex justify-content-between pb-0">

			              <div>
			                <span class="small">';

			                if($bellNotification == "ok"){

			                	$chats .= '<i class="bi bi-bell-fill '.$textSuccess.'"></i>';
			                }

			                $chats .= '<strong>
			                    +'.mb_substr($value->phone_contact,0,2).'
			                      '.mb_substr($value->phone_contact,2,3).'
			                      '.mb_substr($value->phone_contact,5,7).' 
			                  </strong>
			                </span>
			              </div>

			              <div>';

			            if (date("Y-m-d") == TemplateController::formatDate(8,$date_updated_message)){
			                    
			                $chats .= '<span class="small">'.TemplateController::formatDate(6,$date_updated_message).'</span>';

			            }else{

			                $chats .= '<span class="small">'.TemplateController::formatDate(5,$date_updated_message).'</span>';
			            }
			      
			             $chats .= '</div>

			            </div>

			            <div class="d-flex justify-content-between pb-0">
			              
			              <div>
			   
			                <p class="small">'.$textMessage.'</p>
			                 
			              </div>

			              <div class="custom-control custom-checkbox">
			                <input 
			                type="checkbox" 
			                class="custom-control-input mt-2 form-check-input" 
			                id="customCheck" 
			                name="example1"';
			               
			               if ($value->ai_contact == 1){

			               	$chats .= 'checked';

			               }

			               $chats .= 'style="width:18px !important; height:18px !important">

			                <label class="custom-control-label mb-1" for="customCheck">
			                  <i class="fas fa-robot text-success"></i>
			                </label>
			                
			              </div>

			            </div>

			          </div>

			        </a>';

		        $response = array(
					"chats" => base64_encode($chats),
					"lastIdMessage" => $lastIdMessage,
					"phone" => $contacts[0]->phone_contact
				);

				echo json_encode($response).",";
		    }

		}
		

		// echo '<pre>$lastIdMessage '; print_r($this->lastIdMessage); echo '</pre>';
		// echo '<pre>$phone '; print_r($this->phone); echo '</pre>';

	}



}

/*=============================================
Revisar si hay mensajes nuevos en el chat
=============================================*/

if(isset($_POST["phoneMessage"])){

	$ajax = new ChatAjax();
	$ajax -> phoneMessage = $_POST["phoneMessage"];
	$ajax -> orderMessage = $_POST["orderMessage"];
	$ajax -> ajaxLastMessage();

}

/*=============================================
Conversaciones manuales por parte del negocio
=============================================*/

if(isset($_POST["conversation"])){

	$ajax = new ChatAjax();
	$ajax -> conversation = $_POST["conversation"];
	$ajax -> phone = $_POST["phone"];
	$ajax -> token = $_POST["token"];
	$ajax -> ajaxSendMessage();
}

/*=============================================
Traer último chat
=============================================*/

if(isset($_POST["lastIdMessage"])){

	$ajax = new ChatAjax();
	$ajax -> lastIdMessage = $_POST["lastIdMessage"];
	$ajax -> phone = $_POST["phone"];
	$ajax -> borderChat = $_POST["borderChat"];
	$ajax -> ajaxLastChat();
}
