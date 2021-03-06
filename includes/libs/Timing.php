<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

/**
 * An interface to help developers measure the performance of their applications.
 * This interface closely matches the W3C's User Timing specification.
 * The key differences are:
 *
 * - The reference point for all measurements which do not explicitly specify
 *   a start time is $_SERVER['REQUEST_TIME_FLOAT'], not navigationStart.
 * - Successive calls to mark() and measure() with the same entry name cause
 *   the previous entry to be overwritten. This ensures that there is a 1:1
 *   mapping between names and entries.
 * - Because there is a 1:1 mapping, instead of getEntriesByName(), we have
 *   getEntryByName().
 *
 * The in-line documentation incorporates content from the User Timing Specification
 * http://www.w3.org/TR/user-timing/
 * Copyright © 2013 World Wide Web Consortium, (MIT, ERCIM, Keio, Beihang).
 * http://www.w3.org/Consortium/Legal/2015/doc-license
 *
 * @since 1.27
 */
class Timing {

	/** @var array[] */
	private $entries = array();

	public function __construct() {
		$this->clearMarks();
	}

	/**
	 * Store a timestamp with the associated name (a "mark")
	 *
	 * @param string $markName The name associated with the timestamp.
	 *  If there already exists an entry by that name, it is overwritten.
	 * @return array The mark that has been created.
	 */
	public function mark( $markName ) {
		$this->entries[$markName] = array(
			'name'      => $markName,
			'entryType' => 'mark',
			'startTime' => microtime( true ),
			'duration'  => 0,
		);
		return $this->entries[$markName];
	}

	/**
	 * @param string $markName The name of the mark that should
	 *  be cleared. If not specified, all marks will be cleared.
	 */
	public function clearMarks( $markName = null ) {
		if ( $markName !== null ) {
			unset( $this->entries[$markName] );
		} else {
			$this->entries = array(
				'requestStart' => array(
					'name'      => 'requestStart',
					'entryType' => 'mark',
					'startTime' => isset( $_SERVER['REQUEST_TIME_FLOAT'] )
						? $_SERVER['REQUEST_TIME_FLOAT']
						: $_SERVER['REQUEST_TIME'],
					'duration'  => 0,
				),
			);
		}
	}

	/**
	 * This method stores the duration between two marks along with
	 * the associated name (a "measure").
	 *
	 * If neither the startMark nor the endMark argument is specified,
	 * measure() will store the duration from $_SERVER['REQUEST_TIME_FLOAT'] to
	 * the current time.
	 * If the startMark argument is specified, but the endMark argument is not
	 * specified, measure() will store the duration from the most recent
	 * occurrence of the start mark to the current time.
	 * If both the startMark and endMark arguments are specified, measure()
	 * will store the duration from the most recent occurrence of the start
	 * mark to the most recent occurrence of the end mark.
	 *
	 * @param string $measureName
	 * @param string $startMark
	 * @param string $endMark
	 * @return array The measure that has been created.
	 */
	public function measure( $measureName, $startMark = 'requestStart', $endMark = null ) {
		$start = $this->getEntryByName( $startMark );
		$startTime = $start['startTime'];

		if ( $endMark ) {
			$end = $this->getEntryByName( $endMark );
			$endTime = $end['startTime'];
		} else {
			$endTime = microtime( true );
		}

		$this->entries[$measureName] = array(
			'name'      => $measureName,
			'entryType' => 'measure',
			'startTime' => $startTime,
			'duration'  => $endTime - $startTime,
		);

		return $this->entries[$measureName];
	}

	/**
	 * Sort entries in chronological order with respect to startTime.
	 */
	private function sortEntries() {
		uasort( $this->entries, function ( $a, $b ) {
			return 10000 * ( $a['startTime'] - $b['startTime'] );
		} );
	}

	/**
	 * @return array[] All entries in chronological order.
	 */
	public function getEntries() {
		$this->sortEntries();
		return $this->entries;
	}

	/**
	 * @param string $entryType
	 * @return array[] Entries (in chronological order) that have the same value
	 *  for the entryType attribute as the $entryType parameter.
	 */
	public function getEntriesByType( $entryType ) {
		$this->sortEntries();
		$entries = array();
		foreach ( $this->entries as $entry ) {
			if ( $entry['entryType'] === $entryType ) {
				$entries[] = $entry;
			}
		}
		return $entries;
	}

	/**
	 * @param string $name
	 * @return array|null Entry named $name or null if it does not exist.
	 */
	public function getEntryByName( $name ) {
		return isset( $this->entries[$name] ) ? $this->entries[$name] : null;
	}
}
