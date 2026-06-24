<?php
/**
 * Minimal add_query_arg for PHPUnit bootstrap.
 *
 * @param array<string,mixed>|string $args Query args or key.
 * @param string|mixed               $url  Base URL or value when key/value form.
 * @param string|null                $value Optional URL when first arg is key.
 * @return string
 */
function add_query_arg( $args, $url = null, $value = null ) {
	if ( is_array( $args ) ) {
		$query_args = $args;
	} else {
		$query_args = array( $args => $url );
		$url        = $value;
	}

	$parsed = parse_url( (string) $url );
	$query  = array();

	if ( ! empty( $parsed['query'] ) ) {
		parse_str( $parsed['query'], $query );
	}

	$query = array_merge( $query, $query_args );

	$scheme = isset( $parsed['scheme'] ) ? $parsed['scheme'] . '://' : '';
	$host   = isset( $parsed['host'] ) ? $parsed['host'] : '';
	$port   = isset( $parsed['port'] ) ? ':' . $parsed['port'] : '';
	$path   = isset( $parsed['path'] ) ? $parsed['path'] : '';

	return $scheme . $host . $port . $path . '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
}
