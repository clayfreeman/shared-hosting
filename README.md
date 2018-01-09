# Shared Hosting
Framework for easy management of shared hosting accounts.

## Table of Contents

* [Installation](#installation)
* [Usage](#usage)

# Installation

Currently, this package is built and hosted using Launchpad's PPA system.

Before you install this package, make sure that you're running the latest Ubuntu
LTS (`16.04.3` at the time of writing).

To install this package, run the following commands:

```bash
sudo apt-add-repository ppa:certbot/certbot
sudo apt-add-repository ppa:ondrej/php
sudo apt-add-repository ppa:clayfreeman/shared-hosting
sudo apt-get update
sudo apt-get install shared-hosting
```

# Usage

The following set of commands can only be executed by those with `sudo`
privileges. These commands are for managing clients in the shared hosting
environment. More nuanced (and probably more up-to-date) documentation is
provided via the manual pages installed with the package.

## Creating Accounts - `create-account`(8)

Simply type `create-account <username>` to create a shared hosting account.
After the account is created, you should be provided with the account's MySQL
password.

## Creating Sites - `create-site`(8)

To create a site, you must decide on a variety of templates. Currently, the
following templates are available for site creation:

|                       | Canonical Name     | PHP Version |
|-----------------------|--------------------|-------------|
| Drupal 6.x            | `drupal6`          | 5.6         |
| Drupal 7+             | `drupal7`          | 7.1         |
| Generic (PHP 5.6)     | `generic-php5.6`   | 5.6         |
| Generic (PHP 7.0)     | `generic-php7.0`   | 7.0         |
| Generic (PHP 7.1)     | `generic-php7.1`   | 7.1         |
| Generic (PHP 7.2)     | `generic-php7.2`   | 7.2         |
| Joomla 2.x            | `joomla2x`         | 5.6         |
| Joomla 3.5.x to 3.7.x | `joomla35`         | 7.1         |
| Joomla 3.8+           | `joomla38`         | 7.2         |
| Moodle 3.2+           | `moodle`           | 7.1         |
| WordPress (Compat)    | `wordpress-compat` | 5.6         |
| WordPress             | `wordpress`        | 7.1         |

Once a template is selected (noting its canonical name), you may run
`create-site <account> <template> <primary-domain> [<domains>]...` to create a
site for the given user and template.

After the site is created, you should be provided with the document root for the
site files and instructions to fetch a DKIM public key record for all provided
domain names.

Adding the DKIM public key DNS is highly recommended as all outgoing mail with a
`From` header containing one of the provided domains can be configured to be
signed with DKIM. Failure to add the public key record could cost a penalty with
spam prevention.

SPF and DMARC records are also highly recommended. Use the following guidelines
when adding these records (`@` refers to the base domain name):

| Record | Type  | Name       | Value                                                            | TTL       |
|--------|-------|------------|------------------------------------------------------------------|-----------|
| DMARC  | `TXT` | `_dmarc.@` | `v=DMARC1; p=none; sp=none; fo=1; ruf=mailto:email@address.here` | Automatic |
| SPF    | `TXT` | `@`        | `v=spf1 include:server.hostname ~all`                            | Automatic |

Any domain with an SPF record that is also used for client e-mail might require
an extra `include:[domain]` directive so that mail can be delivered from the
client mail provider. If required, this directive should come directly before
`~all` in the SPF record. Notable examples range from `include:emailsrvr.com`
(Rackspace) to `include:_spf.google.com` (G Suite) among others.

As per [RFC 7208](https://tools.ietf.org/html/rfc7208#section-3.1), "SPF records
MUST be published as a DNS TXT (type 16) Resource Record (RR)
[[RFC1035](https://tools.ietf.org/html/rfc1035)] only." This infers that the
`SPF` record type is deprecated and should ideally be replaced by a `TXT` record
with the same name and value.

## Deleting Accounts - `delete-account`(8)

If an account is no longer necessary, or is being moved to another host, you can
delete the account by running `delete-account <account>`. When deleting an
account, it must have no sites associated with it (remove these with the
`delete-site` command).

This command will remove all associated account features (MySQL, Unix account,
etc) and attempt to backup and destroy the home directory for the user. The
backup (if successful) will be stored at `/home/<account>.tar.bz2`.

No databases are removed by this command; they should simply be inaccessible by
non-root MySQL accounts.

## Deleting Sites - `delete-site`(8)

To delete a site, simply run `delete-site <domain>` and the site associated with
the provided domain name will be deleted.

This command only removes configuration files (DKIM, nginx, etc.) and will not
harm site files in the document root.

**IMPORTANT**: DKIM secret keys will be removed and will require DNS record
replacement after any subsequent key generation.

## Disable TLS - `disable-tls`(8)

To disable HTTPS for a site, simply run the command `disable-tls <domain>`. All
certificates and private keys will remain intact and must be removed manually if
desired.

## Enable TLS - `enable-tls`(8)

To enable HTTPS for a site, simply run the command `enable-tls <domain>` and all
domains associated with the site will be given a Let's Encrypt certificate. This
command requires that an HTTP challenge be completed, thus DNS must be pointing
to the server before TLS can be enabled.

## Flushing Configuration - `flush-config`(8)

If for some reason you need to re-write all configuration files that are
dynamically generated with this package, run `flush-config`.

## Listing Accounts - `list-accounts`(8)

To get a list of shared hosting accounts and their respective MySQL passwords,
simply run `list-accounts`.

## Listing Sites - `list-sites`(8)

To get a list of sites and their respective owner, simply run `list-sites`.

## Restarting Services - `restart-services`(8)

Run `restart-services` to restart the following services:

* NGINX
* OpenDKIM
* PHP-FPM
