 var nextinput=validJsonR=validJsonH=0;

 function ConsultarFunciones(){
	var urlsoap = $("#urlsoap").val();
    $.post("/backend/acciones/functions_soap", {urlsoap: urlsoap}, function(d){
    	// console.log(d);
    	var result = JSON.parse(d);
    	// console.log(result);
    	// console.log(result.functions);
    	// console.log(result.functions[0]);
    	jQuery.each(result.functions, function(i,val){
		   var res = val.split(" ");
		   console.log(res[1]);
		});
    });

	// $.ajax({
	// 	url:  '/backend/acciones/functions_soap',
	// 	type: 'POST',
	// 	dataType: 'json',
	// 	data: {
	// 		urlsoap: $('#urlsoap').val(),
	// 	},
	// 	done:function(data){
	// 		console.log(response);
	// 		console.log("me fue bien en el ajax");
	// 	}
	// });
 }

 function validateForm(){
 	if(validJsonR==0 && validJsonH==0){
 		javascript:$('#plantillaForm').submit();
 		return false;
 	}else{
 		if(validJsonR==1){
 			$("#request").addClass('invalido');
	    	$("#resultRequest").text("Formato requerido / json");
 		}
 		if(validJsonH==1){
		    $("#header").addClass('invalido');
		    $("#resultHeader").text("Formato requerido / json");
 		}
 		return false;
 	} 
 }
 function CambioSelect(value){
 	switch ($("#tipoMetodo").val()) {                
 		case "POST": case "PUT":
 			$("#divObject").show();
 			validJsonR=1;
 		break;
 		case "GET": case "DELETE":
 			$("#divObject").hide();
 		break;
 		default:
 			$("#divObject").hide();
 		break;
 	}
 }

 function isJsonH(object,value,id_span){
    try {
        JSON.parse(value);
    }catch (e){
	    object.addClass('invalido');
	    id_span.text("Formato requerido / json");
	    validJsonH=1;
        return false;
    }
	object.removeClass('invalido');
	id_span.text("");
	validJsonH=0;
    return true;
}

function isJsonR(object,value,id_span){
    try {
        JSON.parse(value);
    }catch (e){
	    object.addClass('invalido');
	    id_span.text("Formato requerido / json");
	    validJsonR=1;
        return false;
    }
	object.removeClass('invalido');
	id_span.text("");
	validJsonR=0;
    return true;
}

 $(document).ready(function(){
 	$('#resultRequest').text("Formato requerido / json")
 	$('#resultHeader').text("Formato requerido / json")
    if($("#tipoMetodo").val()=="POST" || $("#tipoMetodo").val()=="POST"){
        $("#divObject").show();
    }else{
        $("#divObject").hide();
    }

    $("#tipoMetodo").change(function(){
        CambioSelect();
    });

    $("#request").focusout(function(){
    	isJsonR($("#request"),$("#request").val(),$("#resultRequest"));
	});

    $("#header").focusout(function(){
    	isJsonH($("#header"),$("#header").val(),$("#resultHeader"));
	});

	
    $(document).on('click','#btn-consultar',ConsultarFunciones);

});