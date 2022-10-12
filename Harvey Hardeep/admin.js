/**
 * Admin settings.
 *
 * @package Saddle_Oak_SOS_Integration
 *
 * Last updated 2020-04-23
 */
import axios from "axios";
import Swal from 'sweetalert2';

let __ = wp.i18n.__;
let _n = wp.i18n._n;
jQuery(function ($) {

    var Saddle_Oak_SOS_Integration_Upgrader = {
        init() {
            this.registerLicenseSave();
            this.registerLicenseReveal();
            this.registerLicenseRevoke();
            this.registerLicenseCheck();
        },

        /**
         * Send command via Ajax.
         */
        sendCommand(action, data, callback, options = {}, blockUI = true) {
            if (blockUI) {
                $.blockUI({
                    message: `<div id="edd-sl-logo-loading" aria-label="${__(
                        "Loading",
                        "edd-software-licensing-example"
                    )}"><img src="${
                        edd_sl_admin.loading
                    }" style="max-width: 200px;" /><br /></div>`,
                    css: {
                        border: "none",
                        padding: "15px",
                        backgroundColor: "transparent",
                    },
                    overlayCSS: {backgroundColor: "#FFF"},
                });
            }
            let default_options = {
                json: true,
                alert_on_error: false,
                prefix: "edd_sl_",
                nonce: $("#_edd_sl").val(),
                timeout: null,
                async: true,
                type: "POST",
            };
            for (let opt in default_options) {
                if (!options.hasOwnProperty(opt)) {
                    options[opt] = default_options[opt];
                }
            }
            // Axios and WordPress require data as form data.
            var formData = new FormData();
            for (let key in data) {
                formData.append(key, data[key]);
            }
            formData.append("action", options.prefix + action);

            axios({
                method: options.type,
                url: ajaxurl,
                data: formData,
            })
                .then((response) => {
                        $.unblockUI();
                        if (!response.data.success && options.alert_on_error) {
                            alert(response.data.data.message);
                            return;
                        }
                        if ("function" === typeof callback) callback(response.data);
                    },
                    (response) => {
                        $.unblockUI();
                        alert(__("Could not complete request", "saddle-oak-sos-integration"));
                    });
        },
        /**
         * Register the reset button on the white label savings button.
         */
        registerLicenseSave() {
            $("body").on("click", "#edd-sl-license-save", function (e) {
                e.preventDefault();
                Saddle_Oak_SOS_Integration_Upgrader.sendCommand(
                    "license_save",
                    {
                        nonce: $("#_edd_sl").val(),
                        license: $("#edd-license").val(),
                    },
                    (response) => {
                        if (response.success) {
                            $(".license-status")
                                .removeClass("edd-sl-success edd-sl-error")
                                .addClass("edd-sl-success")
                                .html(response.data.message)
                                .css("display", "block");
                            $('.edd-sl-action-buttons-wrapper').html(response.data.html);
                        } else {
                            $(".license-status")
                                .removeClass("edd-sl-success edd-sl-error")
                                .addClass("edd-sl-error")
                                .html(response.data[0].message)
                                .css("display", "block");
                        }
                        setTimeout(function () {
                            $(".license-status").fadeOut();
                        }, 15000);
                    }
                );
            });
        },
        /**
         * Revokes a license.
         */
        revokeLicense() {
            Saddle_Oak_SOS_Integration_Upgrader.sendCommand(
                "license_deactivate",
                {
                    nonce: $("#_edd_sl").val(),
                    license: $("#edd-license").val(),
                },
                (response) => {
                    if (response.success) {
                        $(".license-status")
                            .removeClass("edd-sl-success edd-sl-error")
                            .addClass("edd-sl-success")
                            .html(response.data.message)
                            .css("display", "block");
                        $("#edd-license").val("");
                        $('.edd-sl-action-buttons-wrapper').html(response.data.html);
                    } else {
                        $(".license-status")
                            .removeClass("edd-sl-success edd-sl-error")
                            .addClass("edd-sl-error")
                            .html(response.data[0].message)
                            .css("display", "block");
                    }
                    setTimeout(function () {
                        $(".license-status").fadeOut();
                    }, 15000);
                }
            );
        },
        /**
         * Register the reset button on the white label savings button.
         */
        registerLicenseRevoke() {
            $("body").on("click", "#edd-sl-license-deactivate", function (e) {
                e.preventDefault();
                if (
                    Swal.fire({
                        title: __('Revoke License?', 'edd-software-licensing-example'),
                        text: __('You can always enter your license again later.', 'saddle-oak-sos-integration'),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#c3640f',
                        cancelButtonColor: '#721c24',
                        confirmButtonText: __('Revoke License', 'saddle-oak-sos-integration'),
                    }).then((result) => {
                        if (result.value) {
                            Saddle_Oak_SOS_Integration_Upgrader.revokeLicense();
                        }
                    })
                ) {
                    return;
                }

            });
        },
        /**
         * Register the check button on the license check button.
         */
        registerLicenseCheck() {
            $("body").on("click", "#edd-sl-license-check", function (e) {
                e.preventDefault();
                Saddle_Oak_SOS_Integration_Upgrader.sendCommand(
                    "license_check",
                    {
                        nonce: $("#_edd_sl").val(),
                        license: $("#edd-license").val(),
                    },
                    (response) => {
                        if (response.success) {
                            $(".license-status")
                                .removeClass("edd-sl-success edd-sl-error")
                                .addClass("edd-sl-success")
                                .html(response.data.message)
                                .css("display", "block");
                            $('.edd-sl-action-buttons-wrapper').html(response.data.html);
                        } else {
                            $(".license-status")
                                .removeClass("edd-sl-success edd-sl-error")
                                .addClass("edd-sl-error")
                                .html(response.data[0].message)
                                .css("display", "block");
                        }
                        setTimeout(function () {
                            $(".license-status").fadeOut();
                        }, 15000);
                    }
                );
            });
        },
        /**
         * Reveal the license.
         */
        registerLicenseReveal() {
            $("body").on("click", "#edd-sl-field-license-reveal", function (e) {
                if ("password" === $("#edd-license").prop("type")) {
                    $("#edd-license").prop("type", "text");
                } else {
                    $("#edd-license").prop("type", "password");
                }
            });
        },
    };
    Saddle_Oak_SOS_Integration_Upgrader.init();
});
