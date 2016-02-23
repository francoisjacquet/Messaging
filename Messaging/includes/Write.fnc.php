<?php
/**
 * Write functions
 * Send Message, Get Recipients...
 *
 * @package Messaging module
 */

/**
 * Send Message
 *
 * @example $sent = SendMessage( array( 'recipients_key' => $_REQUEST['recipients_key'], 'recipients_ids' => $_REQUEST['recipients_ids'], 'message' => $_REQUEST['message'], 'subject' => $_REQUEST['subject'] ) );
 *
 * @see Write program
 *
 * @param array $msg Associative array (keys = reply_to_id|recipients_key|recipients_ids|message|subject).
 */
function SendMessage( $msg )
{
	global $error;

	// Check required parameters.
	if ( ( ! isset( $msg['reply_to_id'] )
			&& ( ! isset( $msg['recipients_key'] )
				|| ! isset( $msg['recipients_ids'] ) ) )
		|| (string) $msg['recipients_ids'] === ''
		|| ! isset( $msg['message'] )
		|| ! isset( $msg['subject'] ) )
	{
		$error[] = dgettext( 'Messaging', 'The message could not be sent. Form elements are missing.' );

		return false;
	}

	// Check required fields.
	if ( (string) $msg['message'] === ''
		|| (string) $msg['subject'] === '' )
	{
		$error[] = _( 'Please fill in the required fields' );

		return false;
	}

	if ( (string) (int) $msg['reply_to_id'] === $msg['reply_to_id'] )
	{
		$recipients = _getMessageRecipients( 'reply', (string) $msg['reply_to_id'] );
	}
	else
	{
		$recipients = _getMessageRecipients( (string) $msg['recipients_key'], (string) $msg['recipients_ids'] );
	}

	// Check & Get Recipients.
	if ( ! $recipients )
	{
		$error[] = dgettext( 'Messaging', 'You are not allowed to send a message to those recipients.' );

		return false;
	}

	// Serialize Data.
	$data = serialize( array( 'message' => (string) $msg['message'] ) );

	// Save Message.
	DBQuery( "INSERT INTO MESSAGES VALUES(
		(SELECT " . db_seq_nextval( 'MESSAGES_SEQ' ) . "),
		'" . UserSyear() . "',
		'" . UserSchool() . "',
		'" . $recipients . "',
		'" . $msg['subject'] . "',
		CURRENT_TIMESTAMP,
		'" . $data . "'
	)" );

	if ( (string) (int) $msg['reply_to_id'] === $msg['reply_to_id'] )
	{
		$recipient = _getMessageFromIDColumn( $msg['reply_to_id'] );

		if ( ! $recipient )
		{
			// Original sender not found!
			// Unprobable here: already checked in _getMessageRecipients().
			return false;
		}

		return _saveMessageRecipients( $msg_id, $recipient['COLUMN'], $recipient['ID'] );
	}

	// Save Recipients in cross tables.
	if ( $msg['recipients_key'] === 'student_id'
		|| $msg['recipients_key'] === 'staff_id' )
	{

		return _saveMessageRecipients( $msg_id, $msg['recipients_key'], $msg['recipients_ids'] );
	}
	else
	{
		// Wrong recipients key!
		// Unprobable here: already checked in _getMessageRecipients().
		return false;
	}
}


function _saveMessageRecipients( $msg_id, $column, $recipients_ids )
{
	if ( $column !== 'student_id'
		|| $column !== 'staff_id'
		|| ! $recipients_ids
		|| ! $msg_id )
	{
		return false;
	}

	// Get Recipients IDs array.
	if ( mb_strpos( $recipients_ids, ',' ) !== false )
	{
		$recipients_ids = explode( ',', $recipients_ids );
	}
	else
	{
		// Single Recipient.
		$recipients_ids = array( $recipients_ids );
	}

	if ( $column === 'student_id' )
	{
		foreach ( (array) $recipients_ids as $recipient_id )
		{
			DBQuery( "INSERT INTO STUDENTXMESSAGE VALUES(
				'" . $recipient_id . "',
				'" . $msg_id . "'
			)" );
		}
	}
	else
	{
		foreach ( (array) $recipients_ids as $recipient_id )
		{
			DBQuery( "INSERT INTO USERXMESSAGE VALUES(
				'" . $recipient_id . "',
				'" . $msg_id . "'
			)" );
		}
	}

	return true;
}


function _getMessageRecipients( $recipients_key, $recipients_ids )
{
	if ( $recipients_key === 'reply' )
	{
		$reply_to_id = $recipients_ids;

		if ( ! $reply_to_id )
		{
			return '';
		}

		// Reply: just check the reply to ID is allowed (the message has been sent to him first).
		if ( ! empty( $_SESSION['STUDENT_ID'] ) )
		{
			// Student:
			$allowed_reply_RET = DBQuery( DBGet( "SELECT 1 FROM STUDENTXMESSAGE
				WHERE MESSAGE_ID='" . $reply_to_id . "'
				AND STUDENT_ID='" . $_SESSION['STUDENT_ID'] . "'
				AND STATUS<>'sent'" ) );
		}
		else
		{
			// Staff:
			$allowed_reply_RET = DBQuery( DBGet( "SELECT 1 FROM USERXMESSAGE
				WHERE MESSAGE_ID='" . $reply_to_id . "'
				AND USER_ID='" . User( 'STAFF' ) . "'
				AND STATUS<>'sent'" ) );
		}

		if ( ! $allowed_reply_RET )
		{
			return '';
		}

		// Get Recipient == Original message From.
		return _getMessageFromName( $reply_to_id );
	}

	$recipients_keys = _getAllowedRecipientKeys( User( 'PROFILE' ) );

	// Check parameters.
	if ( ! isset( $recipients_key )
		|| ! in_array( $recipients_key, $recipients_keys )
		|| ! isset( $recipients_ids )
		|| (string) $recipients_ids === '' )
	{
		return '';
	}

	$allowed_recipient = false;

	// Check Recipients.
	if ( $recipients_ids === '0' )
	{
		$recipients_all_labels = array(
			'student_id' => _( 'Students' ),
			'staff_id' => _( 'Staff' ),
			// 'course_period_id' => '',
			// 'grade_id' => '',
			// 'profile_id' => '',
		);

		// All.
		return sprintf( 'All %s', $recipients_all_labels[ $recipients_key ] );
	}
	elseif ( (string) (int) $recipients_ids === $recipients_ids
		&& $recipients_ids > 0 )
	{
		// One recipient.
		$allowed_recipient = _checkMessageRecipient( $recipient_key, $recipient_ids );
	}
	elseif ( mb_strpos( $recipients_ids, ',' ) !== false )
	{
		// Recipients.
		$recipients_ids_array =  explode( ',', $recipients_ids );

		foreach ( (array) $recipients_ids_array as $recipient_id )
		{
			$allowed_recipient = _checkMessageRecipient( $recipient_key, $recipient_id );
		}
	}

	if ( ! $allowed_recipient )
	{
		return '';
	}

	if ( $recipients_key === 'student_id' )
	{
		$names_RET = DBGet( DBQuery(
			"SELECT array_agg(FIRST_NAME||' '||LAST_NAME||coalesce(' '||NAME_SUFFIX,'')) AS NAMES
			FROM STUDENTS
			WHERE STUDENT_ID IN(" . $recipient_ids . ")" ) );
	}
	elseif ( $recipients_key === 'staff_id' )
	{
		$names_RET = DBGet( DBQuery( "SELECT array_agg(FIRST_NAME||' '||LAST_NAME) AS NAMES
			FROM STAFF
			WHERE STAFF_ID IN(" . $recipient_ids . ")" ) );
	}

	if ( $names_RET
		&& isset( $names_RET[1]['NAMES'] ) )
	{
		// For example: {"Student Student","Andrea Mazariegos Jr"}.
		// Return Student Student, Andrea Mazariegos Jr.
		return str_replace('","', ', ', mb_substr( $names_RET[1]['NAMES'], 2, -2 ) );
	}

	return '';
}


function _checkMessageRecipient( $recipient_key, $recipient_id )
{
	$recipients_keys = _getAllowedRecipientKeys( User( 'PROFILE' ) );

	// Check parameters.
	if ( ! isset( $recipients_key )
		|| ! in_array( $recipients_key, $recipients_keys )
		|| ! isset( $recipients_id )
		|| (string) $recipients_id === '' )
	{
		return false;
	}

	// Check Recipient ID is allowed.
	// Check not self.
	if ( $recipients_key === 'staff_id'
		&& $recipient_id === User( 'STAFF_ID' ) )
	{
		return false;
	}

	switch ( $recipient_key )
	{
		case 'student_id':

			// May exit on HackingAttempt() if you do not behave!
			SetUserStudentID( $recipient_id );

		break;

		case 'staff_id':

			if ( User( 'PROFILE' ) === 'admin' )
			{
				// May exit on HackingAttempt() if you do not behave!
				SetUserStaffID( $recipient_id );
			}
			else
			{
				$allowed = array();

				if ( User( 'PROFILE' ) === 'student' )
				{
					// Student: allow its Teachers + Admin staff.
					$allowed += _getStudentAllowedTeachersRecipients( $_SESSION['STUDENT_ID'] );
					$allowed += _getStudentAllowedAdminRecipients();
				}
				elseif ( User( 'PROFILE' ) === 'parent' )
				{
					// Parent: allow students' Teachers + Admin staff.
					$allowed += _getParentAllowedTeachersRecipients();
					$allowed += _getParentAllowedAdminRecipients();
				}
				elseif ( User( 'PROFILE' ) === 'teacher' )
				{
					// Teachers: Parents of related students + Admin staff + other Teachers.
					$allowed += _getTeacherAllowedParentsRecipients(); // see SetUserStaffID()!
					$allowed += _getTeacherAllowedAdminRecipients();
				}

				if ( ! in_array( $recipient_id, $allowed ) )
				{
					return false;
				}
			}

		break;

		case 'course_period_id':

		break;

		case 'profile_id':

		break;

		case 'grade_id':

		break;
	}

	return true;
}


function _getAllowedRecipientKeys( $profile )
{
	if ( $profile === 'student' )
	{
		return array( 'staff_id' );
	}
	elseif ( $profile === 'parent' )
	{
		return array( 'staff_id' );
	}
	elseif ( $profile === 'teacher' )
	{
		return array( 'student_id', 'staff_id' );
	}
	elseif ( $profile === 'admin' )
	{
		return array( 'student_id', 'staff_id' );
	}

	//return array( 'student_id', 'staff_id', 'course_period_id', 'grade_id', 'profile_id' );
	
	return array();
}


function _getAllowedAdminRecipients()
{
	static $allowed_ids = array();

	if ( ! $allowed_ids )
	{
		$allowed_ids_RET = DBGet( DBQuery( "SELECT array_agg(STAFF_ID) as ALLOWED_IDS
			FROM STAFF
			WHERE PROFILE='admin'
			AND SYEAR='" . UserSyear() . "'
			AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)" ) );

		if ( $allowed_ids_RET
			&& $allowed_ids_RET[1]['ALLOWED_IDS'] )
		{
			// For example: {70,10,1}.
			$allowed_ids = explode( ',', mb_substr( $allowed_ids_RET[1]['ALLOWED_IDS'], 1, -1 ) );
		}
	}

	return $allowed_ids;
}


function _getStudentAllowedAdminRecipients()
{
	return _getAllowedAdminRecipients();
}


function _getParentAllowedAdminRecipients()
{
	return _getAllowedAdminRecipients();
}


function _getTeacherAllowedAdminRecipients()
{
	return _getAllowedAdminRecipients();
}


function _getStudentAllowedTeachersRecipients( $student_id )
{
	static $allowed_ids = array();

	if ( ! $student_id )
	{
		return array();
	}

	if ( ! isset( $allowed_ids[ $student_id ] ) )
	{
		$allowed_ids_RET = DBGet( DBQuery( "SELECT array_agg(DISTINCT(cp.TEACHER_ID)) as ALLOWED_IDS
			FROM SCHEDULE sch, COURSE_PERIODS cp
			WHERE sch.SYEAR='" . UserSyear() . "'
			AND sch.SCHOOL_ID='" . UserSchool() . "'
			AND sch.STUDENT_ID='" . $student_id . "'
			AND sch.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
			AND cp.SYEAR=sch.SYEAR
			AND cp.SCHOOL_ID=sch.SCHOOL_ID" ) );

		if ( $allowed_ids_RET
			&& $allowed_ids_RET[1]['ALLOWED_IDS'] )
		{
			// For example: {70,10,1}.
			$allowed_ids[ $student_id ] = explode( ',', mb_substr( $allowed_ids_RET[1]['ALLOWED_IDS'], 1, -1 ) );
		}
	}

	return $allowed_ids[ $student_id ];
}


function _getParentAllowedTeachersRecipients()
{
	static $allowed_ids = array();

	if ( ! User( 'STAFF_ID' ) )
	{
		return $allowed_ids;
	}

	if ( ! $allowed_ids )
	{
		// Get Parent Students.
		$students_RET = DBGet( DBQuery( "SELECT sju.STUDENT_ID
			FROM STUDENTS s,STUDENTS_JOIN_USERS sju,STUDENT_ENROLLMENT se 
			WHERE s.STUDENT_ID=sju.STUDENT_ID 
			AND sju.STAFF_ID='" . User( 'STAFF_ID' ) . "' 
			AND se.SYEAR='" . UserSyear() . "' 
			AND se.STUDENT_ID=sju.STUDENT_ID 
			AND ('" . DBDate() . "'>=se.START_DATE
				AND ('" . DBDate() . "'<=se.END_DATE
					OR se.END_DATE IS NULL ) )" ) );

		foreach ( (array) $students_RET as $student )
		{
			$allowed_ids += _getStudentAllowedTeachersRecipients( $student['STUDENT_ID'] );
		}
	}

	return $allowed_ids;
}


function _getTeacherAllowedParentsRecipients()
{
	static $allowed_ids = array();

	if ( ! User( 'STAFF_ID' ) )
	{
		return $allowed_ids;
	}

	if ( ! $allowed_ids )
	{
		$allowed_ids_RET = DBGet( DBQuery( "SELECT array_agg(sju.STAFF_ID) as ALLOWED_IDS
			FROM STUDENTS_JOIN_USERS sju,STUDENT_ENROLLMENT sem,SCHEDULE sch 
			WHERE sem.STUDENT_ID=sju.STUDENT_ID
			AND sem.SYEAR='" . UserSyear() . "'
			AND sem.SCHOOL_ID='" . UserSchool() . "''
			AND sch.STUDENT_ID=sem.STUDENT_ID
			AND sch.SYEAR=s.SYEAR
			AND sch.SCHOOL_ID=sem.SCHOOL_ID
			AND sch.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" ) );

		if ( $allowed_ids_RET
			&& $allowed_ids_RET[1]['ALLOWED_IDS'] )
		{
			// For example: {70,10,1}.
			$allowed_ids = explode( ',', mb_substr( $allowed_ids_RET[1]['ALLOWED_IDS'], 1, -1 ) );
		}
	}

	return $allowed_ids;
}


function _getMessageFromIDColumn( $message_id )
{
	static $from = array();

	if ( ! $message_id )
	{
		return '';
	}

	if ( ! isset( $from[ $message_id ] ) )
	{
		$from_RET = DBQuery( DBGet( "SELECT STUDENT_ID AS ID, 'student_id' AS COLUMN
			FROM STUDENTXMESSAGE
			WHERE MESSAGE_ID='" . $message_id . "'
			AND STATUS='sent'" ) );

		if ( ! $from_RET
			|| ! isset( $from_RET[1]['ID'] ) )
		{
			$from_RET = DBQuery( DBGet( "SELECT STAFF_ID AS ID, 'staff_id' AS COLUMN
				FROM USERXMESSAGE
				WHERE MESSAGE_ID='" . $message_id . "'
				AND STATUS='sent'" ) );
		}

		if ( $from_RET
			&& isset( $from_RET[1]['ID'] ) )
		{
			$from[ $message_id ] = array(
				'ID' => $from_RET[1]['ID'],
				'COLUMN' => $from_RET[1]['COLUMN']
			);
		}
		else
		{
			$from[ $message_id ] = array();
		}
	}

	return $from[ $message_id ];
}


function _getMessageFromName( $message_id )
{
	static $names = array();

	if ( ! $message_id )
	{
		return '';
	}

	if ( ! isset( $names[ $message_id ] ) )
	{
		$from = _getMessageFromIDColumn( $message_id );

		if ( isset( $from['COLUMN'] ) )
		{
			if ( $from['COLUMN'] === 'student_id' )
			{
				$name_RET = DBGet( DBQuery(
					"SELECT FIRST_NAME||' '||LAST_NAME||coalesce(' '||NAME_SUFFIX,'') AS NAME
					FROM STUDENTS
					WHERE STUDENT_ID='" . $from['ID'] . "'" ) );
			}
			else
			{
				$name_RET = DBGet( DBQuery( "SELECT FIRST_NAME||' '||LAST_NAME AS NAME
					FROM STAFF
					WHERE STAFF_ID='" . $from['ID'] . "'" ) );
			}
		}

		if ( $name_RET
			&& isset( $name_RET['NAME'] ) )
		{
			$names[ $message_id ] = $name_RET['NAME'];
		}
		else
		{
			$names[ $message_id ] = '';
		}
	}

	return $names[ $message_id ];
}