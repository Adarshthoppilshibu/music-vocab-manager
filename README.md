# Music Vocabulary Manager — WordPress Plugin

A WordPress plugin built to manage and display music terminology 
on a school curriculum website.

## Features
- Custom admin page to add and delete music terms
- Stores data in a MySQL table using WordPress $wpdb
- [music_terms] shortcode to display terms on any page
- Category filtering: [music_terms category="Rhythm"]
- Secure: uses nonces, input sanitization, and prepared statements

## Technologies
WordPress · PHP · MySQL · $wpdb · Shortcode API · WP Admin UI · Git

## How to Install
1. Clone this repo into /wp-content/plugins/
2. Activate via the WordPress Plugins screen
3. Navigate to "Music Vocab" in the admin sidebar
4. Add terms and use [music_terms] shortcode on any page
