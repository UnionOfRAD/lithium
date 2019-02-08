<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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
			return ['port' => (integer) substr($host, 1)];
		}
		if ($host[0] === '[') {
			if (($close = strpos($host, ']')) === false) {
				throw new LogicException("Failed to parse host string `{$host}`.");
			}
			if (strlen($host) > $close + 1) {
				if ($host[$close + 1] !== ':') {
					throw new LogicException("Failed to parse host string `{$host}`.");
				}
				return [
					'host' => substr($host, 1, $close - 1),
					'port' => (integer) substr($host, $close + 2)
				];
			}
			return ['host' => substr($host, 1, -1)];
		}
		if (($colon = strpos($host, ':')) !== false) {
			return [
				'host' => substr($host, 0, $colon),
				'port' => (integer) substr($host, $colon + 1)
			];
		}
		return ['host' => $host];
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