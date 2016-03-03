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
		$user_id = ! empty( $_SESSION['STUDENT_ID'] ) ? $_SESSION['STUDENT_ID'] : User( 'STAFF_ID' );

		$key = ! empty( $_SESSION['STUDENT_ID'] ) ? 'student_id' : 'staff_id';

		if ( $key === 'student_id' )
		{
			$name_RET = DBGet( DBQuery(
				"SELECT FIRST_NAME||' '||LAST_NAME||coalesce(' '||NAME_SUFFIX,' ') AS NAME
				FROM STUDENTS
				WHERE STUDENT_ID='" . $user_id . "'" ) );
		}
		else
		{
			$name_RET = DBGet( DBQuery( "SELECT FIRST_NAME||' '||LAST_NAME AS NAME
				FROM STAFF
				WHERE STAFF_ID='" . $user_id . "'" ) );
		}

		$name = '';

		if ( $name_RET
			&& isset( $name_RET[1]['NAME'] ) )
		{
			// For example:Student Student.
			$name = $name_RET[1]['NAME'];
		}

		$user = array( 'user_id' => $user_id, 'key' => $key, 'name' => $name );
	}

	return $user;
}
