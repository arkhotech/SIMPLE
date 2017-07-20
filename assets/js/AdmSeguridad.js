function CambioSelect(value){
 	switch ($("#tipoSeguridad").val()) {                
 		case "HTTP_BASIC": case "OAUTH2":
            $("#DivUser").show();
            $("#DivPass").show();
            $("#DivKey").hide();
 		break;
 		case "API_KEY":
            $("#DivKey").show();
            $("#DivUser").hide();
            $("#DivPass").hide();
 		break;
 		default:
            $("#DivUser").hide();
            $("#DivPass").hide();
 			$("#DivKey").hide();
 		break;
 	}
 }

 $(document).ready(function(){
    $("#tipoSeguridad").change(function(){
        CambioSelect();
    });
});