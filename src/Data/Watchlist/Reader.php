<?php

namespace BlueSpice\Data\Watchlist;

use BlueSpice\Services;
use BlueSpice\Data\ReaderParams;
use BlueSpice\Data\DatabaseReader;
use MWNamespace;

class Reader extends DatabaseReader {

	/**
	 *
	 * @param ReaderParams $params
	 * @return PrimaryDataProvider
	 */
	protected function makePrimaryDataProvider( $params ) {
		$contentNamespaces = MWNamespace::getContentNamespaces();
		return new PrimaryDataProvider( $this->db, $contentNamespaces );
	}

	/**
	 *
	 * @return SecondaryDataProvider
	 */
	protected function makeSecondaryDataProvider() {
		return new SecondaryDataProvider(
			Services::getInstance()->getLinkRenderer()
		);
	}

	/**
	 *
	 * @return Schema
	 */
	public function getSchema() {
		return new Schema();
	}
}
