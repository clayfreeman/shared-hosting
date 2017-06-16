# Shared Hosting
Framework for easy management of shared hosting accounts.

## Table of Contents

* [Installation](#installation)
* [Administration](#administration)
  * [Creating Accounts](#creating-accounts)
  * [Creating Sites](#creating-sites)
  * [Deleting Accounts](#deleting-accounts)
  * [Deleting Sites](#deleting-sites)
  * [Disable TLS](#disable-tls)
  * [Enable TLS](#enable-tls)
  * [Flushing Configuration](#flushing-configuration)
  * [Listing Accounts](#listing-accounts)
  * [Restarting Services](#restarting-services)

# Installation

Currently, this package is built and hosted using Launchpad's PPA system.

Before you install this package, make sure that you're running the latest Ubuntu
LTS (`16.04.2` at the time of writing).

To install this package, run the following commands:

```bash
sudo apt-add-repository ppa:certbot/certbot
sudo apt-add-repository ppa:ondrej/php
sudo apt-add-repository ppa:clayfreeman/shared-hosting
sudo apt-get update
sudo apt-get install shared-hosting
```

# Administration

The following set of commands can only be executed by those with `sudo`
privileges. These commands are for managing clients in the shared hosting
environment

## Creating Accounts

Simply type `create-account <username>` to create a shared hosting account.
After the account is created, you should be provided with the account's MySQL
password.

## Creating Sites

To create a site, you must decide on a variety of templates. Currently, the
following templates are available for site creation:

* Joomla 2.x (named `joomla2x`): Uses nginx, PHP 5.6, and HTTP-only (by
  default). This template should be used for Joomla sites below version `3.5` or
  sites that contain code using unsupported language features.
* Joomla 3.5 (named `joomla35`): Uses nginx, PHP 7.1, and HTTP-only (by
  default). This template should be used for modern Joomla sites.

Once a template is selected (noting its simplistic name), you may run
`create-site <account> <template> <primary-domain> [<domains>]...` to create a
site for the given user and template.

After the site is created, you should be provided with the document root for the
site files and a DKIM public key record for all provided domains.

Adding the DKIM public key DNS is highly recommended as all outgoing mail with a
`From` header containing one of the provided domains will be signed with DKIM.
Failure to add the public key record could cost a penalty with SpamAssassin.

SPF and DMARC records are also highly recommended. Use the following guidelines
when adding these records (`@` refers to the base domain name):

| Record | Type  | Name       | Value                                                            | TTL       |
|--------|-------|------------|------------------------------------------------------------------|-----------|
| DMARC  | `TXT` | `_dmarc.@` | `v=DMARC1; p=none; sp=none; fo=1; ruf=mailto:email@address.here` | Automatic |
| SPF    | `TXT` | `@`        | `v=spf1 mx a ~all`                                               | Automatic |

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

## Deleting Accounts

If an account is no longer necessary, or is being moved to another host, you can
delete the account by running `delete-account <account>`. When deleting an
account, it must have no sites associated with it (remove these with the
`delete-site` command).

This command will remove all associated account features (MySQL, Unix account, 
etc.) and attempt to backup and destroy the home directory for the user. The 
backup (if successful) will be stored at `/home/<username>.tar.bz2`.

No databases are removed by this command; they should simply be inaccessible by
non-root MySQL accounts. This allows for more flexibility when taking backups.

## Deleting Sites

To delete a site, simply run `delete-site <domain>` and the site associated with
the provided domain name will be deleted.

This command only removes configuration files (DKIM, nginx, etc.) and will not
harm site files in the document root.

**IMPORTANT**: DKIM secret keys will be removed and will require DNS record
replacement after any subsequent key generation.

## Disable TLS

To disable HTTPS for a site, simply run the command `disable-tls <domain>`. All
certificates and private keys will remain intact and must be removed manually if
desired.

## Enable TLS

To enable HTTPS for a site, simply run the command `enable-tls <domain>` and all
domains associated with the site will be given a Let's Encrypt certificate. This
command requires that an HTTP challenge be completed, thus DNS must be pointing
to the server before TLS can be enabled.

## Flushing Configuration

If for some reason you need to re-write all configuration files that are
dynamically generated with this package, run `flush-config`.

## Listing Accounts

To get a list of shared hosting accounts and their respective MySQL passwords,
simply run `list-accounts`.

## Listing Sites

To get a list of sites and their respective owner, simply run `list-accounts`.

## Restarting Services

To restart all associated services, run `restart-services`.
