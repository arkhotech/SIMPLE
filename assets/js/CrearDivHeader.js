 var nextinput=validJsonR=validJsonH=0;
 var tiposMetodos='';

var rhtmlspecialchars = function (str) {
    if (typeof(str) == "string") {
	    str = str.replace(/&gt;/ig, "");
	    str = str.replace(/&lt;/ig, "");
	    str = str.replace(/&#039;/g, "");
	    str = str.replace(/&quot;/ig, '');
	    str = str.replace(/\n/ig, '');
	    str = str.replace(" ", '');
	    str = str.replace(/&amp;/ig, ''); 
    }
    return str;
}

var rhtmlspecialchars2 = function (str) {
    if (typeof(str) == "string") {
    str = str.replace(/___/ig, " ");
    str = str.replace(/__/ig, " ");
    str = str.replace(/_/ig, " ");
    }
    return str;
}

 function ConsultarFunciones(){
    $("#divOptions").empty();
    $("#warningSpan").text("");
	var urlsoap = $("#urlsoap").val();
    $.post("/backend/acciones/functions_soap", {urlsoap: urlsoap}, function(d){
    	if (d){
 			$('#divMetodosE').hide();
 			$('#divMetodos').show();
	    	var result = JSON.parse(d);
	    	tiposMetodos=result.types;
	    	jQuery.each(result.functions, function(i,val){
			    var res = val.split(" ");
				var subtit = res[1].replace("(", " ");
				var titulo = subtit.split(" ");
			    $("#divOptions").append("<input class='rButton' type='radio' id='operacion' name='extra[operacion]' value='"+titulo[0]+"'> "+titulo[0]+"&nbsp;&nbsp;"); 	
			});
			CambioRadio();
    	}else{
 			$('#divMetodos').hide();
 			$('#divMetodosE').show();
    		$("#warningSpan").text("La consulta al servicio SOAP no trajo resultados, verifique.");
    	}
    });
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
 		// return false;
 	} 
 }
 
function getCleanedString(cadena){
   // Definimos los caracteres que queremos eliminar
   var specialChars = "/\n/!@#$^&%*()+=-[]{}|:<>?,.;";

   // Los eliminamos todos
   for (var i = 0; i < specialChars.length; i++) {
       cadena= cadena.replace(new RegExp("\\" + specialChars[i], 'gi'), '');
   }   
   return cadena;
}

// Convert array to object
var convArrToObj = function(array){
	console.log("entre a la funcion");
    var thisEleObj = new Object();
    if(typeof array == "object"){
        for(var i in array){
            var thisEle = convArrToObj(array[i]);
            thisEleObj[i] = "thisEle";
        }
    }else {
        thisEleObj = array;
    }
    return thisEleObj;
}

 function CambioRadio(value){
    $("[id='operacion']").on("change", function (e) {
    	var metodo=this.value;
    	jQuery.each(tiposMetodos, function(i,val){
    		var bool= val.indexOf(metodo)
    		if (bool>0){
    			var bool2= val.indexOf("Response")
	    		if (bool2>0){
	    			// Caso Response
	    			var cadena= val.split("{");
	    			var ultimo = cadena.pop();
	    			var res= getCleanedString(ultimo);
	    			var res= res.split(" ");
	    			var myArrClean = res.filter(Boolean);
	    			myArrClean= myArrClean.reverse();
			    	$.post("/backend/acciones/converter_json", {myArrClean: myArrClean}, function(d){
				    	if (d){
				    		$("#SpanResponse").text(d);
				    	}else{
				    		$("#warningSpan").text("La consulta al servicio SOAP no trajo resultados, verifique.");
				    	}
			    	});	    			
	    		}else{
	    			// Caso Request
	    			var cadena= val.split("{");
	    			var ultimo = cadena.pop();
	    			var res= getCleanedString(ultimo);
	    			var res= res.split(" ");
	    			var myArrClean = res.filter(Boolean);
	    			myArrClean= myArrClean.reverse();
	    			$.post("/backend/acciones/converter_json", {myArrClean: myArrClean}, function(d){
				    	if (d){
	    					$("#request").val(d);
				    	}else{
				    		$("#warningSpan").text("La consulta al servicio SOAP no trajo resultados, verifique.");
				    	}
			    	});
	    		}



    			
    			// var res= rhtmlspecialchars(val);
    			// var res2= rhtmlspecialchars(res);
    			// console.log(res);

    			// var res4= rhtmlspecialchars(res3);
    			// var res5= rhtmlspecialchars(res4);
    			// var res6= rhtmlspecialchars(res5);
    			// var res7= rhtmlspecialchars(res6);
    			// var res8= rhtmlspecialchars(res7);
    			// var res9= rhtmlspecialchars(res8);
    			// var res10= rhtmlspecialchars2(res9);
    			// var res11= rhtmlspecialchars2(res10);
    		}
		});
	});
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
 	$('#divMetodos').hide();
 	$('#divMetodosE').hide();
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