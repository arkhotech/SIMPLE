$(document).ready(function() {

    $('.navbar-detail').hide();
    $("#main").css("margin-top", $("header").height());

    $('#sidebar_head').click(function () {
        $('.navbar-detail').toggle();
    });

    $(window).resize(function() {
        $('.navbar-detail').hide();
        $("#main").css("margin-top", $("header").height());
    });

    $("[data-toggle=popover]").popover();

    $(".chosen").chosen();

    $(".preventDoubleRequest").one("click", function() {
        $(this).click(function () { return false; });
    });

    $(".file-uploader").each(function(i, el) {
        var $parentDiv = $(el).parent();
        new qq.FileUploader({
            element: el,
            action: $(el).data("action"),
            onComplete: function(id, filename, respuesta) {
                if (!respuesta.error) {
                    if (typeof(respuesta.file_name) !== "undefined") {
                        $parentDiv.find(":input[type=hidden]").val(respuesta.file_name);
                        $parentDiv.find(".qq-upload-list").empty();
                        $parentDiv.find(".link").html("<a target='blank' href='" + site_url + "uploader/datos_get/" + respuesta.file_name + "?id=" + respuesta.id + "&token=" + respuesta.llave + "'>" + respuesta.file_name + "</a> (<a href='#' class='remove'>X</a>)");
                        prepareDynaForm(".dynaForm");
                    } else {
                        $parentDiv.find(".link").html("");
                        alert("La imagen es muy grande");
                    }
                }
            }
        }); 
    });

    $(".file-uploader").parent().on("click", "a.remove", function() {
        var $parentDiv = $(this).closest("div");
        $parentDiv.find(":input[type=hidden]").val("");
        $parentDiv.find(".link").empty();
        $parentDiv.find(".qq-upload-list").empty();
        prepareDynaForm(".dynaForm");
    });

    $("#login .submit").click(function() {
        var form = $("#login");
        if (!$(form).prop("submitting")) {
            $(form).prop("submitting", true);
            $('#login .ajaxLoader').show();

            $.ajax({
                url: $(form).prop("action"),
                data: $(form).serialize(),
                type: $(form).prop("method"),
                dataType: "json",
                success: function(response) {
                    if (response.validacion) {
                        if (response.redirect) {
                            window.location = response.redirect;
                        } else {
                            var f = window[$(form).data("onsuccess")];
                            f(form);
                        }
                    } else {
                        if ($('#login_captcha').length > 0) {
                            if ($('#login_captcha').is(':empty')) {
                                grecaptcha.render('login_captcha', {
                                    'sitekey' : site_key
                                });
                            } else {
                                grecaptcha.reset();
                            }
                        }

                        $(form).prop("submitting", false);
                        $('#login .ajaxLoader').hide();

                        $(".validacion_login").html(response.errores);
                        $('html, body').animate({
                            scrollTop: $(".validacion_login").offset().top - 10
                        });
                    }
                },
                error: function() {
                    $(form).prop("submitting", false);
                    $('#login .ajaxLoader').hide();
                }
            });
        }
        return false;
    });

    $(document).on("submit", ".ajaxForm", function() {
        var form = this;
        if (!form.submitting) {
            form.submitting = true;
            $(form).find(":submit").attr("disabled", true);
            $(form).append("<div class='ajaxLoader'>Cargando</div>");
            var ajaxLoader = $(form).find(".ajaxLoader");
            $(ajaxLoader).css({
                left: ($(form).width() / 2 - $(ajaxLoader).width() / 2) + "px", 
                top: ($(form).height() / 2 - $(ajaxLoader).height() / 2) + "px"
            });
            $('.ajaxLoader').show();
            $.ajax({
                url: form.action,
                data: $(form).serialize(),
                type: form.method,
                dataType: "json",
                success: function(response) {
                    if (response.validacion) {
                        if (response.redirect) {
                            window.location = response.redirect;
                        } else {
                            var f = window[$(form).data("onsuccess")];
                            f(form);
                        }
                    } else {
                        if ($('#login_captcha').length > 0) {
                            if ($('#login_captcha').is(':empty')) {
                                grecaptcha.render('login_captcha', {
                                    'sitekey': site_key
                                });
                            } else {
                                grecaptcha.reset();
                            }
                        }

                        form.submitting = false;
                        $(ajaxLoader).remove();
                        $(form).find(":submit").attr("disabled", false);
            
                        $(form).find(".validacion").html(response.errores);
                        $('html, body').animate({
                            scrollTop: $(form).find(".validacion").offset().top - 10
                        });
                    }
                },
                error: function() {
                    form.submitting = false;
                    $(ajaxLoader).remove();
                    $(form).find(":submit").attr("disabled", false);
                }
            });
        }
        return false;
    });

    // Para manejar los input dependientes en dynaforms
    function prepareDynaForm(form) {
        $(form).find(":input[readonly]").prop("disabled", false);
        $(form).find(".file-uploader ~ input[type=hidden]").prop("type","text");
        $(form).find(".campo[data-dependiente-campo]").each(function(i, el) {
            var tipo = $(el).data("dependiente-tipo");
            var relacion = $(el).data("dependiente-relacion");
            var campo = $(el).data("dependiente-campo");
            var valor = $(el).data("dependiente-valor");
            var existe = false;
            var visible = false;

            $(form).find(":input[name='"+campo+"']").each(function (i, el) {

            	existe = true;

            	if ($(el).css("display") !== 'none' && $(el).attr("type") !== 'hidden' && $(el).parents(".campo").css("opacity") != '0.5' && !visible && $(el).is(":visible")) {
                    var input = $(el).serializeArray();
                    for (var j in input) {
                        if (tipo == "regex") {
                            var regex = new RegExp(valor);
                            if (regex.test(input[j].value)) {
                                visible = true;
                            }
                        } else {
                            if (input[j].value == valor) {
                                visible = true;
                            }
                        }
                        if (relacion == "!=") {
                            visible =! visible;
                        }
                        if (visible) {
                            break;
                        }
                    }
            	}        	
            });

            if (existe) {
                if (visible) {
                    if ($(form).hasClass("debugForm"))
                        $(el).css("opacity","1.0");
                    else
                        $(el).show();

                    if (!$(el).data("readonly"))
                        $(el).find(":input").addClass("enabled-temp");

                } else {
                    if ($(form).hasClass("debugForm"))
                        $(el).css("opacity","0.5");
                    else
                        $(el).hide();

                    $(el).find(":input").addClass("disabled-temp");
                }
            }
        });

        $(form).find(":input.disabled-temp").each(function(i, el) {
            $(el).prop("disabled", true);
            $(el).removeClass("disabled-temp");
        });

        $(form).find(":input.enabled-temp").each(function(i, el) {
            $(el).prop("disabled", false);
            $(el).removeClass("disabled-temp");        	
        });

        $(form).find(".file-uploader ~ input[type=text]").prop("type","hidden");
        $(form).find(":input[readonly]").prop("disabled",true);
    }

    prepareDynaForm(".dynaForm");

    $(".dynaForm").on("change", ":input", function(event) {
        prepareDynaForm($(event.target).closest(".dynaForm"))
    });
});

function buscarAgenda() {
    if (jQuery.trim($('.js-pertenece').val()) != "") {
        var base_url = $('#base_url').val();
        if (jQuery.trim($('.js-pertenece').val()) == '%') {
            location.href = base_url + "/backend/agendas";
        } else {
            var search = $('.js-pertenece').val();
            $('#frmsearch').submit();
        }
    } else {
        $('.validacion').html('<div class="alert alert-error"><a class="close" data-dismiss="alert">×</a>Debe ingresar un nombre de agenda o pertence. si quiere listar todas digite \'%\'</div>');
    }
}

function calendarioFront(idagenda, idobject, idcita, tramite, etapa) {

    var site_url = $('#urlbase').val();

    if (idcita == 0) {
        if (typeof $('#codcita' + idobject) !== "undefined") {
            idcita = $('#codcita' + idobject).val();
        }
    }

    var idtramite = $('#codcita' + idobject).attr('data-id-etapa');

    if (typeof (idtramite) === "undefined" || idtramite == 0) {
        idtramite = tramite;
    }

    if (typeof(etapa) === "undefined" || etapa == 0) {
        etapa = idtramite;
    }

    $('#codcita' + idobject).attr('data-id-etapa');
    $("#modalcalendar").load(site_url + "agenda/ajax_modal_calendar?idagenda=" + idagenda + "&object=" + idobject + "&idcita=" + idcita + "&idtramite=" + idtramite + "&etapa=" + etapa);
    $("#modalcalendar").modal();
}

