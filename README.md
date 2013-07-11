# Readr

Readr is a clean & simple, self-hosted RSS reader. 

It is currently under active development, please report any issues you have.

Readr aims to be as simple as possible to set up and use with no external dependencies and no configuration file to customize.
 
Licensed under the GPLv3 license.

![Screenshot](http://readr.pabloprieto.net/screenshot.png)

## Requirements

* PHP 5.3.7+ with sqlite enabled
* mod_rewrite Apache module enabled

## Installation

1. Upload all files to your server. Don't forget the .htaccess file.
2. Make the directory `/data` writeable
3. Create a cronjob to update your feeds automatically via curl or wget and point it to http://yourdomain/update

## Shortcuts

* `space` : next entry
* `shift` + `space` : previous entry
* `r` or `m` : mark as read/unread
* `f` or `s` : toggle favorite
* `v` : open the original source in a new window

## Todo

* ~~Responsive design~~
* Self updating process
* [Fever](http://www.feedafever.com/api) compatible api

## Credits

Readr makes use of the following libraries:

* SimplePie: http://simplepie.org
* password_compat: https://github.com/ircmaxell/password_compat
* jQuery: http://jquery.com
* Backbone.js: http://backbonejs.org
* Moment.js: http://momentjs.com
* Hammer.js: http://eightmedia.github.io/hammer.js/

## Contact

Pablo Prieto, [pabloprieto.net](http://pabloprieto.net/)

## Changelog

* **v0.7**
	- 'Emulate HTTP' option added for compatibility with servers that don't support default REST/HTTP approach
	- Remember expanded/collapsed state in the feeds menu
* **v0.6**
	- Responsive design
	- Direct urls to feeds, tags and entries
	- Added keyboards shortcuts
* **v0.5.1**
	- Security fix
* **v0.5**
	- Initial version


