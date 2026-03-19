/* PS Community – 2FA Profil-Integration */
(function ($) {
    'use strict';

    var $wrapper;

    function showMsg(msg, isError) {
        var $msg = $('#cpc_2fa_message');
        $msg
            .removeClass('cpc_success cpc_error')
            .addClass(isError ? 'cpc_error' : 'cpc_success')
            .html(msg)
            .show();
        if (!isError) {
            setTimeout(function () { $msg.fadeOut(400); }, 5000);
        }
    }

    function reloadWithDelay(successMsg) {
        showMsg(successMsg, false);
        setTimeout(function () { window.location.reload(); }, 1800);
    }

    $(document).ready(function () {
        $wrapper = $('#cpc_2fa_wrapper');
        if (!$wrapper.length) return;

        /* ── QR-Code anzeigen ─────────────────────────────────────────── */
        $(document).on('click', '#cpc_2fa_show_qr', function () {
            var $btn = $(this).prop('disabled', true);
            $.post(cpc2fa.ajaxurl, {
                action:   'cpc_2fa_get_qr',
                security: cpc2fa.nonce
            })
            .done(function (res) {
                if (res.success) {
                    $('#cpc_2fa_qr_img').attr('src', res.data.qr);
                    $('#cpc_2fa_secret_key').text(res.data.secret);
                    $('#cpc_2fa_qr_wrapper').slideDown(200);
                    $btn.hide();
                } else {
                    $btn.prop('disabled', false);
                    showMsg(res.data.message, true);
                }
            })
            .fail(function () {
                $btn.prop('disabled', false);
                showMsg('Verbindungsfehler. Bitte versuche es erneut.', true);
            });
        });

        /* ── App-Code bestätigen und 2FA aktivieren ───────────────────── */
        $(document).on('click', '#cpc_2fa_verify_app', function () {
            var code = $('#cpc_2fa_app_code').val().replace(/\s/g, '');
            if (!/^\d{6}$/.test(code)) {
                showMsg('Bitte gib einen 6-stelligen Zahlencode ein.', true);
                $('#cpc_2fa_app_code').focus();
                return;
            }
            var $btn = $(this).prop('disabled', true);
            $.post(cpc2fa.ajaxurl, {
                action:   'cpc_2fa_verify_enable_app',
                security: cpc2fa.nonce,
                code:     code
            })
            .done(function (res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    reloadWithDelay(res.data.message);
                } else {
                    showMsg(res.data.message, true);
                    $('#cpc_2fa_app_code').val('').focus();
                }
            })
            .fail(function () {
                $btn.prop('disabled', false);
                showMsg('Verbindungsfehler. Bitte versuche es erneut.', true);
            });
        });

        /* Enter-Taste im Code-Feld abfangen */
        $(document).on('keypress', '#cpc_2fa_app_code', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#cpc_2fa_verify_app').trigger('click');
            }
        });

        /* ── E-Mail-2FA aktivieren ────────────────────────────────────── */
        $(document).on('click', '#cpc_2fa_enable_email', function () {
            if (!window.confirm('Zwei-Faktor-Authentifizierung per E-Mail aktivieren?')) return;
            var $btn = $(this).prop('disabled', true);
            $.post(cpc2fa.ajaxurl, {
                action:   'cpc_2fa_enable_email',
                security: cpc2fa.nonce
            })
            .done(function (res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    reloadWithDelay(res.data.message);
                } else {
                    showMsg(res.data.message, true);
                }
            })
            .fail(function () {
                $btn.prop('disabled', false);
                showMsg('Verbindungsfehler. Bitte versuche es erneut.', true);
            });
        });

        /* ── 2FA deaktivieren ─────────────────────────────────────────── */
        $(document).on('click', '#cpc_2fa_disable', function () {
            if (!window.confirm('Zwei-Faktor-Authentifizierung wirklich deaktivieren?')) return;
            var $btn = $(this).prop('disabled', true);
            $.post(cpc2fa.ajaxurl, {
                action:   'cpc_2fa_disable',
                security: cpc2fa.nonce
            })
            .done(function (res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    reloadWithDelay(res.data.message);
                } else {
                    showMsg(res.data.message, true);
                }
            })
            .fail(function () {
                $btn.prop('disabled', false);
                showMsg('Verbindungsfehler. Bitte versuche es erneut.', true);
            });
        });

        /* ── Backup-Code neu generieren ───────────────────────────────── */
        $(document).on('click', '#cpc_2fa_regen_backup', function () {
            if (!window.confirm('Neuen Backup-Code generieren? Der bisherige Code wird ungültig.')) return;
            var $btn = $(this).prop('disabled', true);
            $.post(cpc2fa.ajaxurl, {
                action:   'cpc_2fa_regen_backup',
                security: cpc2fa.nonce
            })
            .done(function (res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    var $display = $('#cpc_2fa_backup_code_display');
                    // Als <code>-Element darstellen falls bisher nur Text
                    if ($display.is('span')) {
                        $display.replaceWith(
                            '<code id="cpc_2fa_backup_code_display" ' +
                            'style="display:inline-block;padding:4px 10px;background:#f5f5f5;border:1px solid #ddd;border-radius:3px;">' +
                            $('<div/>').text(res.data.backup_code).html() +
                            '</code>'
                        );
                    } else {
                        $display.text(res.data.backup_code);
                    }
                    showMsg(res.data.message, false);
                } else {
                    showMsg(res.data.message, true);
                }
            })
            .fail(function () {
                $btn.prop('disabled', false);
                showMsg('Verbindungsfehler. Bitte versuche es erneut.', true);
            });
        });
    });

})(jQuery);
