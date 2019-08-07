<?php
/**
 * Entity base class for BlueSpice
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit http://bluespice.com
 *
 * @author     Patric Wirth <wirth@hallowelt.com>
 * @package    BlueSpiceFoundation
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */
namespace BlueSpice\Entity;

use Title;
use User;
use Status;

abstract class Content extends \BlueSpice\Entity {
	const NS = -1;

	/**
	 *
	 * @var string
	 */
	private $tsCreatedCache = null;

	/**
	 *
	 * @var string
	 */
	private $tsTouchedCache = null;

	/**
	 * Returns an entity's attributes or the given default, if not set
	 * @param string $attrName
	 * @param mixed|null $default
	 * @return mixed
	 */
	public function get( $attrName, $default = null ) {
		// we currently just use the source titles timestamps
		if ( $attrName == static::ATTR_TIMESTAMP_CREATED ) {
			return $this->getTimestampCreated()
				? $this->getTimestampCreated()
				: $default;
		}
		if ( $attrName == static::ATTR_TIMESTAMP_TOUCHED ) {
			return $this->getTimestampTouched()
				? $this->getTimestampTouched()
				: $default;
		}

		return parent::get( $attrName, $default );
	}

	/**
	 * Returns the User object of the entity's owner
	 * @return User
	 */
	public function getOwner() {
		return User::newFromId( $this->get( static::ATTR_OWNER_ID, 0 ) );
	}

	/**
	 * Gets the source Title object
	 * @return Title
	 */
	public function getTitle() {
		return Title::makeTitle( static::NS, $this->get( static::ATTR_ID, 0 ) );
	}

	/**
	* Get the last touched timestamp
	* @return string|boolean Last-touched timestamp, false if entity was not saved yet
	*/
	public function getTimestampTouched() {
		if ( !$this->exists() ) {
			return false;
		}
		if ( $this->tsTouchedCache ) {
			return $this->tsTouchedCache;
		}
		$this->tsTouchedCache = $this->getTitle()->getTouched();
		return $this->tsTouchedCache;
	}

	/**
	* Get the oldest revision timestamp of this entity
	* @return string|boolean Created timestamp, false if entity was not saved yet
	*/
	public function getTimestampCreated() {
		if ( !$this->exists() ) {
			return false;
		}
		if ( $this->tsCreatedCache ) {
			return $this->tsCreatedCache;
		}
		$this->tsCreatedCache = $this->getTitle()->getEarliestRevTime();
		return $this->tsCreatedCache;
	}

	/**
	 * Saves the current Entity
	 * @return Status
	 */
	public function save( User $oUser = null, $aOptions = [] ) {
		$oTitle = $this->getTitle();
		if ( is_null( $oTitle ) ) {
			return Status::newFatal( 'Related Title error' );
		}

		return parent::save( $oUser, $aOptions );
	}

	/**
	 * Gets the Entity attributes formated for the api
	 * @return array
	 */
	public function getFullData( $data = [] ) {
		return parent::getFullData( array_merge(
			$data,
			[
				static::ATTR_TIMESTAMP_CREATED => $this->getTimestampCreated(),
				static::ATTR_TIMESTAMP_TOUCHED => $this->getTimestampTouched(),
			]
		) );
	}

	/**
	 * Checks, if the current Entity exists in the Wiki
	 * @return boolean
	 */
	public function exists() {
		if ( !parent::exists() ) {
			return false;
		}
		$oTitle = $this->getTitle();
		if ( is_null( $oTitle ) ) {
			return false;
		}
		return $oTitle->exists();
	}

	/**
	 * Invalidated the cache
	 * @return Entity
	 */
	public function invalidateCache() {
		$this->invalidateTitleCache( wfTimestampNow() );
		$this->tsCreatedCache = null;
		$this->tsTouchedCache = null;
		return parent::invalidateCache();
	}

	/**
	 * Almost a copy of Title::invalidateCache method - but we need an immediate
	 * invalidation, not whenever the db feels 'idle'
	 * Updates page_touched for this page; called from LinksUpdate.php
	 *
	 * @param string|null $purgeTime [optional] TS_MW timestamp
	 * @return bool True if the update succeeded
	 */
	protected function invalidateTitleCache( $purgeTime = null ) {
		if ( wfReadOnly() ) {
			return false;
		}

		if ( !$this->getTitle()->exists() ) {
			// avoid gap locking if we know it's not there
			return true;
		}

		$method = __METHOD__;
		$dbw = wfGetDB( DB_MASTER );
		$conds = $this->getTitle()->pageCond();

		$dbTimestamp = $dbw->timestamp( $purgeTime ?: time() );

		$dbw->update(
			'page',
			[ 'page_touched' => $dbTimestamp ],
			$conds + [ 'page_touched < ' . $dbw->addQuotes( $dbTimestamp ) ],
			$method
		);

		return true;
	}
}