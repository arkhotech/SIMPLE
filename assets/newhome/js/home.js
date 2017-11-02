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
});
