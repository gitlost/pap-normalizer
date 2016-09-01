[![Build Status](https://travis-ci.org/gitlost/pap-normalizer.png?branch=master)](https://travis-ci.org/gitlost/pap-normalizer)
# PHP Normalizer #
**Contributors:** [gitlost](https://profiles.wordpress.org/gitlost)  
**Tags:** Unicode, Normalization, Normalize, Normalizer, UTF-8, NFC  
**Requires at least:** 3.9.13  
**Tested up to:** 4.6  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Patch-as-plugin that adds the Normalizer class to WP (and a demo normalizing filter).

## Description ##

Adds the Symfony Normalizer polyfill if the `Intl` extension is not installed.

As a demonstration, adds normalizing and `remove_accents` filters to `sanitize_file_name`, addressing trac tickets

* [#35951](https://core.trac.wordpress.org/ticket/35951)
* [#24661](https://core.trac.wordpress.org/ticket/24661) to a certain extent.
* [#22363](https://core.trac.wordpress.org/ticket/22363)
