 var nextinput=validJsonR=validJsonH=0;
 var tiposMetodos=FunMetodo=FuncResponse=FuncResquest=ObjectSoap=result='';
 var DataTypesSoap=["anyURI","float","language","Qname","boolean","gDay","long","short","byte","gMonth","Name","cadena de caracteres","date","gMonthDay","NCName","time","dateTime","gYear","negativeInteger","token","decimal","gYearMonth","NMTOKEN","unsignedByte","double","ID","NMTOKENS","unsignedInt","duration","IDREFS","nonNegativeInteger","unsignedLong","ENTITIES","int","nonPostiveInteger","unsignedShort","ENTITY","integer","normalizedString"];

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
    $.post("/backend/acciones/functions_soap", {urlsoap: urlsoap}, function(d,e){
    	if (d){
 			$('#divMetodosE').hide();
 			result = JSON.parse(d);
	    	tiposMetodos=result.types;
	    	jQuery.each(result.functions, function(i,val){
			    var res = val.split(" ");
				var subtit = res[1].replace("(", " ");
			    var subtit = subtit.split(" ");
			    $("#operacion").append("<option value='"+subtit[0]+"'>"+subtit[0]+"</option>"); 	
			});
			CambioRadio();
    	}else{
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

 function CambioRadio(){
    $("[id='operacion']").on("change", function (e) {
    	ObjectSoap=this.value;
    	jQuery.each(result.functions, function(i,val){
    	var bool = val.indexOf(ObjectSoap);
    	if (bool>=0){    	
		var res = val.split(" ");
		var subtit = res[1].replace("(", " ");
	    var subtit = subtit.split(" ");
	    FuncResponse=res[0];
		FunMetodo=subtit[0];
		FuncResquest=subtit[1];
		console.log(FuncResponse);
		console.log(FunMetodo);
		console.log(FuncResquest);
		console.log(tiposMetodos);
    	jQuery.each(tiposMetodos, function(i,val){
    		var sep = val.split(" ");
    		if (sep[1]==FuncResquest){
    			// Caso Request
    			console.log("entre al request");
    			console.log(DataTypesSoap);
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
    		 if (sep[1]==FuncResponse){
    			// Caso Response
    			console.log("entre al response");
    			var cadena= val.split("{");
    			var ultimo = cadena.pop();
    			var res= getCleanedString(ultimo);
    			var res= res.split(" ");
    			var myArrClean = res.filter(Boolean);
    			myArrClean= myArrClean.reverse();
		    	$.post("/backend/acciones/converter_json", {myArrClean: myArrClean}, function(d){
			    	if (d){
			    		console.log(d);

	    				$("#response").val(d);
			    	}else{
			    		$("#warningSpan").text("La consulta al servicio SOAP no trajo resultados, verifique.");
			    	}
		    	});
    		}
		});
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