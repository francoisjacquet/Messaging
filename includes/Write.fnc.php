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
	if ( ( ( ! isset( $msg['reply_to_id'] )
				|| (string) $msg['reply_to_id'] === '' )
			&& ( ! isset( $msg['recipients_key'] )
				|| (string) $msg['recipients_key'] === ''
				|| ! isset( $msg['recipients_ids'] )
				|| ! $msg['recipients_ids'] ) )
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
		$recipients = _getMessageRecipients( (string) $msg['recipients_key'], (array) $msg['recipients_ids'] );
	}

	// Check & Get Recipients.
	if ( ! $recipients )
	{
		$error[] = dgettext( 'Messaging', 'You are not allowed to send a message to those recipients.' );

		return false;
	}

	// Serialize From.
	$from = DBEscapeString( serialize( GetCurrentMessagingUser() ) );

	// Sanitize Message.
	if ( version_compare( ROSARIO_VERSION, '2.9-alpha', '>=' ) )
	{
		// Is MarkDown.
		require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

		$sanitized_msg = SanitizeMarkDown( $msg['message'] );
	}
	else
	{
		// Strip tags.
		$sanitized_msg = str_replace( "'", '&#039;', strip_tags( $msg['message'] ) );
	}

	// Serialize Data.
	$data = serialize( array( 'message' => $sanitized_msg ) );

	$subject = $msg['subject'];

	// Limit Subject to 100 chars.
	if ( mb_strlen( $subject ) > 100 )
	{
		$subject = mb_substr( $subject, 0, 100 );
	}

	// Get new Message ID.
	$msg_id_RET = DBGet( DBQuery( "SELECT " . db_seq_nextval( 'MESSAGES_SEQ' ) . " AS ID" ) );

	$msg_id = $msg_id_RET[1]['ID'];

	// Save Message.
	DBQuery( "INSERT INTO MESSAGES VALUES(
		'" . $msg_id . "',
		'" . UserSyear() . "',
		'" . UserSchool() . "',
		'" . $from . "',
		'" . $recipients . "',
		'" . $subject . "',
		CURRENT_TIMESTAMP,
		'" . $data . "'
	)" );

	if ( (string) (int) $msg['reply_to_id'] === $msg['reply_to_id'] )
	{
		$recipient = _getMessageFrom( $msg['reply_to_id'] );

		if ( ! $recipient )
		{
			// Original sender not found!
			// Unprobable here: already checked in _getMessageRecipients().
			return false;
		}

		return _saveMessageSenderRecipients( $msg_id, $recipient['key'], $recipient['user_id'] );
	}

	// Save Recipients in cross tables.
	if ( $msg['recipients_key'] === 'student_id'
		|| $msg['recipients_key'] === 'staff_id' )
	{
		return _saveMessageSenderRecipients( $msg_id, $msg['recipients_key'], $msg['recipients_ids'] );
	}
	else
	{
		// Wrong recipients key!
		// Unprobable here: already checked in _getMessageRecipients().
		return false;
	}
}


function _saveMessageSenderRecipients( $msg_id, $key, $recipients_ids )
{
	if ( ! in_array( $key, array( 'student_id', 'staff_id' ) )
		|| ! $recipients_ids
		|| ! $msg_id )
	{
		return false;
	}

	foreach ( (array) $recipients_ids as $recipient_id )
	{
		DBQuery( "INSERT INTO MESSAGEXUSER VALUES(
			'" . $recipient_id . "',
			'" . $key . "',
			'" . $msg_id . "',
			'unread'
		)" );
	}

	$sender = GetCurrentMessagingUser();

	// Save Sender.
	DBQuery( "INSERT INTO MESSAGEXUSER VALUES(
		'" . $sender['user_id'] . "',
		'" . $sender['key'] . "',
		'" . $msg_id . "',
		'sent'
	)" );

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

		// Get User.
		$user = GetCurrentMessagingUser();

		// Reply: just check the reply to ID is allowed (the message has been sent to him first).
		$allowed_reply_RET = DBGet( DBQuery( "SELECT 1
			FROM MESSAGEXUSER
			WHERE MESSAGE_ID='" . $reply_to_id . "'
			AND USER_ID='" . $user['user_id'] . "'
			AND KEY='" . $user['key'] . "'
			AND STATUS<>'sent'" ) );

		if ( ! $allowed_reply_RET )
		{
			return '';
		}

		// Get Recipient == Original message From.
		$recipient = _getMessageFrom( $reply_to_id );

		return $recipient['name'];
	}

	$recipients_keys = GetAllowedRecipientsKeys( User( 'PROFILE' ) );

	// Check parameters.
	if ( ! isset( $recipients_key )
		|| ! in_array( $recipients_key, $recipients_keys )
		|| ! $recipients_ids )
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

	// Recipients.
	foreach ( (array) $recipients_ids as $recipient_id )
	{
		$allowed_recipient = _checkMessageRecipient( $recipients_key, $recipient_id );
	}

	if ( ! $allowed_recipient )
	{
		return '';
	}

	if ( $recipients_key === 'student_id' )
	{
		$names_RET = DBGet( DBQuery(
			"SELECT array_agg(FIRST_NAME||' '||LAST_NAME||coalesce(' '||NAME_SUFFIX,' ')) AS NAMES
			FROM STUDENTS
			WHERE STUDENT_ID IN('" . implode( "','", $recipients_ids ) . "')" ) );
	}
	elseif ( $recipients_key === 'staff_id' )
	{
		$names_RET = DBGet( DBQuery( "SELECT array_agg(FIRST_NAME||' '||LAST_NAME) AS NAMES
			FROM STAFF
			WHERE STAFF_ID IN('" . implode( "','", $recipients_ids ) . "')" ) );
	}

	if ( isset( $names_RET[1]['NAMES'] ) )
	{
		// For example: {"Student Student","Andrea Mazariegos Jr"}.
		// Return Student Student, Andrea Mazariegos Jr.
		return str_replace('","', ', ', mb_substr( $names_RET[1]['NAMES'], 2, -2 ) );
	}

	return '';
}


function _checkMessageRecipient( $recipient_key, $recipient_id )
{
	$recipients_keys = GetAllowedRecipientsKeys( User( 'PROFILE' ) );

	// Check parameters.
	if ( ! isset( $recipient_key )
		|| ! in_array( $recipient_key, $recipients_keys )
		|| ! isset( $recipient_id )
		|| (string) (int) $recipient_id !== $recipient_id )
	{
		return false;
	}

	// Check Recipient ID is allowed.
	// Check not self.
	if ( $recipient_key === 'staff_id'
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
					$allowed = _getStudentAllowedTeachersRecipients( $_SESSION['STUDENT_ID'] );
					$allowed = array_merge( $allowed, _getStudentAllowedAdminsRecipients() );
				}
				elseif ( User( 'PROFILE' ) === 'parent' )
				{
					// Parent: allow students' Teachers + Admin staff.
					$allowed = _getParentAllowedTeachersRecipients();
					$allowed = array_merge( $allowed, _getParentAllowedAdminsRecipients() );
				}
				elseif ( User( 'PROFILE' ) === 'teacher' )
				{
					// Teachers: Parents of related students + Admin staff + other Teachers.
					$allowed = _getTeacherAllowedParentsRecipients(); // see SetUserStaffID()!
					$allowed = array_merge( $allowed, _getTeacherAllowedAdminsRecipients() );
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


function GetAllowedRecipientsKeys( $profile )
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


function _getAllowedAdminsRecipients()
{
	static $allowed_ids = array();

	if ( ! $allowed_ids )
	{
		$allowed_ids_RET = DBGet( DBQuery( "SELECT array_agg(STAFF_ID) as ALLOWED_IDS
			FROM STAFF
			WHERE PROFILE='admin'
			AND SYEAR='" . UserSyear() . "'
			AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)" ) );

		if ( isset( $allowed_ids_RET[1]['ALLOWED_IDS'] ) )
		{
			// For example: {70,10,1}.
			$allowed_ids = explode( ',', mb_substr( $allowed_ids_RET[1]['ALLOWED_IDS'], 1, -1 ) );
		}
	}

	return $allowed_ids;
}


function _getAllowedTeachersRecipients()
{
	static $allowed_ids = array();

	if ( ! $allowed_ids )
	{
		$allowed_ids_RET = DBGet( DBQuery( "SELECT array_agg(STAFF_ID) as ALLOWED_IDS
			FROM STAFF
			WHERE PROFILE='teacher'
			AND SYEAR='" . UserSyear() . "'
			AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)" ) );

		if ( isset( $allowed_ids_RET[1]['ALLOWED_IDS'] ) )
		{
			// For example: {70,10,1}.
			$allowed_ids = explode( ',', mb_substr( $allowed_ids_RET[1]['ALLOWED_IDS'], 1, -1 ) );
		}
	}

	return $allowed_ids;
}


function _getStudentAllowedAdminsRecipients()
{
	return _getAllowedAdminsRecipients();
}


function _getParentAllowedAdminsRecipients()
{
	return _getAllowedAdminsRecipients();
}


function _getTeacherAllowedAdminsRecipients()
{
	return _getAllowedAdminsRecipients();
}


function _getTeacherAllowedTeachersRecipients()
{
	return _getAllowedTeachersRecipients();
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

		if ( isset( $allowed_ids_RET[1]['ALLOWED_IDS'] ) )
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
		// Get Parent Students for current school.
		$students_RET = DBGet( DBQuery( "SELECT sju.STUDENT_ID
			FROM STUDENTS s,STUDENTS_JOIN_USERS sju,STUDENT_ENROLLMENT se 
			WHERE s.STUDENT_ID=sju.STUDENT_ID 
			AND sju.STAFF_ID='" . User( 'STAFF_ID' ) . "' 
			AND se.SYEAR='" . UserSyear() . "'
			AND se.SCHOOL_ID ='" . UserSchool() . "'
			AND se.STUDENT_ID=sju.STUDENT_ID 
			AND ('" . DBDate() . "'>=se.START_DATE
				AND ('" . DBDate() . "'<=se.END_DATE
					OR se.END_DATE IS NULL ) )" ) );

		// Get each student's Teachers.
		foreach ( (array) $students_RET as $student )
		{
			$allowed_ids = array_merge( $allowed_ids, _getStudentAllowedTeachersRecipients( $student['STUDENT_ID'] ) );
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
			AND sem.SCHOOL_ID='" . UserSchool() . "'
			AND sch.STUDENT_ID=sem.STUDENT_ID
			AND sch.SYEAR=sem.SYEAR
			AND sch.SCHOOL_ID=sem.SCHOOL_ID
			AND sch.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" ) );

		if ( isset( $allowed_ids_RET[1]['ALLOWED_IDS'] ) )
		{
			// For example: {70,10,1}.
			$allowed_ids = explode( ',', mb_substr( $allowed_ids_RET[1]['ALLOWED_IDS'], 1, -1 ) );
		}
	}

	return $allowed_ids;
}


function _getMessageFrom( $msg_id )
{
	static $from = array();

	if ( ! $msg_id )
	{
		return array();
	}

	if ( ! isset( $from[ $msg_id ] ) )
	{
		$from_RET = DBGet( DBQuery( "SELECT m.FROM
			FROM MESSAGES m
			WHERE m.MESSAGE_ID='" . $msg_id . "'" ) );

		if ( isset( $from_RET[1]['FROM'] ) )
		{
			$from[ $msg_id ] = unserialize( $from_RET[1]['FROM'] );
		}
		else
		{
			$from[ $msg_id ] = array();
		}
	}

	return $from[ $msg_id ];
}


function GetReplySubjectMessage( $msg_id )
{
	// Check message ID.
	if ( ! $msg_id
		|| (string) (int) $msg_id !== $msg_id
		|| $msg_id < 1 )
	{
		return array();
	}

	// Get User.
	$user = GetCurrentMessagingUser();

	// Get message Subject.
	$subject_message_sql = "SELECT m.SUBJECT, m.DATA
		FROM MESSAGES m, MESSAGEXUSER mxu
		WHERE m.MESSAGE_ID='" . $msg_id . "'
		AND m.SYEAR='" . UserSyear() . "'
		AND m.SCHOOL_ID='" . UserSchool() . "'
		AND mxu.MESSAGE_ID=m.MESSAGE_ID
		AND mxu.KEY='" . $user['key'] . "'
		AND mxu.USER_ID='" . $user['user_id'] . "'
		AND mxu.STATUS<>'sent'";

	$subject_message_RET = DBGet( DBQuery( $subject_message_sql ) );

	if ( ! isset( $subject_message_RET[1]['SUBJECT'] ) )
	{
		return array();
	}

	$subject = $subject_message_RET[1]['SUBJECT'];

	// Add "Re:" once!
	if ( mb_strpos( $subject, sprintf( dgettext( 'Messaging', 'Re: %s' ), '' ) ) !== 0 )
	{
		$subject = sprintf( dgettext( 'Messaging', 'Re: %s' ), $subject );
	}

	$data = unserialize( $subject_message_RET[1]['DATA'] );

	$message = $data['message'];

	return array( 'subject' => $subject, 'message' => $message ); 
}


function GetRecipientsInfo( $user_profile, $recipients_profile = 'teacher' )
{
	if ( ! $user_profile
		|| ! $recipients_profile )
	{
		return null;
	}

	if ( $user_profile === 'teacher' )
	{
		if ( $recipients_profile === 'teacher' )
		{
			$allowed_ids = _getTeacherAllowedTeachersRecipients();
		}
		else
		{
			$allowed_ids = _getTeacherAllowedAdminsRecipients();
		}
	}
	elseif ( $user_profile === 'student' )
	{
		if ( $recipients_profile === 'teacher' )
		{
			$user = GetCurrentMessagingUser();

			$allowed_ids = _getStudentAllowedTeachersRecipients( $user['user_id'] );
		}
		else
		{
			$allowed_ids = _getStudentAllowedAdminsRecipients();
		}
	}
	elseif ( $user_profile === 'parent' )
	{
		if ( $recipients_profile === 'teacher' )
		{
			$allowed_ids = _getParentAllowedTeachersRecipients();
		}
		else
		{
			$allowed_ids = _getParentAllowedAdminsRecipients();
		}
	}

	if ( ! $allowed_ids )
	{
		return null;
	}

	// Get user name.
	// TODO get Teacher course for Parents & Students => "Name (Course)".
	$users_info_sql = "SELECT STAFF_ID, FIRST_NAME||' '||LAST_NAME AS NAME,
		(SELECT up.TITLE FROM USER_PROFILES up WHERE s.PROFILE_ID NOT IN (1,2) AND s.PROFILE_ID=up.ID) AS PROFILE
		FROM STAFF s
		WHERE s.STAFF_ID IN ('" . implode( "','", $allowed_ids ) . "')
		ORDER BY NAME";

	$users_info_RET = DBGet( DBQuery( $users_info_sql ) );

	$users_options = array();

	foreach ( (array) $users_info_RET as $users_info )
	{
		// Add profile to name if profile != default teacher (2) or admin (1).
		$option = $users_info['NAME'] . ( $users_info['PROFILE'] ? ' (' . $users_info['PROFILE'] . ')' : '' );

		$users_options[ $users_info['STAFF_ID'] ] = $option;
	}

	return $users_options;
}


function _makeWriteChooseCheckbox( $value, $title )
{
	global $THIS_RET;

	$user_id = isset( $THIS_RET['STAFF_ID'] ) ? $THIS_RET['STAFF_ID'] : $THIS_RET['STUDENT_ID'];

	return '<input type="checkbox" name="recipients_ids[]" value="' . $user_id . '" checked />';
}
