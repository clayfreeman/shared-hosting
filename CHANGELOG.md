# CHANGELOG

## 1.5.8

- Switch `Cache-Control` optimization header from 'private' to 'public'.

## 1.5.7

- Fix a bug with the `FPMPool` class.

## 1.5.6

- Fix a bug with the `flush-config` command.

## 1.5.5

- Change composer.json to accept any version of PHP extension (defer to
  packaging control dependency management).

## 1.5.4

- Finish all man page documentation.
- Modify `flush-config`(8) to require `--overwrite` for overwriting PHP-FPM
  common configuration files from the built-in template.
- Modify `get-dkim`(8) to accept `--get-fqdn` to show the Fully-Qualified Domain
  Name (FQDN) of a given DKIM record.

## 1.5.3

- Begin tracking changes with `CHANGELOG.md`.
- Introduced signed Git commits for upstream branch.
