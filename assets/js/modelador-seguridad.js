function seleccionarSeguridad(procesoId){
    $("#modal").load(site_url+"backend/seguridad/ajax_seleccionar/"+procesoId);  
    $("#modal").modal();
    return false;
}