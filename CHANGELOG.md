# Changelog

All notable changes to this project will be documented in this file.

## 1.1.0 - 2026-11-18

### Changed

- Requires the 2.0 of the suite core, which runs on [laravel-privacy](https://github.com/jeffersongoncalves/laravel-privacy), the engine shared by the privacy suites. No change to this package's API, config or tables.

## 1.0.0 - 2026-11-17

First release of laravel-us-privacy-minors, minors under the US state privacy laws (on top of laravel-us-privacy).

- Age assurance results (the age, never the document), reliable methods outweighing self-declaration.
- Consent for targeted advertising and sale of known minors at each state's age (13 to 18); Maryland prohibits it under 18; a verified parent or guardian consents under 13 (COPPA).
- Parent or guardian links with verification.
- `us-privacy.adult` middleware, `@usPrivacyMinorAllowed` by the visitor's state, `php artisan us-privacy:minors --state=XX`.

## [Unreleased]
