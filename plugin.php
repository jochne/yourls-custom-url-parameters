<?php
/*
Plugin Name: WP Boss - YOURLS Custom URL Parameters
Plugin URI: https://www.wpboss.com.br/
Description: Manages long link exit URL parameters
Version: 1.0
Author: John (João Elton Moreto)
Author URI: https://www.wpboss.com.br/

This plugin is based on "Google Analytics" plugin, by Katz Web Services, Inc. Reference link: http://katz.co/yourls-analytics/

Long description:
This is a simple plugin which lets you control parameters to the final long URL.

Basically, this plugin does the following to the final long URL:
1) Inserts default UTM parameters if each one of the defaults is not set on the original long URL.
2) Inserts parameters passed to the short URL to the long URL (overrides with the parameter from the short URL if the same parameter name is set on the long URL).
3) Inserts a parameter called "utm_referrer" with information from the origin of the click (if you prefer not to pass this information to the final URL, delete below lines from 121 to 139).

This plugin do not have any admin screen. All settings must be made directly to the PHP file.

Installation:
You will have to install this file as a plugin.
Learn how to do in the official YOURLS documentation: https://github.com/YOURLS/YOURLS/wiki/How-to-make-Plugins

********************************************************
********************************************************
DISCLAIMER
********************************************************
This plugin is a contribution to the developer community.
There is no warranty or liability on the author for use in your project.
Use at your own risk.
********************************************************
********************************************************
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

// YOURLS hooks reference:
// http://yourls.org/hooklist.php

yourls_add_filter( 'get_keyword_info', 'wpboss_yourls_url_saida', 999, 4 );
function wpboss_yourls_url_saida( $return, $keyword, $field, $notfound ) {
	
	/* ----------------------------------------- */
	/* ----------------------------------------- */
	// If we're not working with a long URL, this filter is unnecessary.
	/* ----------------------------------------- */
	/* ----------------------------------------- */
	if ($notfound !== false || $field !== 'url' || yourls_is_admin() || defined('YOURLS_INFOS') || defined('YOURLS_PREVIEW')) {
		return $return;
	}

	/* ----------------------------------------- */
	/* ----------------------------------------- */
	// If we are working with a long URL, then let's get to it!
	/* ----------------------------------------- */
	/* ----------------------------------------- */
	$url = $return;

	/* ----------------------------------------- */
	/* ----------------------------------------- */
	// Don't create a non-empty URL from an empty URL (i.e. one that was not in the database) since YOURLS depends on emptiness in yourls-go.php
	/* ----------------------------------------- */
	/* ----------------------------------------- */
	if (empty($url)) {
		return $return;
	}

	$parsed = parse_url($url);
	$parsed['scheme'] = isset($parsed['scheme']) ? $parsed['scheme'] : 'http';
	$parsed['host'] = isset($parsed['host']) ? $parsed['host'] : '';
	$parsed['path'] = isset($parsed['path']) ? $parsed['path'] : '';

	$urlStripped = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
	$urlQueryString = array();

	/* ----------------------------------------- */
	/* ----------------------------------------- */
	// Default UTM values if none is set in the long URL
	/* ----------------------------------------- */
	/* ----------------------------------------- */
	$query = array(
		'utm_source' => 'YOURLS',
		'utm_medium' => 'ShortLink',
		'utm_campaign' => 'URI_' . $keyword,
	);

	$urlParsed = parse_url($url);

	/* ----------------------------------------- */
	/* ----------------------------------------- */
	// Are there query args in the long URL? We'll want an array of those, thanks.
	/* ----------------------------------------- */
	/* ----------------------------------------- */
	if (isset($urlParsed['query'])) {
		parse_str($urlParsed['query'], $urlQueryString);
	}

	/* ----------------------------------------- */
	/* ----------------------------------------- */
	// Merge the arrays with query parameters
	/* ----------------------------------------- */
	/* ----------------------------------------- */
	$query = $urlQueryString + $query;

	/* ----------------------------------------- */
	/* ----------------------------------------- */
	// $_GET query strings trumps all (If there are query args added to the shortlink)
	/* ----------------------------------------- */
	/* ----------------------------------------- */
	if (isset($_GET)) {
		foreach ($_GET as $key => $value) {
			if ( isset($query[$key]) && !empty($value) || !isset($query[$key]) ) {
				$query[$key] = $value;
			}
		}
	}

	/* ----------------------------------------- */
	/* ----------------------------------------- */
	// Get the HTTP Referrer from the click
	/* ----------------------------------------- */
	/* ----------------------------------------- */
	$referrer = isset( $_SERVER['HTTP_REFERER'] ) ? yourls_sanitize_url_safe( $_SERVER['HTTP_REFERER'] ) : '';

	if ($referrer) {
		$referrer = parse_url($referrer);
		$referrer['host'] = isset($referrer['host']) ? $referrer['host'] : '';
		$referrer['path'] = isset($referrer['path']) ? $referrer['path'] : '';
		$referrer['query'] = isset($referrer['query']) ? '?' . $referrer['query'] : '';

		if ($referrer['path'] === '/' && $referrer['query'] == '') {
			$referrer['path'] = '';
		}

		$query['utm_referrer'] = $referrer['host'] . $referrer['path'] . $referrer['query'];
	}

	/* ----------------------------------------- */
	/* ----------------------------------------- */
	// Insert the parameters to final URL
	/* ----------------------------------------- */
	/* ----------------------------------------- */
	$url = yourls_add_query_arg($query, $urlStripped);

	/* ----------------------------------------- */
	/* ----------------------------------------- */
	// Uncomment section below if you want to debug the code
	/* ----------------------------------------- */
	/* ----------------------------------------- */
	/*
	if ('testURI' == $keyword) {
		echo '<pre>referrer: '; var_dump($referrer); echo '</pre>';
		echo '<pre>parsed: '; var_dump($parsed); echo '</pre>';
		echo '<pre>urlStripped: '; var_dump($urlStripped); echo '</pre>';
		echo '<pre>urlQueryString: '; var_dump($urlQueryString); echo '</pre>';
		echo '<pre>query: '; var_dump($query); echo '</pre>';
		echo '<pre>urlParsed: '; var_dump($urlParsed); echo '</pre>';
		echo '<pre>url: '; var_dump($url); echo '</pre>';
		echo '<pre>keyword: '; var_dump($keyword); echo '</pre>';
		echo '<pre>field: '; var_dump($field); echo '</pre>';
		echo '<pre>notfound: '; var_dump($notfound); echo '</pre>';
		echo '<style>pre { padding: 20px; border: solid 1px #CCC; background: #fafafa; border-radius: 4px; white-space: pre-wrap; overflow: auto;}</style>';

		// Now die so the normal flow of event is interrupted
		die();
	}
	*/

	/* ----------------------------------------- */
	/* ----------------------------------------- */
	// Return the URL
	/* ----------------------------------------- */
	/* ----------------------------------------- */
	return $url;
}