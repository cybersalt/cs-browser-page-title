# CS Browser Page Title

A Joomla 5 system plugin that sets the browser page title from a custom field value.

## Description

This plugin allows you to use any Joomla custom field to override the browser tab title for articles. When viewing an article, the plugin reads the value from your selected custom field and uses it as the browser page title.

## Features

- Select any article custom field from a dropdown in plugin settings
- Respects Joomla's global "Site Name in Page Titles" setting (before, after, or none)
- Falls back to default title if custom field is empty (configurable)
- Uses `onAfterRender` system event for guaranteed title replacement

## Requirements

- Joomla 5.0 or higher
- PHP 8.1 or higher

## Installation

1. Download the latest release ZIP file
2. In Joomla admin, go to **System > Install > Extensions**
3. Upload and install the ZIP file
4. Go to **System > Plugins**
5. Search for "CS Browser Page Title" and enable it
6. Click on the plugin to configure settings

## Configuration

- **Custom Field**: Select the custom field to use for the browser page title
- **Fallback to Article Title**: If enabled, leaves the default page title unchanged when the custom field is empty

## Why a System Plugin?

Content plugin events (`onContentPrepare`, `onContentAfterDisplay`) fire BEFORE Joomla's article view sets the page title, so any title changes get overwritten. This plugin uses the `onAfterRender` system event which fires after ALL rendering is complete, allowing it to modify the final HTML output and guarantee the title is set correctly.

## Building from Source

Use 7-Zip to create the installation package (never use PowerShell's `Compress-Archive`):

```powershell
cd plg_system_csbrowserpagetitle
& 'C:\Program Files\7-Zip\7z.exe' a -tzip '../plg_system_csbrowserpagetitle_v1.0.0.zip' csbrowserpagetitle.xml services src language
```

## License

GNU General Public License version 2 or later

## Author

Cybersalt - [https://www.cybersalt.org](https://www.cybersalt.org)
