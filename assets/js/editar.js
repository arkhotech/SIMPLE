$(document).ready(function(){
    $("#proc_arch_id").change(function(){
        var proceso_id =$("#proc_arch_id").val();
        console.log("Id proceso seleccionado: "+proceso_id);
        $("#procArchivadoForm").attr('action', '/backend/procesos/editar/'+proceso_id);
        console.log("Submit action: "+$("#procArchivadoForm").attr('action'));
        $('#procArchivadoForm').submit();
        //javascript:$('#procArchivadoForm').submit();
    });
});