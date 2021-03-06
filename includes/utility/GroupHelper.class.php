<?php

class BsGroupHelper {

	protected static $sLockModeGroup = 'lockmode';

	/**
	 * Public getter for lockmode group. This is needed by some extensions.
	 * @return string
	 */
	public static function getLockModeGroup() {
		return self::$sLockModeGroup;
	}

	protected static $aGroups = [];

	/**
	 *
	 * @param array $aConf
	 * @return array
	 */
	public static function getAvailableGroups( $aConf = [] ) {
		$aBlacklist = [];

		if ( isset( $aConf['blacklist'] ) ) {
			if ( !is_array( $aConf['blacklist'] ) ) {
				$aConf['blacklist'] = (array)$aConf['blacklist'];
			}
			$aBlacklist = $aConf['blacklist'];
		}

		$aBlacklist[] = self::$sLockModeGroup;

		$bDoReload = false;
		if ( isset( $aConf['reload'] ) ) {
			$bDoReload = $aConf['reload'];
		}
		if ( empty( self::$aGroups ) ) {
			$bDoReload = true;
		}

		if ( $bDoReload ) {
			self::$aGroups = array_merge(
				User::getImplicitGroups(),
				User::getAllGroups()
			);
			self::$aGroups = array_diff( self::$aGroups, $aBlacklist );
			natsort( self::$aGroups );
		}

		return self::$aGroups;
	}

	/**
	 *
	 * @param string $sRight
	 * @param array $aConf
	 * @return array
	 */
	public static function getGroupsByRight( $sRight, $aConf = [] ) {
		global $wgGroupPermissions;
		$aBlacklist = [];

		if ( isset( $aConf['blacklist'] ) ) {
			if ( !is_array( $aConf['blacklist'] ) ) {
				$aConf['blacklist'] = (array)$aConf['blacklist'];
			}
			$aBlacklist = $aConf['blacklist'];
		}

		$aGroups = [];
		foreach ( $wgGroupPermissions as $sGroup => $aPermissions ) {
			if ( in_array( $sGroup, $aBlacklist ) ) {
				continue;
			}
			foreach ( $aPermissions as $sPermissionName => $bBool ) {
				if ( $sPermissionName == $sRight ) {
					$aGroups[] = $sGroup;
				}
			}
		}

		return $aGroups;
	}

	/**
	 * DEPRECATED!
	 * @deprecated since version 3.0.2 - Do not assign groups temporarily, as
	 * this is broken
	 * @param User $oUser
	 * @param String $sGroupName
	 * @return boolean
	 */
	public static function addTempGroupToUser( $oUser, $sGroupName ) {
		wfDebugLog( 'bluespice-deprecations', __METHOD__, 'private' );
		if ( in_array( $sGroupName, $oUser->getEffectiveGroups() ) ) {
			return true;
		}
		$oUser->addGroup( $sGroupName, wfTimestamp( TS_MW, time() + 60 ) );

		return true;
	}

	/**
	 * DEPRECATED!
	 * @deprecated since version 3.0.2 - Do not assign permissions temporarily,
	 * as this is broken
	 * @param String $sGroupName
	 * @param Array $aPermissions
	 * @param Array $aNamespaces
	 */
	public static function addPermissionsToGroup( $sGroupName, $aPermissions, $aNamespaces = [] ) {
		wfDebugLog( 'bluespice-deprecations', __METHOD__, 'private' );
		global $wgGroupPermissions;

		$aNamespaces = array_diff(
			$aNamespaces,
			[ NS_MEDIA, NS_SPECIAL ]
		);

		foreach ( $aPermissions as $sPermission ) {
			$wgGroupPermissions[$sGroupName][$sPermission] = true;

			// Check if Lockdown is in use
			if ( empty( $aNamespaces ) || !isset( $GLOBALS['wgNamespacePermissionLockdown'] ) ) {
				continue;
			}
			foreach ( $aNamespaces as $iNs ) {
				if ( isset( $GLOBALS['wgNamespacePermissionLockdown'][$iNs][$sPermission] ) ) {
					if ( in_array(
						$sGroupName,
						$GLOBALS['wgNamespacePermissionLockdown'][$iNs][$sPermission]
					) ) {
						continue;
					}
				}
				$GLOBALS['wgNamespacePermissionLockdown'][$iNs][$sPermission][]
					= $sGroupName;
			}
		}
	}

	/**
	 * Returns an array of User being in one or all groups given
	 * @param mixed $aGroups
	 * @return array Array of User objects
	 */
	public static function getUserInGroups( $aGroups ) {
		$dbr = wfGetDB( DB_REPLICA );
		if ( !is_array( $aGroups ) ) {
			$aGroups = [ $aGroups ];
		}
		$aUser = [];
		$res = $dbr->select(
			'user_groups',
			[ 'ug_user' ],
			[ 'ug_group' => $aGroups ],
			__METHOD__,
			[ 'DISTINCT' ]
			);
		if ( !$res ) {
			return $aUser;
		}
		foreach ( $res as $row ) {
			$aUser [] = User::newFromId( $row->ug_user );
		}
		return $aUser;
	}

}
