# Prevent Brute Force Login
Contributors: ervannur  
Tags: login, brute force, prevent brute force, prevent brute force login, lockout, lockdown  
Requires at least: 4.9  
Tested up to: 5.3.2  
Stable tag: trunk  
Requires PHP: 5.6.3  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Limit the number of login attempts by ip address.

## Description

Limit the number of login attempts by ip address. By default, user can try brute force username and password from login page with unlimited attempts. This plugin help you by hiding login form after some failed logins in a short period of time from an ip address. The Unlock form will show up to help real users to unlock only their ip address immediately if their ip address range locked.

**Features**
* Limit the number of failed logins in a short period of time before the ip address range locked.
* Send email notification to the admin when a user locked out (customized)
* Show unlock form to help real user to unlock only their ip address.
* Send email notification with unlock key (customized)

## Installation

1. Upload the plugin files to the */wp-content/plugins/prevent-brute-force-login* directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the *Plugins* screen in WordPress
3. Use the *Settings->Prevent Brute Force* to configure the plugin

## Screenshots

1. Login screen after user fail to login.
2. The lock screen will show up with unlock field after the ip range locked.
3. After user submit their registered email address. They will get email with unlock key.
4. General Settings.
5. Admin email settings. Email template to notified admin when some ip address range has been locked.
6. Unlock email settings. Email template to be send to user who request to unlock their ip address when their ip range locked.

## Changelog

**1.1.0**
* Fix fatal error when unlock lockdown.
* Able to lockdown localhost for testing.
* Fix some fatal errors.

**1.0.1**
* Fix code standard.

**1.0.0**
* Initial Release.
