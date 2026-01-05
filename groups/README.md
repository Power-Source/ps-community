# PS Community - Gruppenfunktion

## Übersicht

Die Gruppenfunktion wurde nach dem gleichen Muster wie die Foren implementiert und ist vollständig in das PS Community Plugin integriert.

## Custom Post Types

### 1. cpc_group
- **Zweck:** Hauptgruppen
- **Features:** 
  - Title (Gruppenname)
  - Editor (Beschreibung)
  - Thumbnail (Gruppenbild)
  - Hierarchie (Parent-Gruppen möglich)
  - Comments (Kommentare)

**Meta-Felder:**
- `cpc_group_type` - public|private|hidden
- `cpc_group_creator` - User ID des Erstellers
- `cpc_group_member_count` - Anzahl aktiver Mitglieder (gecached)
- `cpc_group_updated` - Timestamp der letzten Aktivität

### 2. cpc_group_members
- **Zweck:** Gruppenmitgliedschaften
- **Features:** Verknüpfung User ↔ Group

**Meta-Felder:**
- `cpc_member_user_id` - User ID
- `cpc_member_group_id` - Group ID
- `cpc_member_role` - member|moderator|admin
- `cpc_member_status` - active|pending|banned
- `cpc_member_joined` - Beitrittsdatum (Timestamp)

## Gruppentypen

1. **Öffentlich (public)**
   - Jeder kann die Gruppe sehen
   - Jeder kann direkt beitreten
   - Erscheint in allen Listen

2. **Privat (private)**
   - Jeder kann die Gruppe sehen
   - Beitritt muss von Admin/Moderator genehmigt werden
   - Status: "pending" bis zur Genehmigung

3. **Versteckt (hidden)**
   - Nur Mitglieder können die Gruppe sehen
   - Nicht in öffentlichen Listen sichtbar
   - Nur per Einladung

## Shortcodes

### [cpc-groups]
Zeigt eine Liste aller Gruppen an.

**Parameter:**
- `type` - all|public|private|hidden (Standard: all)
- `columns` - Anzahl der Spalten (Standard: 2)
- `show_avatar` - true|false (Standard: true)
- `avatar_size` - Größe in Pixeln (Standard: 50)
- `show_description` - true|false (Standard: true)
- `description_length` - Anzahl Wörter (Standard: 150)
- `show_member_count` - true|false (Standard: true)
- `show_join_button` - true|false (Standard: true)
- `search` - true|false (Standard: true)
- `limit` - Anzahl Gruppen, -1 für alle (Standard: -1)
- `orderby` - title|date|member_count (Standard: title)
- `order` - ASC|DESC (Standard: ASC)

**Beispiel:**
```
[cpc-groups type="public" columns="3" limit="9"]
```

### [cpc-group-single]
Zeigt Details einer einzelnen Gruppe an.

**Parameter:**
- `group_id` - ID der Gruppe (optional, nutzt aktuelle Seite)
- `show_avatar` - true|false (Standard: true)
- `avatar_size` - Größe in Pixeln (Standard: 100)
- `show_description` - true|false (Standard: true)
- `show_members` - true|false (Standard: true)
- `show_actions` - true|false (Standard: true)

**Beispiel:**
```
[cpc-group-single]
```

### [cpc-group-members]
Zeigt die Mitglieder einer Gruppe an.

**Parameter:**
- `group_id` - ID der Gruppe (optional)
- `role` - admin|moderator|member (optional, zeigt nur diese Rolle)
- `columns` - Anzahl der Spalten (Standard: 4)
- `show_avatar` - true|false (Standard: true)
- `avatar_size` - Größe in Pixeln (Standard: 50)
- `show_role` - true|false (Standard: true)
- `limit` - Anzahl Mitglieder, -1 für alle (Standard: -1)

**Beispiel:**
```
[cpc-group-members role="admin" columns="3"]
```

### [cpc-my-groups]
Zeigt die Gruppen des aktuell angemeldeten Benutzers an.

**Parameter:**
- `columns` - Anzahl der Spalten (Standard: 3)
- `show_avatar` - true|false (Standard: true)
- `avatar_size` - Größe in Pixeln (Standard: 50)
- `show_role` - true|false (Standard: true)

**Beispiel:**
```
[cpc-my-groups columns="2"]
```

### [cpc-group-create]
Zeigt ein Formular zum Erstellen einer neuen Gruppe an.

**Parameter:**
- `redirect` - URL zur Weiterleitung nach Erstellung (optional)

**Beispiel:**
```
[cpc-group-create redirect="/gruppen/"]
```

### [cpc-group-join-button]
Zeigt einen Beitreten/Verlassen-Button an.

**Parameter:**
- `group_id` - ID der Gruppe (optional)
- `join_text` - Text für Beitreten-Button (Standard: "Beitreten")
- `leave_text` - Text für Verlassen-Button (Standard: "Verlassen")

**Beispiel:**
```
[cpc-group-join-button join_text="Jetzt beitreten!"]
```

### [cpc-group-leave-button]
Zeigt nur einen Verlassen-Button an (nur wenn Mitglied).

**Parameter:**
- `group_id` - ID der Gruppe (optional)
- `text` - Button-Text (Standard: "Gruppe verlassen")

**Beispiel:**
```
[cpc-group-leave-button]
```

## PHP-Funktionen

### Helper-Funktionen (lib_groups.php)

```php
// Mitgliedschaft prüfen
cpc_is_group_member($user_id, $group_id)

// Rolle abrufen
cpc_get_group_member_role($user_id, $group_id)

// Admin-Rechte prüfen
cpc_is_group_admin($user_id, $group_id)

// Moderator-Rechte prüfen
cpc_is_group_moderator($user_id, $group_id)

// Sichtbarkeit prüfen
cpc_can_view_group($user_id, $group_id)

// Benutzer hinzufügen
cpc_add_group_member($user_id, $group_id, $role = 'member', $status = 'active')

// Benutzer entfernen
cpc_remove_group_member($user_id, $group_id)

// Benutzer-Gruppen abrufen
cpc_get_user_groups($user_id, $status = 'active')

// Gruppenmitglieder abrufen
cpc_get_group_members($group_id, $status = 'active', $role = '')

// Gruppen-Admins abrufen
cpc_get_group_admins($group_id)

// Mitgliederanzahl abrufen/aktualisieren
cpc_get_group_member_count($group_id)
cpc_update_group_member_count($group_id)

// Gruppenaktivität aktualisieren
cpc_update_group_activity($group_id)

// Gruppen suchen
cpc_search_groups($search_term, $type = '')
```

## AJAX-Actions

- `cpc_join_group` - Gruppe beitreten
- `cpc_leave_group` - Gruppe verlassen
- `cpc_create_group` - Neue Gruppe erstellen
- `cpc_update_member_role` - Mitgliederrolle ändern (Admin only)
- `cpc_remove_member` - Mitglied entfernen (Moderator+)
- `cpc_approve_member` - Mitglied genehmigen (Moderator+)

## Hooks & Filter

### Actions
- `cpc_group_created` - Nach Gruppenerstellung
- `cpc_user_joined_group` - Benutzer ist beigetreten
- `cpc_user_left_group` - Benutzer hat verlassen
- `cpc_member_approved` - Mitglied wurde genehmigt
- `cpc_groups_init_hook` - Bei Frontend-Initialisierung

### Filter
- `cpc_profile_tabs` - Fügt Gruppen-Tab zum Profil hinzu
- `cpc_profile_tab_content` - Gruppen-Inhalt im Profil
- `cpc_avatar_object_types` - Fügt Gruppen-Avatar-Support hinzu
- `cpc_avatar_group` - Gruppen-Avatar-Filter

## Integration mit anderen Modulen

### Activity Feed
Wenn `core-activity` aktiv ist, werden folgende Aktivitäten geloggt:
- Gruppe erstellt
- Gruppe beigetreten
- Gruppe verlassen

### Alerts
Wenn `core-alerts` aktiv ist:
- Beitrittsanfragen an Admins
- Genehmigungen an Benutzer

### Avatar
Wenn `core-avatar` aktiv ist:
- Gruppen-Avatare werden unterstützt
- Fallback auf Standard-Avatar

## Erste Schritte

1. **Gruppen aktivieren:**
   - Im Admin unter "PS Community" → "Einstellungen"
   - "Gruppen" aktivieren (standardmäßig aktiv)

2. **Seiten erstellen:**
   - **Gruppenliste:** Neue Seite mit `[cpc-groups]`
   - **Gruppe erstellen:** Neue Seite mit `[cpc-group-create]`
   - **Meine Gruppen:** Neue Seite mit `[cpc-my-groups]`

3. **Erste Gruppe:**
   - Im Admin unter "Gruppen" → "Neue hinzufügen"
   - Oder über Frontend-Formular mit `[cpc-group-create]`

4. **Menü einrichten:**
   - Links zu Gruppenliste, Gruppen erstellen, Meine Gruppen hinzufügen

## Dateistruktur

```
groups/
├── ajax_groups.php                     # AJAX-Handler
├── cpc_custom_post_group.php           # Custom Post Type: Gruppen
├── cpc_custom_post_group_members.php   # Custom Post Type: Mitgliedschaften
├── cpc_groups_admin.php                # Admin-Interface
├── cpc_groups_hooks_and_filters.php    # Hooks & Filter
├── cpc_groups_shortcodes.php           # Shortcodes
├── cpc_groups.css                      # Styles
├── cpc_groups.js                       # JavaScript
├── lib_groups.php                      # Helper-Funktionen
└── images/
    └── group-avatar-default.png        # Standard-Avatar
```

## Styling

Das CSS ist in `groups/cpc_groups.css` und folgt dem Plugin-Standard. Wichtige CSS-Klassen:

- `.cpc-groups-list` - Gruppenliste-Container
- `.cpc-groups-grid` - Grid-Layout
- `.cpc-group-card` - Einzelne Gruppenkarte
- `.cpc-group-single` - Einzelne Gruppenseite
- `.cpc-group-members` - Mitgliederliste
- `.cpc-group-join-btn` / `.cpc-group-leave-btn` - Buttons

## Kompatibilität

- ClassicPress 1.0+
- WordPress 5.0+ (falls gewünscht)
- Erfordert PS Community Core
- Optional: Activity, Alerts, Avatar Module

## Entwickelt nach

Diese Implementierung folgt exakt der Struktur der Forum-Funktion:
- Gleiche Dateiorganisation
- Gleiche Namenskonventionen
- Gleiche Shortcode-Patterns
- Gleiche AJAX-Struktur
