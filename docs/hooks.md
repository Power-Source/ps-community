---
layout: psource-theme
title: "PS Community Hooks"
---

<h2 align="center" style="color:#38c2bb;">📚 PS Community Hooks</h2>

<div class="menu">
  <a href="https://psource.eimen.net//cp-community/discussions" style="color:#38c2bb;">💬 Forum</a>
  <a href="https://psource.eimen.net//cp-community/releases" style="color:#38c2bb;">📝 Download</a>
</div>

## core_api.php

### Hook: cpc_activity_post_add_hook

**Beschreibung**: Wird ausgelöst, nachdem ein neuer ClassicPress-Beitrag des Typs `cpc_activity` eingefügt wurde. 
Ermöglicht es, zusätzliche Aktionen oder Benachrichtigungen zu generieren.

**Seit**: 14.12.2

### Parameter

| Name         | Typ    | Beschreibung                                                                 |
|--------------|--------|-----------------------------------------------------------------------------|
| `$the_post`  | array  | Optional. Weitere zu verarbeitende Daten aus `$_POST`.                      |
| `$the_files` | array  | Optional. Weitere zu verarbeitende Daten aus `$_FILES`.                     |
| `$new_id`    | int    | Die ID des neu erstellten ClassicPress-Beitrags.                               |

### Beispiel

```php
add_action('cpc_activity_post_add_hook', 'my_custom_function');
function my_custom_function($the_post, $the_files, $new_id) {
    // Dein Code hier, zum Beispiel eine Benachrichtigung senden
}
```

## cpc_admin.php

### Hook: cpc_admin_quick_start_form_save_hook

**Beschreibung**: Wird ausgelöst, wenn das Schnellstart-Formular im Admin-Bereich gespeichert wird.

**Seit**: 0.0.1

**Parameter**:
- `$_POST` (Typ: array) – Die Daten, die aus dem Formular übermittelt wurden.

### Beispiel:

```php
add_action('cpc_admin_quick_start_form_save_hook', 'my_custom_quick_start_save_function');
function my_custom_quick_start_save_function($post_data) {
    // Verarbeite die Daten aus dem Formular
    // Beispiel: Speichere eine benutzerdefinierte Option
    if (isset($post_data['my_custom_option'])) {
        update_option('my_custom_option', sanitize_text_field($post_data['my_custom_option']));
    }
}
```

### Hook: cpc_admin_quick_start_hook

**Beschreibung**: Wird verwendet, um zusätzlichen Inhalt oder Funktionen im Schnellstart-Bereich des Admin-Bereichs anzuzeigen.

**Seit**: 0.0.1

**Parameter**: Keine

### Beispiel:

```php
add_action('cpc_admin_quick_start_hook', 'my_custom_quick_start_content');
function my_custom_quick_start_content() {
    echo '<div class="quick-start-section">';
    echo '<h2>Meine benutzerdefinierte Schnellstart-Anleitung</h2>';
    echo '<p>Hier sind einige Anweisungen, wie du mit meinem benutzerdefinierten Modul beginnen kannst...</p>';
    echo '</div>';
}
```

### Hook: cpc_admin_setup_form_save_hook

**Beschreibung**: Wird ausgelöst, wenn das Setup-Formular im Admin-Bereich gespeichert wird.

**Seit**: 0.0.1

**Parameter**:

- `$_POST` (Typ: array) – Die Daten, die aus dem Formular übermittelt wurden.

### Beispiel:

```php
add_action('cpc_admin_setup_form_save_hook', 'my_custom_setup_save_function');
function my_custom_setup_save_function($post_data) {
    // Verarbeite die Daten aus dem Formular
    // Beispiel: Speichere eine benutzerdefinierte Option
    if (isset($post_data['my_setup_option'])) {
        update_option('my_setup_option', sanitize_text_field($post_data['my_setup_option']));
    }
}
```

### Hook: cpc_admin_setup_form_get_hook

**Beschreibung**: Wird ausgelöst, wenn Daten aus dem Setup-Formular im Admin-Bereich abgerufen werden.

**Seit**: 0.0.1

**Parameter**:

- `$_GET` (Typ: array) – Die Daten, die aus dem Formular abgerufen wurden.

### Beispiel:

```php
add_action('cpc_admin_setup_form_get_hook', 'my_custom_setup_get_function');
function my_custom_setup_get_function($get_data) {
    // Verarbeite die abgerufenen Daten
    // Beispiel: Zeige eine Benachrichtigung basierend auf einer GET-Variable
    if (isset($get_data['my_custom_notice'])) {
        echo '<div class="notice notice-success"><p>' . esc_html($get_data['my_custom_notice']) . '</p></div>';
    }
}
```

### Hook: cpc_admin_getting_started_hook

**Beschreibung**: Wird verwendet, um zusätzlichen Inhalt oder Anleitungen im "Erste Schritte"-Bereich des Admin-Bereichs anzuzeigen.

**Seit**: 0.0.1

**Parameter**: Keine

### Beispiel:

```php
add_action('cpc_admin_getting_started_hook', 'my_custom_getting_started_content');
function my_custom_getting_started_content() {
    echo '<div class="getting-started-section">';
    echo '<h2>Willkommen bei meinem benutzerdefinierten Modul!</h2>';
    echo '<p>Hier sind einige Anweisungen, wie du mit meinem Modul beginnen kannst...</p>';
    echo '</div>';
}
```

### Hook: cpc_admin_getting_started_shortcodes_save_hook

**Beschreibung**: Wird ausgelöst, wenn das Formular für die Shortcodes-Einstellungen im Admin-Bereich gespeichert wird.

**Seit**: 0.0.1

**Parameter**:
- `$_POST` (Typ: array) – Die Daten, die aus dem Formular übermittelt wurden.

### Beispiel:

```php
add_action('cpc_admin_getting_started_shortcodes_save_hook', 'my_custom_shortcodes_save_function');
function my_custom_shortcodes_save_function($post_data) {
    // Verarbeite die Daten aus dem Formular
    // Beispiel: Speichere eine benutzerdefinierte Option
    if (isset($post_data['my_custom_shortcodes_option'])) {
        update_option('my_custom_shortcodes_option', sanitize_text_field($post_data['my_custom_shortcodes_option']));
    }
}
```

### Hook: cpc_admin_getting_started_shortcodes_hook

**Beschreibung**: Wird verwendet, um zusätzlichen Inhalt oder Anleitungen im "Erste Schritte"-Bereich für Shortcodes im Admin-Bereich anzuzeigen.

**Seit**: 0.0.1

**Parameter**: Keine

### Beispiel:

```php
add_action('cpc_admin_getting_started_shortcodes_hook', 'my_custom_shortcodes_content');
function my_custom_shortcodes_content() {
    echo '<div class="getting-started-section">';
    echo '<h2>Willkommen zu den Shortcodes!</h2>';
    echo '<p>Hier sind einige nützliche Shortcodes, die du in deinen Seiten und Beiträgen verwenden kannst...</p>';
    echo '</div>';
}
```

### Hook: cpc_admin_getting_started_styles_save_hook

**Beschreibung**: Wird ausgelöst, wenn das Formular für die Stileinstellungen im Admin-Bereich gespeichert wird.

**Seit**: 0.0.1

**Parameter**:
- `$_POST` (Typ: array) – Die Daten, die aus dem Formular übermittelt wurden.

### Beispiel:

```php
add_action('cpc_admin_getting_started_styles_save_hook', 'my_custom_styles_save_function');
function my_custom_styles_save_function($post_data) {
    // Verarbeite die Daten aus dem Formular
    // Beispiel: Speichere eine benutzerdefinierte Option für Stile
    if (isset($post_data['my_custom_styles_option'])) {
        update_option('my_custom_styles_option', sanitize_text_field($post_data['my_custom_styles_option']));
    }
}
```

### Hook: cpc_admin_getting_started_styles_hook

**Beschreibung**: Wird verwendet, um zusätzlichen Inhalt oder Anleitungen im "Erste Schritte"-Bereich für Stile im Admin-Bereich anzuzeigen.

**Seit**: 0.0.1

**Parameter**: Keine

### Beispiel:

```php
add_action('cpc_admin_getting_started_styles_hook', 'my_custom_styles_content');
function my_custom_styles_content() {
    echo '<div class="getting-started-section">';
    echo '<h2>Willkommen zu den Stilen!</h2>';
    echo '<p>Hier sind einige hilfreiche Tipps zur Anpassung der Stile auf Deiner Seite...</p>';
    echo '</div>';
}
```

### Hook: cpc_admin_custom_css_form_save_hook

**Beschreibung**: Wird ausgelöst, wenn das Formular für benutzerdefiniertes CSS im Admin-Bereich gespeichert wird.

**Seit**: 0.0.1

**Parameter**:
- `$_POST` (Typ: array) – Die Daten, die aus dem Formular übermittelt wurden, einschließlich des benutzerdefinierten CSS.

### Beispiel:

```php
add_action('cpc_admin_custom_css_form_save_hook', 'my_custom_css_save_function');
function my_custom_css_save_function($post_data) {
    // Verarbeite die Daten aus dem Formular
    // Beispiel: Benachrichtige einen Administrator oder führe zusätzliche Validierungen durch
    if (isset($post_data['cpccom_custom_css'])) {
        // Hier könnten zusätzliche Verarbeitungen oder Benachrichtigungen erfolgen
        // Zum Beispiel: Admin benachrichtigen
        wp_mail('admin@example.com', 'CSS wurde aktualisiert', 'Das benutzerdefinierte CSS wurde aktualisiert.');
    }
}
```

### Hook: cpc_admin_custom_css_form_hook

**Beschreibung**: Wird verwendet, um zusätzlichen Inhalt oder Optionen im Formular für benutzerdefiniertes CSS im Admin-Bereich anzuzeigen.

**Seit**: 0.0.1

**Parameter**: Keine

### Beispiel:

```php
add_action('cpc_admin_custom_css_form_hook', 'my_custom_css_form_options');
function my_custom_css_form_options() {
    // Füge zusätzliche Optionen oder Erklärungen zum CSS-Formular hinzu
    echo '<tr><td colspan="2">';
    echo '<p>Hier kannst du zusätzliche Optionen oder Erklärungen zum benutzerdefinierten CSS hinzufügen.</p>';
    echo '</td></tr>';
}
```

## cpc_core.php

### Hook: cpc_forum_auto_close_hook

**Beschreibung**: Wird ausgelöst, wenn die Kommentare eines Forenbeitrags automatisch geschlossen werden.

**Seit**: unbekannt

**Parameter**:
- `int $post_id` – Die ID des Forenbeitrags, dessen Kommentare geschlossen wurden.

### Beispiel:

```php
add_action('cpc_forum_auto_close_hook', 'my_custom_auto_close_action');
function my_custom_auto_close_action($post_id) {
    // Verarbeite den geschlossenen Beitrag weiter
    // Beispiel: Benachrichtige den Administrator oder führe eine andere Aktion durch
    wp_mail('admin@example.com', 'Forum-Kommentar geschlossen', 'Die Kommentare zu dem Beitrag mit der ID ' . $post_id . ' wurden automatisch geschlossen.');
}
```

## ajax_activity.php

### Hook: cpc_activity_comment_add_hook

**Beschreibung**: Wird ausgelöst, nachdem ein neuer Kommentar zu einer Aktivität hinzugefügt wurde.

**Seit**: unbekannt

**Parameter**:
- `array $post_data` – Die Daten, die beim Hinzufügen des Kommentars gesendet wurden (aus `$_POST`).
- `int $comment_id` – Die ID des neu hinzugefügten Kommentars.

### Beispiel:

```php
add_action('cpc_activity_comment_add_hook', 'my_custom_comment_add_action', 10, 2);
function my_custom_comment_add_action($post_data, $comment_id) {
    // Verarbeite den neuen Kommentar weiter
    // Beispiel: Sende eine Benachrichtigung oder führe eine andere Aktion aus
    $comment_content = isset($post_data['comment_content']) ? $post_data['comment_content'] : '';
    $comment_author = isset($post_data['comment_author']) ? $post_data['comment_author'] : '';
    wp_mail('admin@example.com', 'Neuer Kommentar hinzugefügt', "Ein neuer Kommentar von $comment_author wurde hinzugefügt: $comment_content");
}
```

### Hook: cpc_activity_post_add_hook

**Beschreibung**: Wird ausgelöst, wenn ein neuer Beitrag zur Aktivität hinzugefügt wird. Dieser Hook wird verwendet, um Benachrichtigungen für neue Beiträge zu senden.

**Seit**: 0.0.1

**Parameter**:
- `array $the_post` – Die Daten des Beitrags (aus `$_POST` oder `get_post()`).
- `array $the_files` – Die Dateianhänge, die mit dem Beitrag verbunden sind.
- `int $new_id` – Die ID des neu hinzugefügten Beitrags.

### Beispiel:

```php
add_action('cpc_activity_post_add_hook', 'my_custom_post_add_alerts', 10, 3);
function my_custom_post_add_alerts($the_post, $the_files, $new_id) {
    // Verarbeite die Benachrichtigung hier weiter
    $author_id = $the_post['cpc_activity_post_author'];
    $message = sprintf('Neuer Beitrag von %s: %s', get_user_by('id', $author_id)->display_name, $the_post['cpc_activity_post']);
    wp_mail('admin@example.com', 'Neuer Aktivitätsbeitrag', $message);
}
```

## cpc_activity.php

### Hook: cpc_alert_add_hook

**Beschreibung**: Dieser Hook wird ausgelöst, nachdem eine neue Benachrichtigung (Alert) erstellt und gespeichert wurde. Er ermöglicht es anderen Plugins oder Funktionen, zusätzliche Aktionen auszuführen, sobald die Benachrichtigung erstellt wurde.

**Seit**: 0.0.1

**Parameter**:
- `int $recipient_id` – Die ID des Benachrichtigungsempfängers. Dies ist der Benutzer, der die Benachrichtigung erhält.
- `int $alert_id` – Die ID des neu erstellten Benachrichtigungsbeitrags.
- `string $url` – Die URL, die auf die Seite verweist, auf der der Benutzer die vollständige Benachrichtigung sehen kann.
- `string $message` – Die Nachricht, die in der Benachrichtigung enthalten ist.

### Beispiel:

```php
add_action('cpc_alert_add_hook', 'my_custom_alert_add_action', 10, 4);
function my_custom_alert_add_action($recipient_id, $alert_id, $url, $message) {
    // Führe zusätzliche Aktionen durch, z.B. Protokollierung oder zusätzliche Benachrichtigungen
    $recipient = get_user_by('id', $recipient_id);
    $log_message = sprintf('Benachrichtigung #%d für Benutzer %s erstellt. URL: %s, Nachricht: %s', $alert_id, $recipient->user_login, $url, $message);

    // Schreibe in ein Protokoll
    error_log($log_message);
}
```

## cpc_activity_shortcodes.php

### Hook: cpc_activity_init

**Beschreibung**: Dieser Hook wird verwendet, um JavaScript- und CSS-Ressourcen für die Aktivitäts-Komponente des Plugins zu registrieren und zu laden. Außerdem ermöglicht er es anderen Plugins oder Themes, zusätzliche Skripte und Stile einzufügen, indem der Hook cpc_activity_init_hook aufgerufen wird.

**Seit**: 0.0.1

**Parameter**: Keine

**Funktionsweise**:

- JavaScript einbinden: Lädt das Haupt-JavaScript für die Aktivitätsfunktionalität des Plugins und lokalisiert es, um PHP-Variablen für die clientseitige Nutzung bereitzustellen.
- CSS einbinden: Lädt das CSS für die Stilgestaltung der Aktivitätsfunktionalität des Plugins.
- Select2-Bibliothek: Lädt die Select2 JavaScript- und CSS-Dateien, die für Dropdown-Listen verwendet werden.
- Zusätzliche Hooks: Ermöglicht anderen Plugins oder Themes, zusätzliche Skripte oder Stile einzufügen.

### Beispiel:

```php
add_action('wp_enqueue_scripts', 'cpc_activity_init');
function cpc_activity_init() {
    // JavaScript-Datei einbinden
    wp_enqueue_script('cpc-activity-js', plugins_url('cpc_activity.js', __FILE__), array('jquery'));
    
    // JavaScript lokalisieren, um PHP-Variablen verfügbar zu machen
    wp_localize_script('cpc-activity-js', 'cpc_activity_ajax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'plugins_url' => plugins_url('', __FILE__),
        'activity_post_focus' => get_option('cpccom_activity_set_focus')
    ));
    
    // CSS-Datei einbinden
    wp_enqueue_style('cpc-activity-css', plugins_url('cpc_activity.css', __FILE__), array(), '1.0.0');
    
    // Select2-Bibliothek einbinden
    wp_enqueue_script('cpc-select2-js', plugins_url('../js/select2.js', __FILE__), array('jquery'), '4.0.13', true);
    wp_enqueue_style('cpc-select2-css', plugins_url('../js/select2.css', __FILE__), array(), '4.0.13');
    
    // Zusätzliche Hooks für andere Plugins oder Themes
    do_action('cpc_activity_init_hook');
}
```

**Hinweis**:

Dieser Hook stellt sicher, dass alle erforderlichen Ressourcen für die Aktivitätsfunktionalität geladen werden.
Der Hook cpc_activity_init_hook ermöglicht es anderen Entwicklern, zusätzliche Ressourcen hinzuzufügen, ohne die Kernfunktionalität zu überschreiben.

## lib_activity.php

### Hook: cpc_activity_post_add_hook

**Beschreibung**: Dieser Hook wird ausgelöst, nachdem ein neuer Aktivitätsbeitrag hinzugefügt wurde. Er ermöglicht es anderen Plugins oder Funktionen, zusätzliche Aktionen auszuführen, sobald der Beitrag erstellt und gespeichert wurde.

**Seit**: 0.0.1

**Parameter**:
- `array $post_data` – Ein Array, das die Daten des neuen Beitrags enthält, wie im `$_POST`-Array gesendet.
- `array $file_data` – Ein Array, das die Dateidaten enthält, die eventuell hochgeladen wurden, wie im `$_FILES`-Array gesendet.
- `int $post_id` – Die ID des neu erstellten Beitrags.

### Beispiel:

```php
add_action('cpc_activity_post_add_hook', 'my_custom_activity_post_add_action', 10, 3);
function my_custom_activity_post_add_action($post_data, $file_data, $post_id) {
    // Beispiel: Loggen der Post-ID und der Benutzer-ID
    $log_message = sprintf('Neuer Aktivitätsbeitrag #%d erstellt von Benutzer %d.', $post_id, get_current_user_id());
    error_log($log_message);

    // Beispiel: Sende Benachrichtigung an den Administrator
    $admin_email = get_option('admin_email');
    $subject = 'Neuer Aktivitätsbeitrag erstellt';
    $message = sprintf('Ein neuer Aktivitätsbeitrag mit der ID %d wurde erstellt.', $post_id);
    wp_mail($admin_email, $subject, $message);
}
```

**Details**:

**JavaScript- und CSS-Dateien einbinden**:

- `wp_enqueue_script('cpc-activity-js')` lädt das JavaScript für die Aktivität.
- `wp_localize_script()` macht PHP-Variablen im JavaScript verfügbar.
- `wp_enqueue_style('cpc-activity-css')` lädt das CSS für die Aktivität.
- `wp_enqueue_script('cpc-select2-js')` und `wp_enqueue_style('cpc-select2-css')` binden die Select2-Bibliothek ein.

**Aktivitätsbeitrag hinzufügen**:

- Ein neuer Beitrag wird erstellt und gespeichert, wenn die Aktion `cpc_activity_post_add` ausgeführt wird.
- Metadaten werden für den Beitrag gespeichert.
- Ein HTML-Block für den neuen Beitrag wird generiert, einschließlich Avatar, Metadaten, Beitragstext und Anhänge.
- Der Hook `cpc_activity_post_add_hook` wird ausgeführt, um weitere Aktionen zu ermöglichen.

**Verarbeitung von Shortcodes**:

- Der Inhalt des Beitrags wird formatiert, einschließlich Verlinkungen und Zitaten.
- Wenn Anhänge vorhanden sind, werden diese ebenfalls im HTML angezeigt.

**Endgültige Ausgabe**:

- Der formatierte HTML-Code für den neuen Aktivitätsbeitrag wird an die Seite ausgegeben.
- Dieser Hook ist nützlich, um nach der Erstellung eines neuen Aktivitätsbeitrags zusätzliche benutzerdefinierte Aktionen auszuführen, wie das Senden von Benachrichtigungen oder das Protokollieren von Ereignissen.

## cpc_alerts_admin.php

### Hook: cpc_alert_add_hook

**Beschreibung**: Dieser Hook wird ausgelöst, nachdem eine neue Benachrichtigung (Alert) erstellt und gespeichert wurde. Er ermöglicht es anderen Plugins oder Funktionen, zusätzliche Aktionen auszuführen, sobald die Benachrichtigung erstellt wurde.

**Seit**: 0.0.1

**Parameter**:
- `int $recipient_id` – Die ID des Benachrichtigungsempfängers. Dies ist der Benutzer, der die Benachrichtigung erhält.
- `int $alert_id` – Die ID des neu erstellten Benachrichtigungsbeitrags.
- `string $url` – Die URL, die auf die Seite verweist, auf der der Benutzer die vollständige Benachrichtigung sehen kann.
- `string $message` – Die Nachricht, die in der Benachrichtigung enthalten ist.

### Beispiel:

```php
add_action('cpc_alert_add_hook', 'my_custom_alert_add_action', 10, 4);
function my_custom_alert_add_action($recipient_id, $alert_id, $url, $message) {
    // Beispiel: Protokollieren der Benachrichtigungserstellung
    $log_message = sprintf('Benachrichtigung #%d für Benutzer %d erstellt. URL: %s, Nachricht: %s', $alert_id, $recipient_id, $url, $message);
    error_log($log_message);

    // Beispiel: Sende Benachrichtigung an den Administrator
    $admin_email = get_option('admin_email');
    $subject = 'Neue Benachrichtigung erstellt';
    $email_message = sprintf('Eine neue Benachrichtigung mit der ID %d wurde für Benutzer %d erstellt. URL: %s', $alert_id, $recipient_id, $url);
    wp_mail($admin_email, $subject, $email_message);
}
```

**Details**:

**Benachrichtigung erstellen**:

- Der Funktionsaufruf `cpc_com_insert_alert` erstellt einen neuen Benachrichtigungsbeitrag (cpc_alerts) mit den übergebenen Parametern.
- Die Benachrichtigung wird mit Titel, Inhalt, Status und weiteren Metadaten gespeichert.

**Metadaten aktualisieren**:

- Die Metadaten der Benachrichtigung werden aktualisiert, einschließlich des Empfängernamens, des Typs der Benachrichtigung und der Parameter.

**Status überprüfen**:

- Wenn der Status der Benachrichtigung auf publish gesetzt ist, wird das aktuelle Datum und eine Notiz zum Status hinzugefügt.

**Hook auslösen**:

Der Hook `cpc_alert_add_hook` wird mit den Parametern `recipient_id`, `new_alert_id`, `url` und `msg` ausgelöst, um zusätzliche Aktionen zu ermöglichen.

**Rückgabewert**:

- Die Funktion gibt die ID der neu erstellten Benachrichtigung zurück.

Dieser Hook ist nützlich, um nach der Erstellung einer neuen Benachrichtigung weitere benutzerdefinierte Aktionen durchzuführen, wie das Senden von E-Mails oder das Protokollieren von Ereignissen.

### Hook: `cpc_admin_getting_started_alerts`

**Beschreibung**: 
Diese Funktion zeigt die Einstellungsseite für Benachrichtigungen im Admin-Bereich von ClassicPress an. Sie bietet Optionen zur Konfiguration von E-Mail-Benachrichtigungen und zeigt Hilfetexte und Links zu nützlichen Plugins an.

**Seit**: 0.0.1

**Parameter**: Keine

**Rückgabewert**: Keine (die Funktion gibt HTML-Ausgabe direkt aus)

**Details**:

1. **Menüeintrag anzeigen**:
   - Die Funktion überprüft, ob die `cpc_expand`-POST-Variable gesetzt ist und ob sie dem aktuellen Menüeintrag entspricht. Falls ja, wird eine CSS-Klasse hinzugefügt, um das Menüelement mit einem Symbol zum Entfernen anzuzeigen.
   - Ein HTML-Element wird ausgegeben, das als Menüeintrag dient, der auf die Benachrichtigungseinstellungen verweist.

2. **Hilfetext und Setup-Inhalte anzeigen**:
   - Basierend auf der `cpc_expand`-POST-Variable wird der Abschnitt für die Benachrichtigungs-Einstellungen entweder angezeigt oder ausgeblendet.
   - Der Inhalt enthält allgemeine Hinweise zur Verwendung von E-Mail-Benachrichtigungen, einschließlich der Empfehlung, einen externen Mailserver zu verwenden, wenn ein hohes Volumen von E-Mails vorhanden ist.
   - Weitere Hinweise umfassen Empfehlungen für Plugins wie [Postman SMTP Mailer/Email Log](https://wordpress.org/plugins/postman-smtp/) und [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/).

3. **Einstellungen für Benachrichtigungen**:
   - Verschiedene Optionen können konfiguriert werden, darunter:
     - Deaktivierung von Benachrichtigungen
     - Häufigkeit von E-Mail-Benachrichtigungen
     - Maximale Anzahl von E-Mails pro Zyklus
     - Zusammenfassende E-Mail-Adresse für gesendete Benachrichtigungen
     - E-Mail-Adresse für detaillierte Cron-Berichte
     - "Von" Name und E-Mail-Adresse für Benachrichtigungen
     - Option zur Test-E-Mail
     - Aufbewahrung von Benachrichtigungsmeldungen
     - Erneutes Senden fehlgeschlagener Benachrichtigungen
     - Testbenachrichtigung hinzufügen

4. **Aktionen nach dem Formular**:
   - Nach dem Formular wird der Hook `cpc_alerts_admin_setup_form_hook` ausgelöst, um zusätzliche Anpassungen oder Erweiterungen durch andere Plugins oder Funktionen zu ermöglichen.

### Beispiel:

```php
// Beispiel: Hook für zusätzliche Einstellungen nach dem Formular
add_action('cpc_alerts_admin_setup_form_hook', 'my_custom_admin_alerts_setup');
function my_custom_admin_alerts_setup() {
    echo '<p>'.__('Hier könnten zusätzliche Optionen oder Hinweise angezeigt werden.', CPC2_TEXT_DOMAIN).'</p>';
}
```

**Verwendung**:

Die Funktion wird verwendet, um die Admin-Benutzeroberfläche für die Benachrichtigungseinstellungen zu erstellen und anzupassen. Sie sollte innerhalb des ClassicPress-Adminbereichs aufgerufen werden.

## cpc_alerts_shortcodes.php

### Hook: cpc_alerts_init_hook

**Beschreibung**: Wird ausgelöst, nachdem die Skripte und Stile für das `cpc_alerts` Plugin in die Warteschlange eingereiht wurden. Dieser Hook ermöglicht es Entwicklern, zusätzliche Skripte, Stile oder andere Initialisierungen durchzuführen.

**Seit**: Unbekannt

**Parameter**: Keine

### Beispiel:

```php
add_action('cpc_alerts_init_hook', 'my_custom_initialization');
function my_custom_initialization() {
    // Dein Code hier, zum Beispiel ein weiteres Script einreihen
    wp_enqueue_script('my-custom-js', plugins_url('my_custom_script.js', __FILE__), array('jquery'));
}
```
## ajax_activity.php

### Hook: cpc_activity_comment_add_hook

**Beschreibung**: Wird ausgelöst, nachdem ein neuer Kommentar im ClassicPress-Beitrag des Typs `cpc_activity_comment` eingefügt wurde. Dies ermöglicht es Entwicklern, zusätzliche Aktionen nach dem Hinzufügen eines Kommentars durchzuführen, z.B. das Aktualisieren von Caches oder das Senden von Benachrichtigungen.

**Seit**: Unbekannt

**Parameter**:
- `$_POST` (Typ: array) – Die Daten, die mit dem Kommentar übermittelt wurden, wie `post_id`, `comment_content`, und andere POST-Parameter.
- `$new_id` (Typ: int) – Die ID des neu erstellten Kommentars.

### Beispiel:

```php
add_action('cpc_activity_comment_add_hook', 'my_custom_comment_action', 10, 2);
function my_custom_comment_action($post_data, $comment_id) {
    // Dein Code hier, z.B. eine Benachrichtigung senden
    // $post_data enthält die POST-Daten, $comment_id ist die ID des neuen Kommentars
}
```

### Erklärung der `cpc_activity_comment_add` Funktion

- **Funktion**: Diese Funktion verarbeitet die Daten für einen neuen Kommentar, erstellt diesen Kommentar in der Datenbank und gibt das HTML für die Anzeige des Kommentars zurück.
- **Parameter**: Nimmt Daten aus `$_POST` entgegen, um den Kommentar zu erstellen.
- **Aktionen**:
  - `wp_insert_comment` fügt den Kommentar in die Datenbank ein.
  - `do_action('cpc_activity_comment_add_hook', $_POST, $new_id)` ermöglicht zusätzliche Aktionen nach dem Hinzufügen des Kommentars.
  - Gibt HTML für die Anzeige des Kommentars zurück oder `0` bei Fehlern.

## cpc_avatar_shortcodes.php

### Hook: cpc_avatar_init_hook

**Beschreibung**: Wird ausgelöst, nachdem die Skripte und Stile für das `cpc_avatar` Plugin in die Warteschlange eingereiht wurden. Dieser Hook ermöglicht Entwicklern, zusätzliche Skripte, Stile oder andere Initialisierungen vorzunehmen.

**Seit**: 0.0.1

**Parameter**: Keine

### Beispiel:

```php
add_action('cpc_avatar_init_hook', 'my_custom_avatar_initialization');
function my_custom_avatar_initialization() {
    // Dein Code hier, z.B. ein weiteres Script einreihen
    wp_enqueue_script('my-custom-avatar-js', plugins_url('my_custom_avatar_script.js', __FILE__), array('jquery'));
}
```

**Shortcode**: [cpc_avatar]

**Beschreibung**: Gibt das HTML für das Avatar des Benutzers zurück. Der Shortcode kann verwendet werden, um das Avatar eines bestimmten Benutzers anzuzeigen und es ermöglicht zusätzliche Optionen wie das Ändern des Avatars oder das Verlinken auf das Profil.

Seit: 0.0.1

Parameter:

user_id (Typ: int) – Die ID des Benutzers, dessen Avatar angezeigt werden soll. Standardmäßig wird das Avatar des aktuellen Benutzers angezeigt.
size (Typ: int|string) – Die Größe des Avatars in Pixeln oder als Prozentwert (z.B. '100px' oder '50%'). Standardwert ist 256.
change_link (Typ: bool) – Wenn true, wird ein Link zum Ändern des Avatars angezeigt. Standardwert ist false.
profile_link (Typ: bool) – Wenn true, wird der Avatar mit einem Link zum Profil des Benutzers versehen. Standardwert ist false.
change_avatar_text (Typ: string) – Der Text des Links zum Ändern des Avatars. Standardwert ist 'Bild ändern'.
change_avatar_title (Typ: string) – Der Titel des Links zum Ändern des Avatars. Standardwert ist 'Bild hochladen und zuschneiden, um es anzuzeigen'.
avatar_style (Typ: string) – Der Stil des Avatars. Mögliche Werte sind popup oder andere benutzerdefinierte Stile. Standardwert ist 'popup'.
popup_width (Typ: int) – Die Breite des Popups zum Ändern des Avatars. Standardwert ist 750.
popup_height (Typ: int) – Die Höhe des Popups zum Ändern des Avatars. Standardwert ist 450.
styles (Typ: bool) – Ob Stile angewendet werden sollen. Standardwert ist true.
check_privacy (Typ: bool) – Ob die Sichtbarkeit des Profils überprüft werden soll. Standardwert ist false.
after (Typ: string) – Inhalt, der nach dem Avatar eingefügt wird.
before (Typ: string) – Inhalt, der vor dem Avatar eingefügt wird.

### Beispiel:

echo do_shortcode('[cpc_avatar user_id="123" size="100" change_link="true" profile_link="true"]');

### Erklärung:

cpc_avatar_init: Diese Funktion lädt die notwendigen Skripte und Stile für das cpc_avatar Plugin. Sie wird beim Laden des Footers initialisiert.
cpc_avatar: Dieser Shortcode generiert HTML für die Anzeige eines Avatars, basierend auf den angegebenen Attributen. Er kann auch Links zum Profil oder zur Avatar-Änderung hinzufügen.

### Entwickler-Ressourcen:

- **Shortcodes:** [PS Community Shortcodes](shortcodes.md)
- **Hooks:** [PS Community Hooks](hooks.md)
- **Filter:** [PS Community Filter](filter.md)











