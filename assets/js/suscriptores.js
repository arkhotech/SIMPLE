function validateForm(){
        var resultR = isJsonR();
        if (resultR!='0'){
            $("#request").addClass('invalido');
            $("#resultRequest").text("Formato requerido / json");
        }else{
            $("#request").removeClass('invalido');
            $("#resultRequest").text("");
            javascript:$('#plantillaForm').submit();
        }
}

function isJsonR(){
    try {
        if($("#request").val() != null && $("#request").val() != ''){
            JSON.parse($("#request").val());
        }
    }catch (e){
        return 1;
    }        
    return 0;
}
