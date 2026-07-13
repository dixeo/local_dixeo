# Dixeo AI — Core Plugin for Moodle

Foundation plugin that powers the Dixeo AI ecosystem for Moodle 4.5+. This plugin provides the shared services, API communication, and intelligence layer used by the other Dixeo plugins.

**This plugin does not provide a user interface on its own.** It is required by:

- **[Dixeo Editor](../../../local_dixeo_editor)** — AI-powered content editing for pages and labels
- **[Dixeo Module Generator](../../../blocks_dixeo_modulegen)** — Generate new course activities with AI
- **[Dixeo Tutor](../../../blocks_dixeo_tutor)** — AI tutor chatbot for students ("Ask Ed")
- **[Dixeo Course Designer](../../../blocks_dixeo_designer)** — Use AI to design a complete course from source documents with a customisable pedagogicial template

## What it does

- **Module generation** — Create pages, labels, quizzes, glossaries, and slideshows from natural language instructions
- **Content editing** — Make targeted AI edits to existing module content
- **Course generation** — Generate full course structures (sections + modules) from a description, optionally grounded in uploaded documents (PDF, DOCX, TXT, PPTX)
- **Course templates** — Reusable pedagogical templates that constrain how courses are structured (e.g., ABC Learning Design, Bloom's Taxonomy)
- **AI tutoring** — Context-aware conversational assistant that understands the course content
- **File sync** — Automatically indexes course documents so AI can reference them during generation and tutoring
- **Credit management** — Track usage, balance, and transaction history

## Requirements

- Moodle 4.5+
- PHP 8.1+
- A Dixeo API key

## Installation

1. Copy `dixeo` to `/local/dixeo/`
2. Visit Site Administration > Notifications
3. Configure at Site Administration > Plugins > Local plugins > Dixeo AI

## Configuration

| Setting | Description |
|---------|-------------|
| **API URL** | Dixeo API endpoint (default: `https://api.dixeo.com`) |
| **API Key** | Your Dixeo API key (required) |
| **Namespace** | Site identifier for multi-site isolation (default: `default`) |

## Capabilities

| Capability | Description | Default Roles |
|------------|-------------|---------------|
| `local/dixeo:manage` | Manage settings and view admin reports | Manager |
| `local/dixeo:generate` | Generate new modules with AI | Editing Teacher, Manager |
| `local/dixeo:edit` | Edit existing modules with AI | Editing Teacher, Manager |
| `local/dixeo:create` | Create courses using Dixeo Course Designer | Manager, Course Creator |
| `local/dixeo:talktotutor` | Interact with the AI Tutor | Manager, Editing Teacher, Non-Editing Teacher, Student |
| `local/dixeo:viewusage` | View credit usage reports | Manager |

## License

GNU GPL v3 or later
