# CHANGELOG

## 1.6.5

- Update manpages regarding last patch release.

## 1.6.4

- Upgrade PHP version of template `joomla3.8` to 7.3.
- Remove template `generic-php5.6` and promote it to `generic-php7.1`.
- Remove templates `drupal6`, `joomla2.x`, `webpress`, `wordpress-compat`.

## 1.6.3

- Automatically promote sites using the `generic-php7.0` template to use the
  `generic-php7.1` template instead.

## 1.6.2

- Preemptively replace all occurrences of PHP 7.0 with 7.1 and drop support for
  Ubuntu `xenial`.

## 1.6.1

- Add per-site includes to HTTPS too (I accidentally missed the HTTPS template
  in the last release).

## 1.6.0

- Rename the Joomla! templates to be consistent with the naming scheme of the
  rest of the templates.
- Add per-site includes to allow configuration of edge-case scenarios that
  shouldn't be covered by the template.

## 1.5.11

- Ensure that the `/media` location block for Joomla! sites also caches.

## 1.5.10

- Add `generic-php7.2` template.

## 1.5.9

- Add public caching rules to each template for static resources.

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
