
**Deutsch** | [English](README.en.md)

[![Version](https://img.shields.io/badge/Version-1.2.1-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-bis%207.1.0-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.2-03768e?style=flat-square)
[![Lizenz](https://img.shields.io/badge/Lizenz-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

<div align="center">
  <img src="css/images/cpc_logo.png" alt="PS Community" width="112" height="112">

  <h1>PS Community</h1>
  <p><strong>Deine Community. Deine Plattform. Deine Regeln.</strong></p>
  <p>Verwandle Deine ClassicPress-Website in ein vollständiges soziales Netzwerk: mit Profilen, Aktivitätsstream, Freundschaften, Gruppen, Foren, Medien, Dokumenten, Projekten und starken PSOURCE-Integrationen.</p>
  <p><a href="https://psource.eimen.net/psource/ps-community/">Projektseite</a> · <a href="https://psource.eimen.net/wiki/ps-community-dokumentation/">Dokumentation</a> · <a href="https://github.com/Power-Source/ps-community/issues">Fehler melden</a> · <a href="https://psource.eimen.net/">PSOURCE</a></p>
</div>

---

## Mehr als ein Community-Plugin

PS Community bringt die zentralen Bausteine einer modernen Online-Community direkt in ClassicPress. Du entscheidest im Adminbereich, welche Funktionen Deine Website benötigt. Die Module greifen ineinander, bleiben aber einzeln steuerbar.

Das Ergebnis passt sich Deinem Projekt an: Mitgliederbereich, Verein, Kunden-Community, Supportforum, Schule, internes Netzwerk, Projektplattform oder eine ganz eigene Idee.

## Highlights

| Bereich | Was PS Community mitbringt |
| --- | --- |
| **Profile & Mitglieder** | Erweiterbare Benutzerprofile, Avatare, Privatsphäre, Profil-Tabs, Mitgliederverzeichnis und „Zuletzt aktiv“-Informationen |
| **Aktivität** | Persönliche und globale Aktivitätswalls, Beiträge, Kommentare, Link-Vorschauen, Medien und Lounge-Modus |
| **Soziales Netzwerk** | Freundschaftsanfragen, Favoriten, Blockieren, Einladungen und E-Mail-Benachrichtigungen |
| **Foren & Q&A** | Unbegrenzte Foren, Themen, Antworten, Unterforen, akzeptierte Antworten, offene Fragen und Experten-Ranglisten |
| **Gruppen** | Öffentliche und private Gruppen, Mitgliedschaften, Rollen, Einladungen, Gruppen-Aktivität und eigene Inhaltsbereiche |
| **Medien & Galerien** | Bilder, Video, Audio und PDFs, Verzeichnisse, sortierbare Galerien, Lightbox und optionale Slideshow |
| **Dokumente** | Dokumentbibliotheken, Ordner, Profil- und Gruppenkontext sowie PDF-Vorschau mit lokalem PDF.js-Fallback |
| **Projekte** | Persönliche und gruppenbezogene Projekte, Aufgaben, Prioritäten, Deadlines, Kommentare und Aktivitätsprotokoll |
| **Multisite** | Netzwerkweite Modulrichtlinien, Level-basierte Freigaben, Speicher-Scope und zentrale Vorschau der Site-Konfiguration |
| **Anpassung** | Umfangreiche Shortcodes, Attribute, Profil-Tabs, Hooks, Filter, eigene Styles und übersetzbare Oberflächentexte |

## Modular aufgebaut

Unter **PS Community → Einstellungen → Funktionen** aktivierst Du genau die Bausteine, die Du brauchst.

| Core-Modul | Funktion |
| --- | --- |
| `core-profile` | Benutzerprofile und Profilfelder |
| `core-activity` | Aktivitätsstream und Activity Plus |
| `core-avatar` | Benutzeravatare |
| `core-friendships` | Freundschaften und soziale Beziehungen |
| `core-alerts` | E-Mail-Benachrichtigungen |
| `core-forums` | Foren und Q&A |
| `core-groups` | Community-Gruppen |
| `core-members` | Durchsuchbares Mitgliederverzeichnis |
| `core-media` | Medien und Galerien |
| `core-docs` | Dokumente und Ordner |
| `core-projects` | Projekte und Aufgaben |
| `core-invite` | Sichere Einladungen per E-Mail |

Profile, Aktivität, Avatare, Freundschaften, Benachrichtigungen, Foren, Mitgliederverzeichnis und Einladungen sind bei einer frischen Installation standardmäßig aktiv. Medien, Dokumente, Projekte und Gruppen können passend zum Einsatzzweck zugeschaltet werden.

## Schnellstart

### Voraussetzungen

- Eine laufende ClassicPress-Installation
- PHP und Datenbank gemäß den Anforderungen Deiner ClassicPress-Version
- Pretty Permalinks für lesbare Profil-, Gruppen- und Forum-URLs empfohlen

### Installation

1. Lade den Ordner `ps-community` nach `wp-content/plugins/` hoch oder installiere das Plugin über Deine gewohnte Paketverwaltung.
2. Aktiviere **PS Community** im Pluginbereich.
3. Öffne **PS Community → Setup** und ordne die benötigten Community-Seiten zu.
4. Wähle unter **PS Community → Einstellungen → Funktionen** Deine Module aus.
5. Prüfe unter **Einstellungen → Permalinks** einmal die Permalink-Struktur.
6. Ergänze bei Bedarf weitere Seiten mit den unten aufgeführten Shortcodes.

## Die wichtigsten Shortcodes

PS Community registriert eine umfangreiche Shortcode-API. Diese Auswahl deckt die häufigsten Seiten und Workflows ab.

### Profile, Aktivität und Mitglieder

| Shortcode | Verwendung |
| --- | --- |
| `[cpc-activity-page]` | Kombinierte Profil- und Aktivitätsseite |
| `[cpc-activity]` | Aktivität eines Benutzers |
| `[cpc-activity-wall]` | Globale oder konfigurierte Aktivitätswall |
| `[cpc-avatar]` | Avatar eines Benutzers |
| `[cpc-members-directory]` | Durchsuchbares Mitgliederverzeichnis |
| `[cpc-usermeta]` | Profilinformationen ausgeben |
| `[cpc-usermeta-change]` | Profilinformationen bearbeiten |

Beispiel für ein Mitgliederverzeichnis:

```text
[cpc-members-directory per_page="30" show_search="1" show_atoz="1" show_actions="1" order="ASC"]
```

### Gruppen

| Shortcode | Verwendung |
| --- | --- |
| `[cpc-groups]` | Gruppenverzeichnis |
| `[cpc-group-single]` | Einzelne Gruppe |
| `[cpc-group-members]` | Mitglieder einer Gruppe |
| `[cpc-my-groups]` | Gruppen des aktuellen Benutzers |
| `[cpc-group-create]` | Gruppe im Frontend erstellen |
| `[cpc-group-join-button]` | Einer Gruppe beitreten |

### Foren und Q&A

| Shortcode | Verwendung |
| --- | --- |
| `[cpc-forums]` | Forumsliste |
| `[cpc-forum]` | Einzelnes Forum |
| `[cpc-forum-post]` | Einzelnes Thema |
| `[cpc-forum-children]` | Unterforen |
| `[cpc-forum-unanswered]` | Themen ohne akzeptierte Antwort |
| `[cpc-forum-experts]` | Top-Mitglieder nach akzeptierten Antworten |

```text
[cpc-forum-unanswered days="30" max="10"]
[cpc-forum-experts days="30" max="10" show_rank="1"]
```

### Inhalte und Einladungen

| Shortcode | Verwendung |
| --- | --- |
| `[cpc-media-directory]` | Medien- und Galerieverzeichnis |
| `[cpc-gallery-list]` | Galerien auflisten |
| `[cpc-gallery-items]` | Inhalte einer Galerie |
| `[cpc-docs-directory]` | Dokumentverzeichnis |
| `[cpc-projects-directory]` | Projektverzeichnis |
| `[cpc-invite]` | Einladung versenden oder annehmen |
| `[cpc-events]` | Kommende Inhalte aus PS Events einbinden |

Die vollständigen Attribute und spezialisierten Shortcodes findest Du in der [Dokumentation](https://psource.eimen.net/wiki/ps-community-dokumentation/) und direkt in den jeweiligen Admin-Hilfen.

## Medien, Galerien und Dokumente

PS Community behandelt Community-Inhalte nicht als bloße Anhänge. Mitglieder können Medien und Dokumente im Profil- oder Gruppenkontext organisieren und präsentieren.

- Vorschau für Bilder, Video, Audio und PDF
- Moderne, tastaturbedienbare Lightbox mit Fokus-Management und ARIA-Status
- Touch- und Swipe-Navigation auf Mobilgeräten
- Sortierbare Galerie-Inhalte mit direkter Speicherung
- Optionale Bild-Slideshow mit frei wählbarem Intervall
- Native PDF-Anzeige oder lokaler PDF.js-Fallback ohne CDN
- Ordner und Verzeichnisse für strukturierte Inhalte
- Sichtbarkeitsregeln für private und gruppenbezogene Medien

## Projekte, die in der Community leben

Projekte sind mit Profilen, Gruppen und dem Aktivitätsstream verbunden. Aufgaben lassen sich priorisieren, terminieren und kommentieren. Persönliche Projekte, eigene Gruppenprojekte und Beteiligungen bleiben sauber getrennt, während relevante Änderungen im passenden Aktivitätskontext sichtbar werden.

## Integrationen

PS Community arbeitet mit weiteren PSOURCE-Plugins zusammen. Verfügbare Integrationen werden unter **PS Community → Integrationen** verwaltet.

| Integration | Funktion in PS Community |
| --- | --- |
| **PS Events** | Automatische Event-Integration und Frontend-Ausgabe über `[cpc-events]` |
| **PS Chat** | Gruppen-Chat, Profilstatus und kontextbezogene Chat-Funktionen |
| **PS PM-System** | Privates Postfach und Unterhaltungen direkt im Profil, inklusive ungelesener Nachrichten und optionaler Medienanbindung |
| **PS Jobboard** | Jobboard-Tab mit Jobs und Experten im eigenen Profil |
| **PS MarketPress** | Bestellungen des aktuellen Blogs als geschützte Kundenzone im eigenen Profil |
| **PS Security** | Zwei-Faktor-Authentifizierung im Community-Profil, sofern verfügbar |

Die Erweiterungen bleiben eigenständige Plugins. PS Community erkennt ihre Verfügbarkeit und blendet nur passende Funktionen ein.

## Für Multisite gemacht

In ClassicPress Multisite kann PS Community zentral gesteuert werden:

- Module für Subsites netzwerkweit erlauben oder sperren
- Regeln nach Hosting- oder Site-Level vergeben
- Effektive Module jeder Site in einer Vorschau prüfen
- Benutzer-Cloud netzwerkweit oder site-lokal führen
- Speicherlimits zentral setzen
- Profilmedien und Avatare mit eigenem Scope verwalten

Der Mainblog behält dabei die volle Kontrolle über seine Community-Funktionen.

## Sicherheit und Privatsphäre

Community-Funktionen brauchen belastbare Berechtigungen. PS Community prüft deshalb nicht nur, ob ein Benutzer angemeldet ist, sondern berücksichtigt den jeweiligen Kontext.

- Nonces und Berechtigungsprüfungen für schreibende Aktionen
- Signierte, zeitlich begrenzte Einladungs-Token
- Validierte E-Mail-Adressen und sichere Same-Host-Weiterleitungen
- Rollen- und Mitgliedschaftsprüfung für private Gruppeninhalte
- Sichtbarkeitsregeln für Aktivität, Medien, Dokumente und Projekte
- Deterministische Gruppenrollen und konsolidierte Mitgliedschaften
- Lokale Frontend-Abhängigkeiten ohne notwendige CDN-Aufrufe

Bitte melde sicherheitsrelevante Funde nicht öffentlich. Nutze dafür die Kontaktmöglichkeiten auf [PSOURCE](https://psource.eimen.net/).

## Anpassung und Entwicklung

PS Community ist auf Erweiterbarkeit ausgelegt. Themes und Plugins können sich unter anderem in Profil-Tabs, Aktivitätsereignisse, Integrationsbereiche und die Ausgabe der Module einklinken.

Für lokale Anpassungen gilt:

- Überschreibe Darstellung bevorzugt im Theme oder über vorgesehene Hooks und Filter.
- Ändere `cpc_config.php` nur bewusst; die Datei kann bei Plugin-Updates ersetzt werden.
- Verwende die Textdomain `cp-community` für Übersetzungen.
- Aktiviere nur benötigte Module, um den Laufzeitumfang klein zu halten.

### Projektstruktur

```text
activity/       Aktivitätsstream und Activity Plus
alerts/         E-Mail-Benachrichtigungen
avatar/         Avatare
cpc_docs/       Dokumente und Ordner
events/         PS-Events-Integration
forums/         Foren und Q&A
friendships/    Freundschaften und Beziehungen
groups/         Gruppen und Mitgliedschaften
lib/            Integrationen und gemeinsame Abläufe
media/          Medien, Galerien und Lightbox
members/        Mitgliederverzeichnis
multisite/      Netzwerkweite Richtlinien
projects/       Projekte und Aufgaben
usermeta/       Profile, Profilfelder und Privatsphäre
```

## Übersetzungen

PS Community ist vollständig auf die Textdomain `cp-community` ausgerichtet. Im Ordner `languages/` liegen eine aktuelle POT-Basis sowie englische PO- und MO-Dateien. Sichtbare JavaScript-Texte werden über lokalisierte PHP-Objekte bereitgestellt.

## Mitmachen

Fehlerberichte, konkrete Verbesserungsvorschläge und Pull Requests sind willkommen.

- [GitHub-Repository](https://github.com/Power-Source/ps-community)
- [Issues und Feature-Ideen](https://github.com/Power-Source/ps-community/issues)
- [PS Community Projektseite](https://psource.eimen.net/psource/ps-community/)
- [NerdService](https://nerdservice.eimen.net/)

Bitte beschreibe bei Fehlern Deine ClassicPress- und PHP-Version, die aktivierten PS-Community-Module und die Schritte, mit denen sich das Verhalten reproduzieren lässt.

## Lizenz

PS Community ist freie Software unter der **GNU General Public License, Version 2 oder neuer**. Weitere Details stehen in [license.txt](license.txt).

---

<div align="center">
  Entwickelt von <a href="https://psource.eimen.net/">PSOURCE</a> für Communities, die ihre Plattform selbst besitzen wollen.
</div>