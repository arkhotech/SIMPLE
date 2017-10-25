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

    $("#login .submit").click(function() {
        console.log("submit");
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

                        $(".validacion").html(response.errores);
                        $('html, body').animate({
                            scrollTop: $(".validacion").offset().top - 10
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
                            f (form);
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
