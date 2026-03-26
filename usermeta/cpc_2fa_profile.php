<?php
/**
 * Zwei-Faktor-Authentifizierung (2FA) – PS Community Profil-Integration
 *
 * Bindet die 2FA aus PS Security (cp-defender) in den Profil-Bearbeiten-Screen ein.
 * Wird nur geladen, wenn das Plugin „core-profile" aktiv und cp-defender verfügbar ist.
 */

// Enqueue JS + Inline-CSS wenn nötig
add_action( 'wp_enqueue_scripts', 'cpc_2fa_enqueue_assets' );
function cpc_2fa_enqueue_assets() {
	if ( ! cpc_2fa_is_available() || ! is_user_logged_in() ) {
		return;
	}
	wp_enqueue_script(
		'cpc-2fa-profile',
		plugins_url( 'cpc_2fa_profile.js', __FILE__ ),
		array( 'jquery' ),
		get_option( 'cp_community_ver', '1.0.0' ),
		true
	);
	wp_localize_script( 'cpc-2fa-profile', 'cpc2fa', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'cpc-2fa-nonce' ),
	) );

	// Inline-CSS an vorhandenes Stylesheet hängen
	$css = '
.cpc_2fa_wrapper { margin: 6px 0 4px; }
.cpc_2fa_badge { display: inline-block; padding: 2px 10px; border-radius: 3px; font-size: 0.85em; font-weight: bold; }
.cpc_2fa_active  { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.cpc_2fa_inactive{ background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.cpc_2fa_msg { padding: 8px 12px; border-radius: 3px; margin: 8px 0; }
.cpc_2fa_msg.cpc_success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.cpc_2fa_msg.cpc_error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
#cpc_2fa_qr_wrapper p { margin: 6px 0; font-size: 0.93em; }
#cpc_2fa_app_code { letter-spacing: 3px; }
';
	wp_add_inline_style( 'cpc-usermeta-css', $css );
}

/**
 * Prüft ob cp-defender Auth_API geladen wurde.
 */
function cpc_2fa_is_available() {
	return class_exists( 'CP_Defender\Module\Advanced_Tools\Component\Auth_API' )
		&& class_exists( 'CP_Defender\Module\Advanced_Tools\Model\Auth_Settings' );
}

/**
 * Fügt den 2FA-Block in den Filter-Hook des Profil-Bearbeiten-Formulars ein.
 */
add_filter( 'cpc_usermeta_change_filter', 'cpc_2fa_add_profile_section', 10, 3 );
function cpc_2fa_add_profile_section( $tabs, $atts, $user_id ) {
	if ( ! cpc_2fa_is_available() ) {
		return $tabs;
	}

	// Nur für den eigenen Account anzeigen
	if ( ! is_user_logged_in() || get_current_user_id() !== (int) $user_id ) {
		return $tabs;
	}

	$settings = \CP_Defender\Module\Advanced_Tools\Model\Auth_Settings::instance();
	if ( empty( $settings->enabled ) ) {
		return $tabs;
	}

	$allow_app   = ! empty( $settings->allowAppAuth );
	$allow_email = ! empty( $settings->allowEmailAuth );

	if ( ! $allow_app && ! $allow_email ) {
		return $tabs;
	}

	$is_on       = (bool) \CP_Defender\Module\Advanced_Tools\Component\Auth_API::isUserEnableOTP( $user_id );
	$method      = $is_on ? \CP_Defender\Module\Advanced_Tools\Component\Auth_API::getEffectiveUserAuthMethod( $user_id ) : '';
	$backup_data = $is_on ? get_user_meta( $user_id, 'defenderBackupCode', true ) : null;
	$backup_code = ( is_array( $backup_data ) && ! empty( $backup_data['code'] ) ) ? $backup_data['code'] : '';

	ob_start();
	?>
	<div class="cpc_2fa_wrapper" id="cpc_2fa_wrapper"
		data-enabled="<?php echo $is_on ? '1' : '0'; ?>"
		data-method="<?php echo esc_attr( $method ); ?>"
		data-allow-app="<?php echo $allow_app ? '1' : '0'; ?>"
		data-allow-email="<?php echo $allow_email ? '1' : '0'; ?>">

		<strong><?php esc_html_e( 'Zwei-Faktor-Authentifizierung', CPC2_TEXT_DOMAIN ); ?></strong>

		<div id="cpc_2fa_status_text" style="margin:8px 0 10px;">
			<?php if ( $is_on ) : ?>
				<span class="cpc_2fa_badge cpc_2fa_active"><?php esc_html_e( 'Aktiv', CPC2_TEXT_DOMAIN ); ?></span>
				<?php if ( $method === \CP_Defender\Module\Advanced_Tools\Component\Auth_API::AUTH_METHOD_APP ) : ?>
					&mdash; <?php esc_html_e( 'Methode: Authenticator-App', CPC2_TEXT_DOMAIN ); ?>
				<?php elseif ( $method === \CP_Defender\Module\Advanced_Tools\Component\Auth_API::AUTH_METHOD_EMAIL ) : ?>
					&mdash; <?php esc_html_e( 'Methode: E-Mail', CPC2_TEXT_DOMAIN ); ?>
				<?php endif; ?>
			<?php else : ?>
				<span class="cpc_2fa_badge cpc_2fa_inactive"><?php esc_html_e( 'Nicht aktiv', CPC2_TEXT_DOMAIN ); ?></span>
			<?php endif; ?>
		</div>

		<div id="cpc_2fa_message" class="cpc_2fa_msg" style="display:none;"></div>

		<?php if ( ! $is_on ) : ?>

			<?php if ( $allow_app ) : ?>
			<div id="cpc_2fa_app_setup_section" style="margin-bottom:8px;">
				<button type="button" id="cpc_2fa_show_qr" class="cpc_button">
					<?php esc_html_e( 'Per Authenticator-App aktivieren', CPC2_TEXT_DOMAIN ); ?>
				</button>
				<div id="cpc_2fa_qr_wrapper" style="display:none; margin-top:12px; padding:12px; background:#fafafa; border:1px solid #e0e0e0; border-radius:4px;">
					<p><?php esc_html_e( 'Scanne diesen QR-Code mit deiner Authenticator-App (z.B. Google Authenticator, Authy):', CPC2_TEXT_DOMAIN ); ?></p>
					<img id="cpc_2fa_qr_img" src="" alt="QR Code" style="display:block; width:200px; height:200px; margin:10px 0;" />
					<p><?php esc_html_e( 'Kein QR-Scanner? Gib diesen Schlüssel manuell in der App ein:', CPC2_TEXT_DOMAIN ); ?></p>
				<code id="cpc_2fa_secret_key" style="display:inline-block; padding:4px 10px; background:#f5f5f5; border:1px solid #ddd; border-radius:3px; font-size:0.95em; letter-spacing:2px; margin-bottom:10px;"></code>
				<p><?php esc_html_e( 'Gib anschließend den 6-stelligen Code aus der App ein:', CPC2_TEXT_DOMAIN ); ?></p>
					<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:6px;">
						<input type="text" id="cpc_2fa_app_code" maxlength="6" inputmode="numeric" pattern="\d{6}"
							placeholder="000000"
							style="width:110px; font-size:1.2em; text-align:center; padding:6px; border:1px solid #ccc; border-radius:3px;" />
						<button type="button" id="cpc_2fa_verify_app" class="cpc_button">
							<?php esc_html_e( 'Bestätigen &amp; aktivieren', CPC2_TEXT_DOMAIN ); ?>
						</button>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( $allow_email ) : ?>
			<div id="cpc_2fa_email_setup_section">
				<button type="button" id="cpc_2fa_enable_email" class="cpc_button">
					<?php esc_html_e( 'Per E-Mail aktivieren', CPC2_TEXT_DOMAIN ); ?>
				</button>
			</div>
			<?php endif; ?>

		<?php else : ?>

			<div id="cpc_2fa_active_section">
				<div id="cpc_2fa_backup_wrap" style="margin:0 0 10px;">
					<label><strong><?php esc_html_e( 'Backup-Code:', CPC2_TEXT_DOMAIN ); ?></strong></label><br/>
					<?php if ( $backup_code ) : ?>
					<code id="cpc_2fa_backup_code_display"
						style="display:inline-block; padding:4px 10px; background:#f5f5f5; border:1px solid #ddd; border-radius:3px; font-size:0.95em; margin:4px 0;">
						<?php echo esc_html( $backup_code ); ?>
					</code>
					<br/>
					<?php else : ?>
					<span id="cpc_2fa_backup_code_display" style="color:#999; font-size:0.9em;">
						<?php esc_html_e( 'Noch kein Backup-Code vorhanden.', CPC2_TEXT_DOMAIN ); ?>
					</span>
					<br/>
					<?php endif; ?>
					<button type="button" id="cpc_2fa_regen_backup" class="cpc_button cpc_button_secondary" style="margin-top:6px; font-size:0.9em;">
						<?php echo $backup_code
							? esc_html__( 'Neuen Backup-Code generieren', CPC2_TEXT_DOMAIN )
							: esc_html__( 'Backup-Code generieren', CPC2_TEXT_DOMAIN ); ?>
					</button>
				</div>

				<button type="button" id="cpc_2fa_disable" class="cpc_button" style="background:#c0392b; border-color:#c0392b; color:#fff;">
					<?php esc_html_e( '2FA deaktivieren', CPC2_TEXT_DOMAIN ); ?>
				</button>
			</div>

		<?php endif; ?>
	</div>
	<?php
	$html = ob_get_clean();

	$tabs_array = get_option( 'cpc_comfile_tabs' );
	$tabs[]     = array(
		'tab'       => isset( $tabs_array['cpc_comfile_tab_password'] ) ? (int) $tabs_array['cpc_comfile_tab_password'] : 1,
		'html'      => $html,
		'mandatory' => false,
	);

	return $tabs;
}

/* ── AJAX-Handler ─────────────────────────────────────────────────────────── */

/** QR-Code-URL ermitteln und zurückgeben */
add_action( 'wp_ajax_cpc_2fa_get_qr', 'cpc_2fa_ajax_get_qr' );
function cpc_2fa_ajax_get_qr() {
	check_ajax_referer( 'cpc-2fa-nonce', 'security' );

	if ( ! cpc_2fa_is_available() ) {
		wp_send_json_error( array( 'message' => __( 'PS Security nicht aktiv.', CPC2_TEXT_DOMAIN ) ) );
	}

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => __( 'Nicht angemeldet.', CPC2_TEXT_DOMAIN ) ) );
	}

	$user   = get_user_by( 'id', $user_id );
	$secret = \CP_Defender\Module\Advanced_Tools\Component\Auth_API::createSecretForCurrentUser();
	$title  = get_bloginfo( 'name' );
	$qr_url = \CP_Defender\Module\Advanced_Tools\Component\Auth_API::generateQRCode(
		$user->user_email,
		$secret,
		200,
		200,
		$title
	);

	wp_send_json_success( array(
		'qr'     => $qr_url,
		'secret' => $secret,
	) );
}

/** App-Code verifizieren und 2FA per App aktivieren */
add_action( 'wp_ajax_cpc_2fa_verify_enable_app', 'cpc_2fa_ajax_verify_enable_app' );
function cpc_2fa_ajax_verify_enable_app() {
	check_ajax_referer( 'cpc-2fa-nonce', 'security' );

	if ( ! cpc_2fa_is_available() ) {
		wp_send_json_error( array( 'message' => __( 'PS Security nicht aktiv.', CPC2_TEXT_DOMAIN ) ) );
	}

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => __( 'Nicht angemeldet.', CPC2_TEXT_DOMAIN ) ) );
	}

	$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
	if ( ! preg_match( '/^\d{6}$/', $code ) ) {
		wp_send_json_error( array( 'message' => __( 'Bitte gib einen gültigen 6-stelligen Code ein.', CPC2_TEXT_DOMAIN ) ) );
	}

	$secret = \CP_Defender\Module\Advanced_Tools\Component\Auth_API::getUserSecret( $user_id );
	if ( ! $secret ) {
		wp_send_json_error( array( 'message' => __( 'Kein Token gefunden. Bitte lade die Seite neu.', CPC2_TEXT_DOMAIN ) ) );
	}

	if ( ! \CP_Defender\Module\Advanced_Tools\Component\Auth_API::compare( $secret, $code ) ) {
		wp_send_json_error( array( 'message' => __( 'Der eingegebene Code ist nicht korrekt. Bitte versuche es erneut.', CPC2_TEXT_DOMAIN ) ) );
	}

	update_user_meta( $user_id, 'defenderAuthOn', 1 );
	update_user_meta( $user_id, 'defenderAuthMethod', \CP_Defender\Module\Advanced_Tools\Component\Auth_API::AUTH_METHOD_APP );
	$backup_code = \CP_Defender\Module\Advanced_Tools\Component\Auth_API::createBackupCode( $user_id );

	wp_send_json_success( array(
		'message'     => __( '2FA per Authenticator-App wurde aktiviert!', CPC2_TEXT_DOMAIN ),
		'backup_code' => $backup_code,
	) );
}

/** 2FA per E-Mail aktivieren */
add_action( 'wp_ajax_cpc_2fa_enable_email', 'cpc_2fa_ajax_enable_email' );
function cpc_2fa_ajax_enable_email() {
	check_ajax_referer( 'cpc-2fa-nonce', 'security' );

	if ( ! cpc_2fa_is_available() ) {
		wp_send_json_error( array( 'message' => __( 'PS Security nicht aktiv.', CPC2_TEXT_DOMAIN ) ) );
	}

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => __( 'Nicht angemeldet.', CPC2_TEXT_DOMAIN ) ) );
	}

	update_user_meta( $user_id, 'defenderAuthOn', 1 );
	update_user_meta( $user_id, 'defenderAuthMethod', \CP_Defender\Module\Advanced_Tools\Component\Auth_API::AUTH_METHOD_EMAIL );
	$backup_code = \CP_Defender\Module\Advanced_Tools\Component\Auth_API::createBackupCode( $user_id );
	$email       = \CP_Defender\Module\Advanced_Tools\Component\Auth_API::getBackupEmail( $user_id );

	wp_send_json_success( array(
		'message'     => sprintf(
			/* translators: %s: E-Mail-Adresse */
			__( '2FA per E-Mail aktiviert. Codes werden an %s gesendet.', CPC2_TEXT_DOMAIN ),
			esc_html( $email )
		),
		'backup_code' => $backup_code,
	) );
}

/** 2FA deaktivieren */
add_action( 'wp_ajax_cpc_2fa_disable', 'cpc_2fa_ajax_disable' );
function cpc_2fa_ajax_disable() {
	check_ajax_referer( 'cpc-2fa-nonce', 'security' );

	if ( ! cpc_2fa_is_available() ) {
		wp_send_json_error( array( 'message' => __( 'PS Security nicht aktiv.', CPC2_TEXT_DOMAIN ) ) );
	}

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => __( 'Nicht angemeldet.', CPC2_TEXT_DOMAIN ) ) );
	}

	delete_user_meta( $user_id, 'defenderAuthOn' );
	delete_user_meta( $user_id, 'defenderAuthMethod' );
	delete_user_meta( $user_id, 'defenderAuthSecret' );
	delete_user_meta( $user_id, 'defenderBackupCode' );

	wp_send_json_success( array(
		'message' => __( 'Zwei-Faktor-Authentifizierung wurde deaktiviert.', CPC2_TEXT_DOMAIN ),
	) );
}

/** Backup-Code neu generieren */
add_action( 'wp_ajax_cpc_2fa_regen_backup', 'cpc_2fa_ajax_regen_backup' );
function cpc_2fa_ajax_regen_backup() {
	check_ajax_referer( 'cpc-2fa-nonce', 'security' );

	if ( ! cpc_2fa_is_available() ) {
		wp_send_json_error( array( 'message' => __( 'PS Security nicht aktiv.', CPC2_TEXT_DOMAIN ) ) );
	}

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => __( 'Nicht angemeldet.', CPC2_TEXT_DOMAIN ) ) );
	}

	if ( ! \CP_Defender\Module\Advanced_Tools\Component\Auth_API::isUserEnableOTP( $user_id ) ) {
		wp_send_json_error( array( 'message' => __( '2FA ist nicht aktiv.', CPC2_TEXT_DOMAIN ) ) );
	}

	$code = \CP_Defender\Module\Advanced_Tools\Component\Auth_API::createBackupCode( $user_id );

	wp_send_json_success( array(
		'message'     => __( 'Neuer Backup-Code generiert. Bewahre ihn sicher auf!', CPC2_TEXT_DOMAIN ),
		'backup_code' => $code,
	) );
}
