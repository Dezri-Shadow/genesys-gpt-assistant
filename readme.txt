=== Genesys GPT Assistant ===
Contributors: Dezri
Tags: gpt, ai, npc generator, genesys, roleplay
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that generates Genesys RPG NPCs using the OpenAI GPT API. Inputs include setting, NPC level, player tier, world background, and encounter context.

== Description ==

This plugin provides a frontend form for Game Masters to generate dynamic NPCs for Fantasy Flight Games' Genesys RPG system. It uses a custom GPT assistant (ChatGPT API) to respond with structured NPC data, quirks, and roleplay prompts.

Features:
* Customizable form inputs
* GPT-4 integration via REST API
* JSON response parsing with collapsible output
* Bootstrap styling and modals
* Admin settings page for API key

== Installation ==

1. Upload the `genesys-gpt-assistant` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the WordPress admin panel.
3. Go to **Settings → Genesys GPT** and paste your OpenAI API key.
4. Insert the shortcode `[genesys_gpt_form]` on any page or post.

== Changelog ==

= 1.0 =
* Initial release with full form input, prompt processing, GPT-4 support, and frontend rendering.

== Upgrade Notice ==

= 1.0 =
Initial stable release.
