<?php
/**
 * Common / Shared functions
 *
 * @package Messaging module
 */


function GetCurrentMessagingUser()
{
	static $user = array();

	if ( ! $user )
	{
		$user_id = User( 'STAFF_ID' ) ? User( 'STAFF_ID' ) : UserStudentID();

		$key = User( 'STAFF_ID' ) ? 'staff_id' : 'student_id';

		$name = User( 'NAME' );

		$user = array( 'user_id' => $user_id, 'key' => $key, 'name' => $name );
	}

	return $user;
}
