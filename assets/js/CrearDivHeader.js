 var nextinput = 0;

 function CambioSelect(value){
 	switch ($("#tipoMetodo").val()) {                
 		case "POST": case "PUT":
 			$("#divObject").show();
 		break;
 		case "GET": case "DELETE":
 			$("#divObject").hide();
 		break;
 		default:
 			$("#divObject").hide();
 		break;
 	}
 }

 function CrearHeaders(){   
 	nextinput++;	
 	var section = "<div id="+nextinput+" clas='col-md-12'><input name='extra[nombre"+nextinput+"]' type='text'/><input name='extra[valor"+nextinput+"]' type='text' /><button onclick=(DeleteInputs(this)) value="+nextinput+" type='button' class='btn-danger'><span class='icon-minus'></span></button></div>";
 	$("#divDinamico").append(section);
 }

 function DeleteInputs(id){
 	$("#"+id.value).remove();
 }

 $(document).ready(function(){
    if($("#tipoMetodo").val()=="POST" || $("#tipoMetodo").val()=="POST"){
        $("#divObject").show();
    }else{
        $("#divObject").hide();
    }
    $("#tipoMetodo").change(function(){
        CambioSelect();
    });
    $(document).on("click","#btn-add",CrearHeaders);
});