<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace BlueSpice\Data\Entity;

use Status;
use BlueSpice\Entity;

interface IWriter {
	/**
	 * Create or Update given entity
	 * @param $entity Entity
	 * @return Status
	 */
	public function writeEntity( Entity $entity );
}