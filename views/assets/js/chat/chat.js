/*=============================================
Mover el scroll hasta la última conversación
=============================================*/

function scrollMoveToEnd(){

	$(document).ready(function() {

		var messages = $(".msg:last");

		if(messages.length > 0){

			$("html, body, #chatBody").animate({

				scrollTop: $("#chatBody")[0].scrollHeight
			
			},500);

		}

	})

}

scrollMoveToEnd();



/*=============================================
Revisar si hay mensajes nuevos en el chat
=============================================*/

var interval = setInterval(function(){

	var phoneMessage = $("#phoneMessage").val();
	// console.log("phoneMessage", phoneMessage);
	var orderMessage = $("#orderMessage").val();
	// console.log("orderMessage", orderMessage);

	if(phoneMessage != undefined && orderMessage != undefined){

		intervalMessage(phoneMessage,orderMessage);
	}

	/*=============================================
	Función para indentificar nuevos chats
	=============================================*/

	intervalChat();


},2000); //cada 2 segundos

/*=============================================
Función si hay mensajes nuevos en el chat
=============================================*/

function intervalMessage(phoneMessage,orderMessage){

	var data = new FormData();
	data.append("phoneMessage", phoneMessage);
	data.append("orderMessage", orderMessage);

	$.ajax({
		url:"/ajax/chat.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
        processData: false,
        success: function (response){
        	
        	// console.log("response", response);
        	
        	if(response != ""){
        		
        		// console.log("response", response);

        		var response = JSON.parse("["+response+"]");
        		
        		$("#chatBody").append(decodeURIComponent(escape(atob(response[0].message))));
        		$("#orderMessage").val(response[0].lastOrder);

        		/*=============================================
				Sonido cuando el cliente escribe un nuevo mensaje en el chat actual
				=============================================*/

        		if(response[0].type == "client"){

	        		$("#messageSound")[0].play();

	        	}

	        	scrollMoveToEnd();

        	}

        }

	})

}

/*=============================================
Respondiendo Chat Manualmente desde el botón
=============================================*/

$(document).on("click",".send",function(){

	var conversation = $("#userInput").val();

	sendMessage(conversation);

})

/*=============================================
Respondiendo Chat Manualmente con Enter
=============================================*/

$("#userInput").keyup(function(event){

	event.preventDefault();

	if(event.keyCode == 13 && $("#userInput").val() != ""){

		var conversation = $("#userInput").val();

		sendMessage(conversation);
	}

})

/*=============================================
Función para enviar la conversación
=============================================*/

function sendMessage(conversation){

	$("#userInput").val("");

	var data = new FormData();
	data.append("conversation", conversation);
	data.append("phone", $("#phoneMessage").val());
	data.append("token",localStorage.getItem("tokenAdmin"));

	$.ajax({
		url:"/ajax/chat.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
        processData: false,
        success: function (response){
        	
        	// console.log("response", response);
        }

    })

}

/*=============================================
Función para indentificar nuevos chats
=============================================*/

function intervalChat(){

	var data = new FormData();
	data.append("lastIdMessage", $("#lastIdMessage").attr("lastIdMessage"));
	data.append("phone", $("#phoneMessage").val());
	data.append("borderChat", $("#borderChat").val());

	$.ajax({
		url:"/ajax/chat.ajax.php",
		method: "POST",
		data: data,
		contentType: false,
		cache: false,
        processData: false,
        success: function (response){
        	
        	if(response != ""){

        		$("#lastIdMessage").html('');
        		
        		// console.log("response", response);

        		var response = JSON.parse("["+response.slice(0,-1)+"]");

        		$("#lastIdMessage").attr("lastIdMessage",response[0].lastIdMessage);

        		/*=============================================
				Sonido cuando el cliente tiene una conversación nueva
				=============================================*/

        		if(response[0].phone != $("#phoneMessage").val()){

        			$("#chatSound")[0].play();
        		}

        		response.forEach((e,i)=>{

        			$("#lastIdMessage").append(decodeURIComponent(escape(atob(e.chats))));

        		})
        		
        	}
        }

    })

}

