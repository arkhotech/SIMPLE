function CambioSelect(){
 	switch ($("#tipoSeguridad").val()) {                
 		case "HTTP_BASIC": case "OAUTH2":
            $("#DivUser").show();
            $("#DivPass").show();
            $("#DivKey").hide();
            $("#key").val("");
        break;
        case "API_KEY":
            $("#DivKey").show();
            $("#DivUser").hide();
            $("#DivPass").hide();
            $("#user").val("");
            $("#pass").val("");
 		break;
 		default:
            $("#DivUser").hide();
            $("#DivPass").hide();
 			$("#DivKey").hide();
            $("#user").val("");
            $("#pass").val("");
            $("#key").val("");
 		break;
 	}
 }

 $(document).ready(function(){
    CambioSelect();
    $("#tipoSeguridad").change(function(){
        CambioSelect();
    });
});