var validJsonR=0;

function validateForm(){
    var casoR=0;
    var select =$("#tramiteSel").val();
    if (select==''){
        $("#tramiteSel").addClass('invalido');
    }else{
        $("#tramiteSel").removeClass('invalido');
        javascript:$('#plantillaForm').submit();
    }
}

/*$(document).ready(function(){
    $("#tramiteSel").change(function(){
        var idTramite = $("#tramiteSel").val();

        var json='';
        $.ajax({
            url:'/backend/acciones/getTareasProceso',
            type:'POST',
            async:false,
            dataType: 'JSON',
            data: {idProceso: idTramite}
        })
            .done(function(d){
                json=d;
            })
            .fail(function(){
                json=0;
            });

        console.log("Respuesta tareas con callback: "+json);
        console.log(json.data);

        if(json.data.length > 0){
            $("#callbackSel").append("<option value=''>Seleccione...</option>");
            for (var i = 0; i < json.data.length; i++){
                console.log("Id: "+json.data[i].id);
                console.log("Nombre: "+json.data[i].nombre);
                $("#callbackSel").append("<option value='"+json.data[i].id_tarea+"'>"+json.data[i].nombre+"</option>");
            }
        }
    });
});*/