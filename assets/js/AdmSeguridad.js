function CambioSelect(){
        console.log($("#tipoSeguridad").val());
        switch ($("#tipoSeguridad").val()) {                
            case "HTTP_BASIC":
                $("#DivBasic").show();
                $("#DivKey").hide();
                $("#DivAuth").hide();
                $(".key").val("");
                $(".oauth").val("");
            break;
            case "API_KEY":
                $("#DivBasic").hide();
                $("#DivKey").show();
                $("#DivAuth").hide();
                $(".basic").val("");
                $(".oauth").val("");

            break;
            case "OAUTH2":
                console.log("entre en oauth2");
                $("#DivBasic").hide();
                $("#DivKey").hide();
                $("#DivAuth").show();
                $(".basic").val("");
                $(".key").val("");
            break;            
            default:
                $("#DivBasic").hide();
                $("#DivKey").hide();
                $("#DivAuth").hide();
                $(".basic").val("");
                $(".key").val("");
                $(".oauth").val("");
            break;
        }
 }

 $(document).ready(function(){
    CambioSelect();
    $("#tipoSeguridad").change(function(){
        CambioSelect();
    });
});