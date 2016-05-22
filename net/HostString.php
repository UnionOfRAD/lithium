<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net;

use LogicException;

/**
 * Implements helper methods to parse so called host strings
 * used throughout the framework.
 */
class HostString {

	/**
	 * Parses host string that can either hold just the host name (i.e. `localhost`), a
	 * host/port combination (i.e. `localhost:8080`) or just the port prefixed with a
	 * colon (i.e. `:8080`). Also works with IPv4 and IPv6 addresses.
	 *
	 * Note: IPv6 addresses must be enclosed in square brackets `'[::1]:80'`.
	 *
	 * @param string $host The host string with host, host/port or just port.
	 * @return array An associative array containing parsed `'host'`, `'port'` or both.
	 */
	public static function parse($host) {
		if ($host[0] === ':') {
			return array('port' => (integer) substr($host, 1));
		}
		if ($host[0] === '[') {
			if (($close = strpos($host, ']')) === false) {
				throw new LogicException("Failed to parse host string `{$host}`.");
			}
			if (strlen($host) > $close + 1) {
				if ($host[$close + 1] !== ':') {
					throw new LogicException("Failed to parse host string `{$host}`.");
				}
				return array(
					'host' => substr($host, 1, $close - 1),
					'port' => (integer) substr($host, $close + 2)
				);
			}
			return array('host' => substr($host, 1, -1));
		}
		if (($colon = strpos($host, ':')) !== false) {
			return array(
				'host' => substr($host, 0, $colon),
				'port' => (integer) substr($host, $colon + 1)
			);
		}
		return array('host' => $host);
	}

	/**
	 * Checks if a given string is a path to a Unix socket.
	 *
	 * Based on the assumption that Unix sockets are not available on
	 * Windows, this check is radically simplified.
	 *
	 * @param string $hostOrSocket String containing either host name/port or an absolute
	 *        path to a socket.
	 * @return boolean
	 */
	public static function isSocket($hostOrSocket) {
		return $hostOrSocket[0] === '/';
	}
}

?>