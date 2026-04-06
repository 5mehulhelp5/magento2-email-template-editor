# Hryvinskyi Email Template Editor for Magento 2

A professional, feature-rich email template editor for Magento 2 that replaces the default email template management with a modern, real-time editing experience.

![Editor Overview](docs/screenshots/editor-overview.png)

## Features

- **Visual Template Editor** — CodeMirror-powered code editor with syntax highlighting, code folding, and bracket matching
- **Live Preview** — Real-time split-pane preview with desktop/mobile viewport toggle
- **Template Sidebar with Filters** — Browse templates by module group, filter by All / Customized / Default
- **Draft Management** — Create, rename, duplicate, delete drafts with auto-save
- **Publish with Schedule** — Publish immediately or schedule for a date range, with changes summary
- **Schedule Timeline** — Visual progress bar with start/end dates, duration, and status (Active / Scheduled / Expired)
- **Version History** — Full history with diff viewer, preview, and one-click restore
- **Variable Chooser** — Searchable slide-in panel with grouped variables and recently used tracking
- **Theme System** — JSON-based themes with import/export
- **Custom CSS & Tailwind** — Write custom CSS or use Tailwind utilities, auto-inlined for email clients
- **Send Test Email** — Send preview to any email address for real client testing
- **Keyboard Shortcuts** — `Ctrl+S` save, `Ctrl+Shift+P` publish, `Ctrl+Enter` refresh, `Ctrl+Shift+H` history, `Ctrl+Shift+E` test email, `Escape` close
- **Confirmation Dialogs** — Styled modals for all destructive actions to prevent accidental data loss
- **Sample Data Providers** — Mock data, last order/customer, specific order search, or custom JSON variables
- **Store View Overrides** — Different templates per store view
- **Admin User Tracking** — Created by, last edited by, timestamps on all overrides

## Requirements

- Magento 2.4.6+
- PHP 8.1+
- `hryvinskyi/magento2-base`
- `hryvinskyi/magento2-configuration-fields`

## Installation

### Composer

```bash
composer require hryvinskyi/magento2-email-template-editor
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

### Manual

1. Copy the module to `app/code/Hryvinskyi/EmailTemplateEditor/`
2. Run:
```bash
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Configuration

Navigate to **Stores > Configuration > Hryvinskyi > Email Template Editor > General Settings** to enable/disable the editor.

## Usage

### Accessing the Editor

Navigate to **Stores > Email Template Editor** in the Magento admin panel.

### Editing a Template

1. Select a template from the left sidebar
2. Edit the HTML content in the code editor
3. Preview changes in real time on the right panel
4. Use the variable chooser to insert template variables
5. Add custom CSS or apply a theme
6. Save as draft or publish when ready

### Managing Overrides

Each template can have multiple overrides:
- **Drafts** - Work-in-progress versions (auto-saved)
- **Published** - Live overrides applied to transactional emails
- **Scheduled** - Overrides with active date ranges

Overrides are store-view specific, allowing different templates per store.

### Publishing Workflow

1. Edit the template content
2. Click **Publish** (or `Ctrl+Shift+P`)
3. Review the changes summary
4. Add a version comment
5. Choose immediate or scheduled publishing
6. Confirm to publish

## License

Proprietary - Copyright (c) 2026 Volodymyr Hryvinskyi. All rights reserved.




