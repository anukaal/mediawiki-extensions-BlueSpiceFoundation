<?php

namespace BlueSpice;

interface INotification {
	/**
	 * @return string
	 */
	public function getKey();

	/**
	 * @return array
	 */
	public function getParams();

	/**
	 * @return array
	 */
	public function getAudience();

	/**
	 * @return \User The user that initiated the notification
	 */
	public function getUser();
}