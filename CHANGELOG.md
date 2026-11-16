# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Fixed

- Fix error 500 (PHP memory exhausted) on the "Oauth authorization" tab of an application with many authorizations: the list is now paginated
- Speed up the mail collector form: the authorization dropdown now loads and searches authorizations page by page instead of loading them all
- Speed up the "Mail collectors" tab of an application: only the matching mail collectors are read, without one extra query per mail collector
- Remove a database query run by the plugin on every GLPI page and AJAX request

## [1.5.6] - 2026-11-05

### Fixed

- Fix `Serialization of 'Closure' is not allowed` error when authorizing a new mailbox from the mail collector form

## [1.5.5] - 2026-10-06

### Fixed

- Fix mail collector login field not synced with the selected OAuth authorization on form load
- CI: fix Psalm cache directory, drop the stray `glpi-project/tools` composer dependency, declare a unique composer autoloader suffix

## [1.5.4] - 2026-08-04

### Fixed

- Show the specific OAuth authorization error instead of a generic message
- Ignore the connection host/port/security level entered on the authorization diagnostic form and always use the provider's own values
- Escape mail collector host and login when displayed on the application's "Mail collectors" tab

## [1.5.3] - 2026-06-24

### Fixed

- Improve rendering

## [1.5.2] - 2026-06-24

### Fixed

- Improve authorization checks

## [1.5.1] - 2026-05-12

### Fixed

- Hide the client secret field by rendering it as a password input

## [1.5.0] - 2025-09-29

## Fixed
- Fix missing icon and fix escape HTML special chars

## [1.5.0] - 2025-09-29

### Added

- GLPI 11 compatibility

## [1.4.4] - 2025-09-19

## Fix

- Fix UI for the copy to clipboard button
- Remove useless params for proxy configuration
