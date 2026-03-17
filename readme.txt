=== PS Community ===
Contributors: DerN3rd
Tags: wp community, social network, social networking, social media, cp-community, wp community, community
Requires at least: 4.9
Tested up to: 6.8.1
ClassicPress: 2.6.0
Stable tag: 1.1.1
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

**Dies ist das ultimative Plugin für soziale Netzwerke für ClassicPress.** Du kannst auf Deiner ClassicPress-Webseite Dein eigenes soziales Netzwerk erstellen.

Mit Profilen, Aktivitäten (Pinnwand), unbegrenzten Foren, Freunden, E-Mail-Benachrichtigungen und vielem mehr ist es perfekt für Vereine, Schulen, Interessengruppen, die Interaktion mit Kunden, Support-Seiten, Spieleseiten, Dating-Seiten – nur durch Deine Vorstellungskraft begrenzt!

Füge einfach das Plugin hinzu, klicken auf eine Schaltfläche und Du hast ganz einfach Dein eigenes soziales Netzwerk.

**Unglaublich kompatibel**

Unglaublich kompatibel mit Themes und Plugins. Weitere Informationen, einschließlich zusätzlicher Plugins für PS Community, findest Du unter https://github.com/Power-Source/.

**Massiv anpassbar**

Möchtest Du etwas ändern, das Layout, den Text, die Schaltflächenbeschriftungen? Die wahre Stärke von PS Community liegt in Shortcodes mit zahlreichen Optionen, mit denen Du nahezu alles ändern und Deine sozialen Netzwerkseiten so gestalten kannst, wie Du es möchtest!

**Mehrsprachige Webseite?**

Kein Problem! Ändere ganz einfach den gesamten Text, den Deine Benutzer sehen, über die Optionen. Verwendest Du WPML? Funktioniert auch problemlos mit diesem Plugin!

[POWERED BY PSOURCE](https://github.com/Power-Source)

== ChangeLog ==

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

== Upgrade Notice ==

Latest news and information at https://n3rds.work/blog.
