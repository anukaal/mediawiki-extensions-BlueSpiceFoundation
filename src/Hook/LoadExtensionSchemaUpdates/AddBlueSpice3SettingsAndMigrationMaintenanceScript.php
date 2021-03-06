<?php

namespace BlueSpice\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddBlueSpice3SettingsAndMigrationMaintenanceScript extends LoadExtensionSchemaUpdates {
	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		$this->updater->addExtensionTable(
			'bs_settings3',
			$this->getExtensionPath() . '/maintenance/db/bs_settings3.sql'
		);

		$this->updater->addPostDatabaseUpdateMaintenance(
			'BSMigrateSettings'
		);
		return true;
	}

	/**
	 *
	 * @return string
	 */
	protected function getExtensionPath() {
		return dirname( dirname( dirname( __DIR__ ) ) );
	}

}
