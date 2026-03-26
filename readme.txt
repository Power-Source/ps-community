=== PS Community ===
Contributors: DerN3rd
Tags: wp community, social network, social networking, social media, cp-community, wp community, community
Requires at least: 4.9
Tested up to: 6.8.1
ClassicPress: 2.7.0
Stable tag: 1.1.3
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

**Dies ist das ultimative Plugin für soziale Netzwerke für ClassicPress.** Du kannst auf Deiner ClassicPress-Webseite Dein eigenes soziales Netzwerk erstellen.

Mit Profilen, Aktivitäten (Pinnwand), unbegrenzten Foren, Freunden, E-Mail-Benachrichtigungen und vielem mehr ist es perfekt für Vereine, Schulen, Interessengruppen, die Interaktion mit Kunden, Support-Seiten, Spieleseiten, Dating-Seiten – nur durch Deine Vorstellungskraft begrenzt!

Füge einfach das Plugin hinzu, klicken auf eine Schaltfläche und Du hast ganz einfach Dein eigenes soziales Netzwerk.

**Unglaublich kompatibel**

Unglaublich kompatibel mit Themes und Plugins. Weitere Informationen, einschließlich zusätzlicher Plugins für PS Community, findest Du unter https://psource.eimen.net//.

**Massiv anpassbar**

Möchtest Du etwas ändern, das Layout, den Text, die Schaltflächenbeschriftungen? Die wahre Stärke von PS Community liegt in Shortcodes mit zahlreichen Optionen, mit denen Du nahezu alles ändern und Deine sozialen Netzwerkseiten so gestalten kannst, wie Du es möchtest!

**Mehrsprachige Webseite?**

Kein Problem! Ändere ganz einfach den gesamten Text, den Deine Benutzer sehen, über die Optionen. Verwendest Du WPML? Funktioniert auch problemlos mit diesem Plugin!

[POWERED BY PSOURCE](https://psource.eimen.net/)

== Neue Module und Shortcodes ==

Die folgenden Funktionen sind über den Tab **PS Community > Einstellungen > Funktionen** als Core-Module aktivierbar:

* Mitgliederverzeichnis (`core-members`)
* Events (`core-events`)
* Einladungen (`core-invite`)
* Lounge-Modus (als Teil der Aktivitätswall, kein eigenes Core-Modul)

=== Mitgliederverzeichnis ===

Das Mitgliederverzeichnis wird über den Shortcode auf einer Seite ausgegeben (z.B. Seite "Mitglieder"). Ist das Modul **Mitgliederverzeichnis** aktiviert, kannst Du unter **PS Community > Einstellungen > System-Optionen** eine bestehende Seite auswählen oder direkt eine Seite erstellen lassen, bei der `[cpc-members-directory]` automatisch eingefügt wird.

Shortcodes:

* `[cpc-members]`
* `[cpc-members-directory]`

Wichtige Attribute:

* `per_page` (5-100, Standard: 24)
* `role` (z.B. subscriber, editor)
* `show_search` (1/0)
* `show_atoz` (1/0)
* `show_last_active` (1/0)
* `show_actions` (1/0, zeigt Profil/Freund/PM Aktionen wenn verfügbar)
* `order` (ASC/DESC)

Beispiel:

`[cpc-members-directory per_page="30" show_search="1" show_atoz="1" show_actions="1" order="ASC"]`

=== Events (Hybrid intern + PS Events) ===

Shortcode:

* `[cpc-events]`

Wichtige Attribute:

* `limit` (1-100, Standard: 12)
* `upcoming` (1 = nur kommende, 0 = auch vergangene)

Beispiel:

`[cpc-events limit="10" upcoming="1"]`

Provider-Steuerung im Admin:

* **Events-Provider**: Auto, Intern, Extern (PS Events)

Hinweis: Bei externer Nutzung wird das Plugin **events-and-bookings** (PS Events) automatisch erkannt. Als externer Shortcode wird bevorzugt `eab_archive`, dann `eab_calendar` verwendet. Falls kein externer Shortcode rendert, erfolgt ein Fallback über den CPT `psource_event`.

=== Einladungen ===

Shortcode:

* `[cpc-invite]`

Optionales Attribut:

* `redirect` (Ziel-URL im gleichen Netzwerk)

Beispiel:

`[cpc-invite redirect="https://example.com/community/"]`

Der Invite-Flow nutzt signierte Token (Transient), E-Mail-Validierung und sichere Redirect-Prüfung auf dieselbe Host-Domain.

=== Forum Q&A (Akzeptierte Antwort + Übersicht) ===

Im Forum kann der Themen-Ersteller (oder Forum-Admin) eine Antwort als **akzeptiert** markieren.

Neue Shortcodes:

* `[cpc-forum-unanswered]` (zeigt Themen ohne akzeptierte Antwort)
* `[cpc-forum-experts]` (zeigt Top-Nutzer nach akzeptierten Antworten)

Wichtige Attribute für `[cpc-forum-unanswered]`:

* `slug` (optional: nur ein Forum-Slug)
* `days` (Zeitraum in Tagen, Standard: 30)
* `max` (max. Einträge, Standard: 10)

Wichtige Attribute für `[cpc-forum-experts]`:

* `slug` (optional: nur ein Forum-Slug)
* `days` (Zeitraum in Tagen, Standard: 30)
* `max` (max. Einträge, Standard: 10)
* `show_rank` (1/0, Ranglabel anzeigen)

Hinweis zum Rangsystem:

* Die Expertenliste kann optional Ranglabels anzeigen (Rookie/Helper/Pro/Master), basierend auf Anzahl akzeptierter Antworten im Zeitraum.

== ChangeLog ==

= 1.1.3 =

* Hinzugefügt: Neues Core-Modul **Mitgliederverzeichnis** inkl. Shortcodes `[cpc-members]` und `[cpc-members-directory]`
* Hinzugefügt: Neues Core-Modul **Events** mit Hybrid-Rendering (intern + PS Events) über `[cpc-events]`
* Hinzugefügt: Neues Core-Modul **Einladungen** mit signiertem Token-Flow und Shortcode `[cpc-invite]`
* Hinzugefügt: Forum-Q&A mit akzeptierter Antwort inkl. Auswertung über `[cpc-forum-unanswered]` und `[cpc-forum-experts]`
* Verbesserung: Expertenliste im Forum optional mit Ranglabels (Rookie/Helper/Pro/Master)

= 1.1.2 =

* Fix: Beim Erstellen von Gruppen im Backend (Dashboard/CPT) wird der Ersteller jetzt zuverlässig als Gruppen-Admin-Mitglied angelegt
* Fix: Bestehende Gruppen werden beim Speichern automatisch repariert, falls dem Ersteller die Admin-Mitgliedschaft fehlt oder falschen Status/Rolle hat
* Fix: Wenn der Gruppenersteller seiner eigenen Gruppe beitritt, wird er korrekt als Admin statt als normales Mitglied geführt
* UX-Fix: Die automatische Projects-Widget-Sidebar (`cpc-projects`) wurde entfernt; das Task-Widget kann wie gewohnt in bestehenden Sidebars platziert werden
* UX-Fix: Projekt-Task-Tab startet standardmäßig mit Status-Filter **Offen** statt **Status/Alle**
* Verbesserung: Tasks werden im Projekt-Tab konsistent mit **Offen zuerst** und danach **höchste Priorität zuerst** dargestellt
* Fix: Kein unerwünschtes Auto-Scrollen mehr bei URLs mit leerem Hash (`...&cpc_project_section=tasks#`), Scroll nur noch bei explizitem Task-Anchor
* Fix: Cursor wird in Textfeldern wieder angezeigt
* Hinzugefügt: Chat-Status im Profil bei aktivierter PS Chat Integration
* Hinzugefügt: Erweiterte Profil-Privatsphäre-Optionen
* Hinzugefügt: Events-Hybridsteuerung im Funktionen-Tab (Provider-Modus: Auto/Intern/Extern) inkl. Auswahl eines bevorzugten PS-Events-Shortcodes (eab_*)
* Hinzugefügt: Forum-Q&A mit akzeptierter Antwort direkt im Thema (inkl. Markieren/Entfernen)
* Hinzugefügt: Neue Forum-Shortcodes `[cpc-forum-unanswered]` und `[cpc-forum-experts]`
* Hinzugefügt: Optionales Experten-Ranglabel (Rookie/Helper/Pro/Master) in `[cpc-forum-experts]`

= 1.1.1 =

* Fix: Speichern in Dokumente/Projekte/Medien Tabs leitet nicht mehr auf `/wp-admin/admin-ajax.php` ("0"-Seite) um
* Redirect-Handling fuer Frontend-Formulare zentralisiert und gegen AJAX-Redirect-Ziele abgesichert
* Profil- und Gruppen-Tabs: Speichern bleibt im erwarteten Seitenkontext mit korrekter Notice
* Verbesserung: Ordner-Erkennungssystem mit redundanten Meta-Markern für erhöhte Zuverlässigkeit
* Ordner werden nun explizit mit Meta-Key gekennzeichnet, fallback auf empty post_content
* Fix: Ordner-Status bleibt konsistent beim Bearbeiten von Dokumenten (Inhaltsänderung zu/von Ordner)
* Verbesserung: Folder-Rendering in Profil- und Gruppen-Tabs zeigt neu erstellte Ordner korrekt an
* **UX-Fix: Ordner zeigen bei der Bearbeitung keinen Content-Editor mehr an** - nur Titel und Einstellungen
* Neu: Medien, Dokumente und Projekte werden zentral über den Core-Funktionen-Tab gesteuert (`core-media`, `core-docs`, `core-projects`)
* Fix: Gruppen lassen sich im Community-Dashboard wieder korrekt deaktivieren (kein erzwungenes Reaktivieren in Multisite)
* Bereinigung: Redundante "Modul aktivieren"-Schalter in Medien-, Dokumente- und Projekte-Tab entfernt, um Doppelsteuerung zu vermeiden
* Fix: Aktivität Plus Link-Vorschau bleibt nach dem Veröffentlichen im Stream als Card erhalten (Titel/Beschreibung/Bild), statt nur als nackter Link
* UX-Fix: Aktivität Plus Editor wird nach erfolgreichem Posten sauber zurückgesetzt (Link-/Video-Vorschau und Eingabefelder werden geleert)
* Fix: Projekt-Task-Kommentar-Submit im Gruppenkontext wieder stabil (kein fehlerhafter GET-Fallback mehr auf Group-URL)
* Verbesserung: Projekt-Tab „Aktivität" zeigt jetzt die projektbezogenen Aktivitätsmeldungen aller Nutzer mit direkten Links
* Neu: Projekt-Task-Events werden bei Gruppen-Projekten zusätzlich in den Gruppen-Aktivitätsstream geschrieben
* Neu: Dokumente/Ordner-Aktionen (Erstellen, Umbenennen, Aktualisieren, Löschen) werden im Gruppen-Aktivitätsstream protokolliert
* Fix: `cpc-forum-children` zeigt in der Count-Spalte wieder die Anzahl der Threads je Child-Forum statt Aktivität/aktive User
* Verbesserung: Task-Widget „Meine Tasks" zeigt nur **offene Tasks** sortiert nach Priorität und Deadline statt zuletzt geändert
* UX-Fix: Task-Widget **synchronisiert Status sofort** – abgeschlossene Tasks verschwinden aus dem Widget nach Reload
* Verbesserung: Task-Widget zeigt Restzeit zur Deadline statt „hinzugefügt vor..." – einfacher Überblick über dringende Aufgaben
* Neu: Task-Links im Widget und in Aktivitätsmeldungen **navigieren direkt zum Task-Tab** und scrollen zur Task
* Fix: Projekt-Deep-Linking mit `cpc_project_section=tasks` Query-Parameter öffnet automatisch Tasks-Tab mit Hash-Scroll zum Task
* Fix: Task-Kommentare lassen sich jetzt wieder löschen (AJAX-Handler comment-type Typ-Mismatch und Validierungsfehler behoben)
* UX: Alle nervigen Bestätigungs-Dialoge bei Lösch-Operationen (Tasks, Kommentare, Dateien, Projekte) entfernt
* Fix: Dokument-Editor in Edit-Form war nicht sichtbar (CSS-Wrapper-Fehler für TinyMCE behoben)
* UX-Fix: Task-Liste mit korrekter `<div>`-Struktur statt `<li>`/`<ul>` für konsistentes HTML-Rendering
* Neu: Dokumente können direkt auf der Dokumente-Übersichtsseite erstellt werden - mit Zuordnung zu eigenes Profil oder Gruppen
* UX-Verbesserung: Create-Form auf Übersichtsseite mit Gruppen-Auswahl (nur Gruppen, bei denen Nutzer Docs erstellen darf)
* Responsive Modal-Design für mobile Endgeräte optimiert
* Neu: Task-Deadline-Datumsauswahl mit vorausgefüllten Standardwerten
* Dashboard-Einstellungen für Task-Deadline-Offset (Standard: 7 Tage) und Standard-Uhrzeit (Standard: 09:00)
* Nutzer können Standard-Deadline-Offset und Zeit individuell anpassen und dann vor dem Speichern überschreiben
* Neu: Öffentliche Projekte-Übersichtsseite mit View Management (nur öffentliche für Gäste, private sichtbar für beteiligte User)
* Dashboard-Einstellungen für Projekte-Directory-Seite, Titel und Items pro Seite
* Shortcodes `[cpc-projects-directory]` und `[cpc-project-directory]` für Übersichtsseite
* Archive-Template für Projekte-Übersicht mit Suche und Pagination
* Neu: Globale Aktivitätswall unter Aktivität Plus mit eigener Seite, Shortcode `[cpc-activity-wall]` und automatischer Seitenausgabe
* Neue Wall-Optionen: öffentliche Gruppenposts einbeziehen, Systemmeldungen ausblenden und Push-Hook für neue globale Posts

= 1.1.0 =

* Neue globale Mediathek (Galerie- und Medienverzeichnis) mit eigener Seite im Frontend
* Neue Einstellungen fuer Mediathek-Seite und Anzahl Elemente pro Seite im Medien-Admin
* Neue Shortcodes `cpc-media-directory` und `cpc-gallery-directory`
* Mediathek mit Tabs (Galerien/Medien), Suche, Typ-Filter und Pagination
* Automatisches Rendern der Mediathek auf der konfigurierten Seite (ohne zusaetzlichen Shortcode)
* Einzelne Galerie-Seiten rendern nun den kompletten Galerieinhalt direkt auf der Single-View
* Gruppenmodule Medien und Projekte sind global standardmaessig deaktiviert und koennen im Admin gezielt aktiviert werden
* Bei Gruppenerstellung sind Medien und Projekte standardmaessig deaktiviert und koennen pro Gruppe optional im Einstellungen-Tab aktiviert werden
* Gruppen-Einstellungen um neue Sektion "Optionale Module" (Medien/Projekte) inkl. AJAX-Speicherung erweitert
* Gruppen-Tabs fuer Medien und Projekte respektieren jetzt konsequent die jeweilige pro-Gruppen-Aktivierung
* Admin-Einstellungstabs fuer Medien, Dokumente und Projekte mit aussagekraeftigen Dashicons versehen
* Projekte: "Projekt oeffnen" bleibt im Gruppen-Kontext und rendert die Projekt-Detailansicht im Gruppen-Tab statt ausserhalb auf der CPT-URL

= 1.0.9 =

* Gruppen-Einstellung „Gruppenerstellung erlauben“ korrigiert (Checkbox bleibt nach Speichern/Neuladen zuverlässig aktiv oder deaktiviert)
* Gruppen-Tabschaltung stabilisiert (Fallback auf reguläre Navigation bei fehlgeschlagenem AJAX)
* Fatal Error im Gruppen-Mitglieder-Tab behoben (`membership_id`-Zuweisung auf Array in `lib_groups.php`)
* Mehrere Forums-Berechtigungsprüfungen korrigiert (`=` durch `==` in Bedingungen ersetzt)* Activity Plus für Gruppen-Aktivität implementiert:
* Admin-Einstellung für Gruppen-Upload-Limit (Standard: 100 MB pro Gruppe)
* Separater Group-Cloud-Speicherpfad `/cpc-pro-content/groups/{group-slug}/activity/`
* Gruppen-Aktivitätsformular mit vollständiger Activity Plus UI (Bild/Link/Video-Uploads)
* AJAX-Handler für Datei-Uploads mit FormData-Support erweitert
* Automatische Bereinigung von Gruppen-Medien bei Gruppen-Löschung
* Activity Plus Assets (CSS/JS) in Gruppen-Context geladen
* JavaScript Event-Handler global gemacht für User- und Gruppen-Aktivität
* Theme-Classes korrigiert für konsistentes Design (cpcap-theme-*, cpcap-alignment-*)

= 1.0.8 =

* Activity Plus Integration für Aktivitäten ergänzt (Bilder, Link-Vorschau, Video-Embed)
* Einstellungen für Activity Plus im Admin ergänzt (Aktivierung, Features, Theme, Ausrichtung)
* Upload- und Bereinigungslogik für Activity Plus in PS-Community-Struktur integriert
* Jobboard-Integration in Profil-Tab ergänzt (Startseite, Meine Jobs, Mein Profil, Jobboard, Expertenboard)
* Navigation im Jobboard-Profil-Tab vollständig intern umgesetzt (inkl. AJAX-Sektionen und Formular-Redirects)
* Assets für Jobboard-Sektionen im Profil-Tab ergänzt (inkl. Expert-Form, Social-Icons und Anhänge/Uploader)
* Unterstützung für Jobboardguthaben-Menüpunkt im Profil-Tab ergänzt (über bestehende Jobboard-Hooks)

= 1.0.7 =

* PS Chat Integration
* Einige Bugfixes

= 1.0.6 =

* Gruppenfunktion BETA
* Kleinere Bugfixes
* Weiteres Jquery UI ersetzt

= 1.0.5 =

* Fehlerbehebung WYSIWYG/BBCODE Editor

= 1.0.4 =

* Wir haben einige Permalink-Bugs behoben im Forum
* Im Forum steht jetzt ein WYSIWYG Editor und ein BBCODE Editor zur Verfügung, wähle in den Forum-Einstellungen.
* Thickbox entfernt und ersetzt für bessere ClassicPress Kompatibilität und mehr Sicherheit.

= 1.0.3 =

* Fix: Bug im User-wp_redirect
* Better Responsive

= 1.0.2 =

* Fix: Implicit conversion
* Update: Updater
* Fix some old PhP

= 1.0.1 =

* CSS Anpassung Einstellungs-Screen
* Updater 1.3
* Links aktualisiert
* Fix: Creation of dynamic property

= 1.0.0 =
* Release
