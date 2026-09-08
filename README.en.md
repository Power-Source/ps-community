**English** | [Deutsch](README.md)

[![Version](https://img.shields.io/badge/Version-1.2.1-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-up%20to%207.1.0-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.2-03768e?style=flat-square)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

<div align="center">
	<img src="css/images/cpc_logo.png" alt="PS Community" width="112" height="112">

	<h1>PS Community</h1>
	<p><strong>Your community. Your platform. Your rules.</strong></p>
	<p>Turn your ClassicPress website into a complete social network with profiles, activity streams, friendships, groups, forums, media, documents, projects, and powerful PSOURCE integrations.</p>
	<p><a href="https://psource.eimen.net/psource/ps-community/">Project page</a> · <a href="https://psource.eimen.net/wiki/ps-community-dokumentation/">Documentation</a> · <a href="https://github.com/Power-Source/ps-community/issues">Report an issue</a> · <a href="https://psource.eimen.net/">PSOURCE</a></p>
</div>

---

## More Than a Community Plugin

PS Community brings the essential building blocks of a modern online community directly to ClassicPress. You decide in the admin area which features your website needs. The modules work together while remaining individually configurable.

The result adapts to your project: a members area, club, customer community, support forum, school, internal network, project platform, or an idea entirely your own.

## Highlights

| Area | What PS Community Provides |
| --- | --- |
| **Profiles & Members** | Extensible user profiles, avatars, privacy controls, profile tabs, a member directory, and last-active information |
| **Activity** | Personal and global activity walls, posts, comments, link previews, media, and lounge mode |
| **Social Network** | Friend requests, favorites, blocking, invitations, and email notifications |
| **Forums & Q&A** | Unlimited forums, topics, replies, subforums, accepted answers, unanswered questions, and expert rankings |
| **Groups** | Public and private groups, memberships, roles, invitations, group activity, and dedicated content areas |
| **Media & Galleries** | Images, video, audio, and PDFs, directories, sortable galleries, a lightbox, and optional slideshows |
| **Documents** | Document libraries, folders, profile and group contexts, and PDF previews with a local PDF.js fallback |
| **Projects** | Personal and group projects, tasks, priorities, deadlines, comments, and activity logs |
| **Multisite** | Network-wide module policies, level-based permissions, storage scopes, and a centralized site configuration overview |
| **Customization** | Extensive shortcodes, attributes, profile tabs, hooks, filters, custom styles, and translatable interface text |

## Modular by Design

Under **PS Community → Settings → Features**, you can activate exactly the building blocks you need.

| Core Module | Function |
| --- | --- |
| `core-profile` | User profiles and profile fields |
| `core-activity` | Activity stream and Activity Plus |
| `core-avatar` | User avatars |
| `core-friendships` | Friendships and social connections |
| `core-alerts` | Email notifications |
| `core-forums` | Forums and Q&A |
| `core-groups` | Community groups |
| `core-members` | Searchable member directory |
| `core-media` | Media and galleries |
| `core-docs` | Documents and folders |
| `core-projects` | Projects and tasks |
| `core-invite` | Secure email invitations |

Profiles, activity, avatars, friendships, notifications, forums, the member directory, and invitations are enabled by default on a fresh installation. Media, documents, projects, and groups can be enabled to suit your use case.

## Quick Start

### Requirements

- A running ClassicPress installation
- PHP and a database that meet the requirements of your ClassicPress version
- Pretty permalinks are recommended for readable profile, group, and forum URLs

### Installation

1. Upload the `ps-community` directory to `wp-content/plugins/`, or install the plugin through your preferred package management method.
2. Activate **PS Community** in the Plugins area.
3. Open **PS Community → Setup** and assign the required community pages.
4. Select your modules under **PS Community → Settings → Features**.
5. Visit **Settings → Permalinks** and verify the permalink structure once.
6. Add more pages with the shortcodes listed below as needed.

## Essential Shortcodes

PS Community registers an extensive shortcode API. This selection covers the most common pages and workflows.

### Profiles, Activity, and Members

| Shortcode | Usage |
| --- | --- |
| `[cpc-activity-page]` | Combined profile and activity page |
| `[cpc-activity]` | A user's activity |
| `[cpc-activity-wall]` | Global or configured activity wall |
| `[cpc-avatar]` | A user's avatar |
| `[cpc-members-directory]` | Searchable member directory |
| `[cpc-usermeta]` | Display profile information |
| `[cpc-usermeta-change]` | Edit profile information |

Example member directory:

```text
[cpc-members-directory per_page="30" show_search="1" show_atoz="1" show_actions="1" order="ASC"]
```

### Groups

| Shortcode | Usage |
| --- | --- |
| `[cpc-groups]` | Group directory |
| `[cpc-group-single]` | Single group |
| `[cpc-group-members]` | Members of a group |
| `[cpc-my-groups]` | Current user's groups |
| `[cpc-group-create]` | Create a group from the frontend |
| `[cpc-group-join-button]` | Join a group |

### Forums and Q&A

| Shortcode | Usage |
| --- | --- |
| `[cpc-forums]` | Forum list |
| `[cpc-forum]` | Single forum |
| `[cpc-forum-post]` | Single topic |
| `[cpc-forum-children]` | Subforums |
| `[cpc-forum-unanswered]` | Topics without an accepted answer |
| `[cpc-forum-experts]` | Top members by accepted answers |

```text
[cpc-forum-unanswered days="30" max="10"]
[cpc-forum-experts days="30" max="10" show_rank="1"]
```

### Content and Invitations

| Shortcode | Usage |
| --- | --- |
| `[cpc-media-directory]` | Media and gallery directory |
| `[cpc-gallery-list]` | List galleries |
| `[cpc-gallery-items]` | Contents of a gallery |
| `[cpc-docs-directory]` | Document directory |
| `[cpc-projects-directory]` | Project directory |
| `[cpc-invite]` | Send or accept an invitation |
| `[cpc-events]` | Embed upcoming content from PS Events |

You can find all attributes and specialized shortcodes in the [documentation](https://psource.eimen.net/wiki/ps-community-dokumentation/) and in the relevant admin help sections.

## Media, Galleries, and Documents

PS Community treats community content as more than simple attachments. Members can organize and present media and documents within profile or group contexts.

- Previews for images, video, audio, and PDF files
- Modern keyboard-accessible lightbox with focus management and ARIA status
- Touch and swipe navigation on mobile devices
- Sortable gallery content with immediate saving
- Optional image slideshows with configurable intervals
- Native PDF display or a local PDF.js fallback without a CDN
- Folders and directories for structured content
- Visibility rules for private and group-related media

## Projects That Live in Your Community

Projects are connected to profiles, groups, and the activity stream. Tasks can be prioritized, scheduled, and commented on. Personal projects, projects in groups you created, and projects you participate in remain clearly separated, while relevant changes appear in the appropriate activity context.

## Integrations

PS Community works with other PSOURCE plugins. Available integrations are managed under **PS Community → Integrations**.

| Integration | Function in PS Community |
| --- | --- |
| **PS Events** | Automatic event integration and frontend output through `[cpc-events]` |
| **PS Chat** | Group chat, profile status, and context-aware chat features |
| **PS PM-System** | Private inbox and conversations directly in the profile, including unread messages and optional media integration |
| **PS Jobboard** | Jobboard tab with jobs and experts in the user's own profile |
| **PS MarketPress** | Orders from the current site as a protected customer area in the user's own profile |
| **PS Security** | Two-factor authentication in the community profile, when available |

The extensions remain independent plugins. PS Community detects their availability and displays only the appropriate features.

## Built for Multisite

PS Community can be managed centrally in ClassicPress Multisite:

- Allow or block modules for subsites across the network
- Define rules by hosting or site level
- Review the effective modules for each site in a centralized overview
- Keep the user cloud network-wide or site-local
- Set storage limits centrally
- Manage profile media and avatars with their own scopes

The main site retains full control over its community features.

## Security and Privacy

Community features need reliable permissions. PS Community therefore checks more than whether a user is logged in; it also considers the relevant context.

- Nonces and capability checks for write operations
- Signed, time-limited invitation tokens
- Validated email addresses and secure same-host redirects
- Role and membership checks for private group content
- Visibility rules for activity, media, documents, and projects
- Deterministic group roles and consolidated memberships
- Local frontend dependencies without required CDN requests

Please do not disclose security-related findings publicly. Use the contact options on [PSOURCE](https://psource.eimen.net/) instead.

## Customization and Development

PS Community is designed for extensibility. Themes and plugins can hook into profile tabs, activity events, integration areas, module output, and more.

For local customizations:

- Prefer overriding presentation in your theme or through the available hooks and filters.
- Only modify `cpc_config.php` deliberately; the file may be replaced during plugin updates.
- Use the `cp-community` text domain for translations.
- Enable only the modules you need to keep the runtime footprint small.

### Project Structure

```text
activity/       Activity stream and Activity Plus
alerts/         Email notifications
avatar/         Avatars
cpc_docs/       Documents and folders
events/         PS Events integration
forums/         Forums and Q&A
friendships/    Friendships and relationships
groups/         Groups and memberships
lib/            Integrations and shared workflows
media/          Media, galleries, and lightbox
members/        Member directory
multisite/      Network-wide policies
projects/       Projects and tasks
usermeta/       Profiles, profile fields, and privacy
```

## Translations

PS Community consistently uses the `cp-community` text domain. The `languages/` directory contains an up-to-date POT template as well as English PO and MO files. Visible JavaScript strings are provided through localized PHP objects.

## Contributing

Bug reports, concrete suggestions for improvement, and pull requests are welcome.

- [GitHub repository](https://github.com/Power-Source/ps-community)
- [Issues and feature ideas](https://github.com/Power-Source/ps-community/issues)
- [PS Community project page](https://psource.eimen.net/psource/ps-community/)
- [NerdService](https://nerdservice.eimen.net/)

When reporting an issue, please include your ClassicPress and PHP versions, the enabled PS Community modules, and the steps required to reproduce the behavior.

## License

PS Community is free software licensed under the **GNU General Public License, version 2 or later**. See [license.txt](license.txt) for details.

---

<div align="center">
	Developed by <a href="https://psource.eimen.net/">PSOURCE</a> for communities that want to own their platform.
</div>
