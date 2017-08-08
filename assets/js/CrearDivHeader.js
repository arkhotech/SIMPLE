var nextinput=validJsonR=validJsonH=0;
var tiposMetodos=FunMetodo=FuncResponse=FuncResquest=ObjectSoap=result='';
var operaciones = [];

function ConsultarFunciones(){
    limpiar();
	var urlsoap = $("#urlsoap").val();
    $.post("/backend/acciones/functions_soap", {urlsoap: urlsoap}, function(d,e){
    	manejorespuesta(d);
    });
 }

 function validateForm(){    
    if(validJsonR==0 && validJsonR==0){
 		javascript:$('#plantillaForm').submit();
 		return false;
 	}else{
        /*if(validJsonR==1){
            console.log("entre a validar el request");
 			$("#request").addClass('invalido');
	    	$("#resultRequest").text("Formato requerido / json");
 		}*/

 		if(validJsonH==1){
            console.log("entre a validar el header");
		    $("#header").addClass('invalido');
		    $("#resultHeader").text("Formato requerido / json");
 		}
    }
 		// return false; 
 }
 
function getCleanedString(cadena){
   // Definimos los caracteres que queremos eliminar
   var specialChars = "\b\t\n\v\f\r/\n/!@#$^&%*()+=-[]{}|:<>?;,.";
   // Los eliminamos todos
   for (var i = 0; i < specialChars.length; i++) {
       cadena= cadena.replace(new RegExp("\\" + specialChars[i], 'gi'), '');
   }   
   return cadena;
}

function CovertJson(myArrClean,operaciones){
	var json='';
	    $.ajax({  
            url:'/backend/acciones/converter_json',
            type:'POST',
            async:false,
            dataType: 'JSON',
            data: {myArrClean: myArrClean, operaciones:operaciones}
        })
        .done(function(d){ 
        	json=d;
        })
        .fail(function(){
        	json=0;
        });
     	return json;
}

 function CambioRadio(){
    $("[id='operacion']").on("change", function (e) {
        $("#request").val("");
    	$("#response").val("");
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
        jQuery.each(tiposMetodos, function(i,val){
    		var sep = val.split(" ");
    		if (sep[1]==FuncResquest){
    			// Caso Request
    			var cadena= val.split("{");
    			var ultimo = cadena.pop();
    			var res= getCleanedString(ultimo);
    			var res= res.split(" ");
    			var myArrClean = res.filter(Boolean);
    			myArrClean= myArrClean.reverse();
    			var json = CovertJson(myArrClean,operaciones); 
    			if(json==0){
			    	$("#warningSpan").text("La consulta al servicio SOAP no trajo resultados, verifique.");
    			}else{
    				var result= JSON.stringify(json,null,2);
	    			$("#request").val(result);
    			}
    		}
    		if (sep[1]==FuncResponse){
    			// Caso Response
    			var cadena= val.split("{");
    			var ultimo = cadena.pop();
    			var res= getCleanedString(ultimo);
    			var res= res.split(" ");
    			var myArrClean = res.filter(Boolean);
    			myArrClean= myArrClean.reverse();
				var json = CovertJson(myArrClean,operaciones); 
    			if(json==0){
			    	$("#warningSpan").text("La consulta al servicio SOAP no trajo resultados, verifique.");
    			}else{
    				var result= JSON.stringify(json,null,2);
	    			$("#response").val(result);
    			}
    		}
		});
	}
	});
	});
 }

function CambioSelect(value){
    console.log("esta cambiando el select");
    validJsonR=0;
    validJsonH=0;
    $("#request").focusout(function(){
        console.log("entre a validar el request");
        isJsonR($("#request"),$("#request").val(),$("#resultRequest"));
    });
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
    var obj = $("#header").val();
    if(obj.length>1){
        try {
            JSON.parse(value);
        }catch (e){
            object.addClass('invalido');
            id_span.text("Formato requerido / json");
            validJsonH=1;
            return false;
        }        
    }
	object.removeClass('invalido');
	id_span.text("");
	validJsonH=0;
    return true;
}

function isJsonR(object,value,id_span){
    //if(obj.length>1){
        try {
            JSON.parse(value);
        }catch (e){
            object.addClass('invalido');
            id_span.text("Formato requerido / json");
            validJsonR=1;
            return false;
        }
    //}
    object.removeClass('invalido');
    id_span.text("");
    validJsonR=0;
    return true;
}


function manejorespuesta(data){
    if (data){
        $("#request").val("");
        $("#response").val("");
        $("#operacion").empty();    
        $('#divMetodosE').hide();
        result = JSON.parse(data);
        if(result.caso==2){
            $("#urlsoap").val(result.targetNamespace[0]);
        }
        jQuery.each(result.types, function(i,val){  
            val =  getCleanedString(val); 
            val = val.replace(/\n/g, "");
            val = val.split(" ");
            val = val.filter(Boolean);
            for (j = 0; j < val.length; j++) { 
                val[j] = val[j].trim();
            }
         operaciones[i] = val;
		});
    	tiposMetodos=result.types;
		$("#operacion").append("<option value=''>Seleccione...</option>"); 	
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
}

var CargarWsdl = function(){ 
    $("#urlsoap").val("");
    limpiar();
    var formu=$(this);
    var nombreform=$(this).attr("id");
    var form = $('#plantillaForm').get(0); 
    var formData = new FormData(form);
    $.ajax({
        url: "/backend/acciones/upload_file", 
        type: 'POST',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function(data){
        	$("#modalImportarWsdl").modal('hide');
        	manejorespuesta(data);
        },
        error: function(data){
          	alert("ha ocurrido un error al cargar su archivo");
        },
    });
};
var limpiar = function(){
    $("#request").val("");
    $("#response").val("");
    $("#operacion").empty();    
    $('#divMetodosE').hide();
    $("#warningSpan").text("");
}

 $(document).ready(function(){
    console.log("hola desde aqui");
 	$('#divMetodosE').hide();
 	$('#resultRequest').text("Formato requerido / json")
 	$('#resultHeader').text("Formato requerido / json")
    
    if($("#tipoMetodo").val()=="POST" || $("#tipoMetodo").val()=="PUT"){
        $("#divObject").show();
    }else{
        $("#divObject").hide();
    }

    $("#tipoMetodo").change(function(){
        CambioSelect();
    });



    $("#header").focusout(function(){
    	isJsonH($("#header"),$("#header").val(),$("#resultHeader"));
	});

    $(document).on('click','#btn-consultar',ConsultarFunciones);
    $(document).on('click','#btn-load',CargarWsdl);
    $("#urlsoap").keyup(function(){
        limpiar();
    });

});