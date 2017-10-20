<?php
/**
 * Hook handler base class for MediaWiki hook ResourceLoaderGetConfigVars
 *
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
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit http://bluespice.com
 *
 * @author     Robert Vogel <vogel@hallowelt.com>
 * @package    BlueSpiceFoundation
 * @copyright  Copyright (C) 2017 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */
namespace BlueSpice\Hook;
use BlueSpice\Hook;

abstract class ResourceLoaderGetConfigVars extends Hook {

	/**
	 *
	 * @var array
	 */
	protected $vars = null;

	/**
	 *
	 * @param array $parser
	 * @return boolean
	 */
	public static function callback( &$vars ) {
		$className = static::class;
		$hookHandler = new $className(
			null,
			null,
			$vars
		);
		return $hookHandler->process();
	}

	/**
	 *
	 * @param \IContextSource $context
	 * @param \Config $config
	 * @param array $vars
	 */
	public function __construct( $context, $config, &$vars ) {
		parent::__construct( $context, $config );

		$this->vars = $vars;
	}
}