---
layout: psource-theme
title: "PS Community Shortcodes"
---

<h2 align="center" style="color:#38c2bb;">📚 PS Community Shortcodes</h2>

<div class="menu">
  <a href="https://github.com/Power-Source/cp-community/discussions" style="color:#38c2bb;">💬 Forum</a>
  <a href="https://github.com/Power-Source/cp-community/releases" style="color:#38c2bb;">📝 Download</a>
</div>

# CP-Community Shortcodes Dokumentation

## Shortcodes: 

### `[cpc_avatar]`

**Beschreibung**: Gibt das HTML für das Avatar des Benutzers zurück. Der Shortcode kann verwendet werden, um das Avatar eines bestimmten Benutzers anzuzeigen und es ermöglicht zusätzliche Optionen wie das Ändern des Avatars oder das Verlinken auf das Profil.

**Seit**: 0.0.1

**Parameter**:

- `user_id` (Typ: int) – Die ID des Benutzers, dessen Avatar angezeigt werden soll. Standardmäßig wird das Avatar des aktuellen Benutzers angezeigt.
- `size` (Typ: int|string) – Die Größe des Avatars in Pixeln oder als Prozentwert (z.B. '100px' oder '50%'). Standardwert ist 256.
- `change_link` (Typ: bool) – Wenn true, wird ein Link zum Ändern des Avatars angezeigt. Standardwert ist false.
- `profile_link` (Typ: bool) – Wenn true, wird der Avatar mit einem Link zum Profil des Benutzers versehen. Standardwert ist false.
- `change_avatar_text` (Typ: string) – Der Text des Links zum Ändern des Avatars. Standardwert ist 'Bild ändern'.
- `change_avatar_title` (Typ: string) – Der Titel des Links zum Ändern des Avatars. Standardwert ist 'Bild hochladen und zuschneiden, um es anzuzeigen'.
- `avatar_style` (Typ: string) – Der Stil des Avatars. Mögliche Werte sind popup oder andere benutzerdefinierte Stile. Standardwert ist 'popup'.
- `popup_width` (Typ: int) – Die Breite des Popups zum Ändern des Avatars. Standardwert ist 750.
- `popup_height` (Typ: int) – Die Höhe des Popups zum Ändern des Avatars. Standardwert ist 450.
- `styles` (Typ: bool) – Ob Stile angewendet werden sollen. Standardwert ist true.
- `check_privacy` (Typ: bool) – Ob die Sichtbarkeit des Profils überprüft werden soll. Standardwert ist false.
- `after` (Typ: string) – Inhalt, der nach dem Avatar eingefügt wird.
- `before` (Typ: string) – Inhalt, der vor dem Avatar eingefügt wird.

### Beispiel:

`echo do_shortcode('[cpc_avatar user_id="123" size="100" change_link="true" profile_link="true"]');`

**Erklärung**:

- `cpc_avatar_init`: Diese Funktion lädt die notwendigen Skripte und Stile für das cpc_avatar Plugin. Sie wird beim Laden des Footers initialisiert.
- `cpc_avatar`: Dieser Shortcode generiert HTML für die Anzeige eines Avatars, basierend auf den angegebenen Attributen. Er kann auch Links zum Profil oder zur Avatar-Änderung hinzufügen.

### Entwickler-Ressourcen:

- **Shortcodes:** [PS Community Shortcodes](shortcodes.md)
- **Hooks:** [PS Community Hooks](hooks.md)
- **Filter:** [PS Community Filter](filter.md)

<div style="display: flex; justify-content: space-around; background-color: #f3f3f3; padding: 10px; border-radius: 5px;">
  <a href="https://cp-psource.github.io/cp-community/" style="text-decoration: none; color: #0366d6; font-weight: bold;">Home</a>
  <a href="https://github.com/Power-Source/cp-community/releases" style="text-decoration: none; color: #0366d6; font-weight: bold;">Downloads</a>
  <a href="https://github.com/Power-Source/cp-community/wiki" style="text-decoration: none; color: #0366d6; font-weight: bold;">Docs</a>
  <a href="https://github.com/Power-Source/cp-community/discussions" style="text-decoration: none; color: #0366d6; font-weight: bold;">Support</a>
  <a href="https://github.com/Power-Source/cp-community/issues" style="text-decoration: none; color: #0366d6; font-weight: bold;">Bug Report</a>
  <a href="https://cp-psource.github.io/cp-community/psource.html" style="text-decoration: none; color: #0366d6; font-weight: bold;">PSOURCE</a>
</div>

<div>
 <a href="https://github.com/Power-Source">Copyright PSOURCE 2024</a>
</div>