# Changelog

All notable changes to CS Browser Page Title will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-01-03

### 🚀 New Features
- **Initial Release**: Joomla 5 system plugin to set browser page title from custom field value
- **Custom Field Selection**: SQL-based dropdown to select any article custom field
- **Site Name Handling**: Respects Joomla's global "Site Name in Page Titles" setting (before, after, or none)
- **Fallback Option**: Configurable option to leave default title unchanged if custom field is empty
- **System Plugin Architecture**: Uses `onAfterRender` event to modify final HTML output, ensuring title changes are not overwritten by Joomla's article view
- **DatabaseAwareTrait**: Direct database queries to fetch custom field values
