=== WP Opt-in ===
Contributors: Petter
Tags: email, mail, plugin, sidebar, 
Requires at least: 2.0.2
Tested up to: 2.8.4
Stable tag: 1.3.1

Collect e-mail addresses from users with a simple form. Send them an e-mail automagically.

== Description ==

This plugin can be used to send e-mail to users wanting specific information. The front side of the 'WP Opt-in' plugin is a simple form with an e-mail input field and a submit button. By entering an e-mail address into the field and pressing submit, an e-mail is sent automatically to the specified address.

The contents and sender of the automatic e-mail can be specified in options, along with whatever you would like for status messages and other text for the front side.

IP address and time for submit is stored together with the e-mail address in the database. E-mail entries in the database can be selectively deleted. Export to a Bcc friendly format is also possible.

== Installation ==

1. Deactivate the plugin in the WordPress 'Plugins' menu if you are upgrading
2. Download and unzip the plugin
3. Upload the `wp-opt-in` folder to the `/wp-content/plugins/` folder
4. Active the plugin, place the widget in your sidebar and edit its title
5. Configure the plugin in the 'WP Opt-in' menu located under the WordPress 'Options' menu

== Frequently Asked Questions ==

= Can I send e-mail to all users who have asked for information through this plugin? =

Yes. Simply copy all the e-mail addresses from the 'Bcc friendly format' section on the plugin options page. Paste them into the Bcc field in your favorite e-mail client software.

= No e-mails are sent! Why? =

Check if PHP is configured to use a working SMTP server, possibly in a file called php.ini.

= What does Bcc mean? =

Blind carbon copy. A receipient field for e-mail. Using it results in not revealing e-mail addresses between the recipients.

= Can spammers use this to get me in trouble? =

Subject, sender and contents of the e-mail sent from this plugin is controlled by the plugin and you. There should be no reason for sending someone this information unless they asked for it.

= What about security? =

The input e-mail address is checked for suspicious characters before it's used.

== Screenshots ==

Check [the author homepage](http://neppe.no) for en example of usage.

== Changelog ==

= 1.3.1 =
* Try to fix double e-mail to self.
* Remove unnecessary code in opt-in-form

= 1.3 =
* Text in notification e-mail changed to be more general.
* Made it possible to place the php file in either a wp-opt-in folder or the plugins folder. Recommended: folder (to be compatible with future versions).

= 1.2 =
* Better Windows/Linux support. Thanks to Last Programmer for testing.

= 1.1 =
* Improved PHP mail() compatibility

= 1.0 =
* Option to send notification email
* Use form footer field
* Seamlessly perform database upgrade.

= 0.9.2 =
* Correct bugfix in 0.9.1.

= 0.9.1 =
* Tiny bugfix.

= 0.9 =
* Remove old duplicates of submitted new email.

= 0.8 =
* Fix register activation hook. Thanks to Jared.

= 0.7 =
* Create initial opt-in-users database table fix.

= 0.6 =
* Fix widgets on more WordPress versions.

= 0.5 =
* Add control to edit widget title.

= 0.4 =
* Correct typos introduced in 0.3.

= 0.3 =
* Widget support. Thanks to Rune.

= 0.2 =
* Add a comment and improve readme.

= 0.1 =
* Initial version

== Support ==

Visit the [plugin home page](http://neppe.no/wordpress/wp-opt-in).
