<?php
/**
 * Write (new Message or a Reply)
 *
 * @package Messaging module
 */

// Include Common functions.
require_once 'modules/Messaging/includes/Common.fnc.php';

// Include Write functions.
require_once 'modules/Messaging/includes/Write.fnc.php';

if ( isset( $_POST['send'] ) )
{
	// Send message.
	$sent = SendMessage( array(
		'reply_to_id' => isset( $_REQUEST['reply_to_id'] ) ? $_REQUEST['reply_to_id'] : '',
		'recipients_key' => isset( $_REQUEST['recipients_key'] ) ? $_REQUEST['recipients_key'] : '',
		'recipients_ids' => isset( $_REQUEST['recipients_ids'] ) ? $_REQUEST['recipients_ids'] : '',
		'message' => isset( $_POST['message'] ) ? $_POST['message'] : '', // Bypass strip_tags.
		'subject' => isset( $_REQUEST['subject'] ) ? $_REQUEST['subject'] : '',
	) );

	if ( $sent )
	{
		$note[] = button( 'check', '', '', 'bigger' ) . '&nbsp;' . dgettext( 'Messaging', 'Message sent.' );
	}
	elseif ( ! $error )
	{
		$error[] = dgettext( 'Messaging', 'The message could not be sent.' );
	}
}
elseif ( isset( $_REQUEST['reply_to_id'] )
		&& $_REQUEST['reply_to_id'] )
{
	$reply = GetReplySubjectMessage( $_REQUEST['reply_to_id'] );
}


// Allow Edit if non admin.
if ( User( 'PROFILE' ) !== 'admin' )
{
	$_ROSARIO['allow_edit'] = true;
}


$title = ProgramTitle();

if ( SchoolInfo( 'SCHOOLS_NB' ) > 1
	&& User( 'PROFILE' ) !== 'student' )
{
	// If more than 1 school, mention current school.
	$title .= ' (' . SchoolInfo( 'TITLE' ) . ')';
}

DrawHeader( $title );

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );


if ( ! $reply )
{
	$recipients_key = '';

	// Get allowed recipients keys.
	$recipients_keys = GetAllowedRecipientsKeys( User( 'PROFILE' ) );

	if ( ! $recipients_keys )
	{
		// If no allowed recipients keys, display fatal error.
		$error[] = dgettext( 'Messaging', 'You are not allowed to send messages.' );

		echo ErrorMessage( $error, 'fatal' );
	}
	elseif ( count( $recipients_keys ) > 1 )
	{
		$search_staff_url = PreparePHP_SELF(
			$_REQUEST,
			array( 'search_modfunc', 'reply_to_id', 'teacher_staff' ),
			array( 'recipients_key' => 'staff_id' )
		);

		$search_teacher_staff_url = PreparePHP_SELF(
			$_REQUEST,
			array( 'search_modfunc', 'reply_to_id' ),
			array( 'recipients_key' => 'staff_id', 'teacher_staff' => 'Y' )
		);

		$search_student_url = PreparePHP_SELF(
			$_REQUEST,
			array( 'search_modfunc', 'reply_to_id', 'teacher_staff' ),
			array( 'recipients_key' => 'student_id' )
		);

		// If more than one allowed recipients key, display Users | Students.
		// For Teachers, it will be Parents | Students | Staff.
		$header = '<a href="' . $search_staff_url . '"><b>' .
			( User( 'PROFILE' ) === 'admin' ? _( 'Users' ) : _( 'Parents' ) ) . '</b></a>';
		$header .= ' | <a href="' . $search_student_url . '"><b>' .
			_( 'Students' ) . '</b></a>';

		if ( User( 'PROFILE' ) === 'teacher' )
		{
			$header .= ' | <a href="' . $search_teacher_staff_url . '"><b>' .
				_( 'Staff' ) . '</b></a>';
		}

		echo DrawHeader( $header );

		// Search screen.
		if ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' )
		{
			if ( isset( $_REQUEST['recipients_key'] )
				&& in_array( $_REQUEST['recipients_key'], $recipients_keys ) )
			{
				$recipients_key = $_REQUEST['recipients_key'];
			}
			else
			{
				// Defaults to student_id for Teachers, to staff_id for Admins.
				$recipients_key = User( 'PROFILE' ) === 'teacher' ? 'student_id' : 'staff_id';
			}

			if ( ! isset( $_REQUEST['search_modfunc'] )
				&& ! isset( $_REQUEST['teacher_staff'] ) )
			{
				$extra['new'] = true;

				// Pass recipients_key to next screen.
				$extra['action'] = '&recipients_key=' . $recipients_key;

				if ( User( 'PROFILE' ) === 'teacher'
					&& $recipients_key === 'staff_id' )
				{
					// Find a Parent.
					$extra['search_title'] = dgettext( 'Messaging', 'Find a Parent' );

					$extra['profile'] = 'parent';
				}

				// Only for admins and teachers.
				Search( $recipients_key, $extra );

				// Unset Recipients key so Write form is not displayed.
				$recipients_key = '';
			}
		}
	}
	else
	{
		if ( count( $recipients_keys ) === 1 )
		{
			$recipients_key = $recipients_keys[0];
		}
		elseif ( isset( $_REQUEST['recipients_key'] )
			&& in_array( $_REQUEST['recipients_key'], $recipients_keys ) )
		{
			$recipients_key = $_REQUEST['recipients_key'];
		}
	}
}


// Is reply or Recipients key set.
if ( $reply
	|| $recipients_key )
{
	// Write form.
	echo '<form method="POST" action="' . PreparePHP_SELF() . '">';

	// TODO: test when changing SYEAR / SCHOOL
	// Recipients key hidden field.
	echo '<input type="hidden" name="recipients_key" value="' . $recipients_key . '" />';

	$subject = $original_message = '';

	// If is reply, get Subject as "Re: Original subject".
	if ( $reply )
	{
		echo '<input type="hidden" name="reply_to_id" value="' . $_REQUEST['reply_to_id'] . '" />';

		$subject = $reply['subject'];

		$original_message = $reply['message'];
	}

	// Send button.
	echo DrawHeader( '', SubmitButton( dgettext( 'Messaging', 'Send' ), 'send' ) );

	// Subject field.
	DrawHeader( TextInput(
		$subject,
		'subject',
		dgettext( 'Messaging', 'Subject' ),
		'required maxlength="100" class="width-100p"',
		false
	) );

	// Original message if Reply.
	if ( $original_message )
	{
		DrawHeader( '<div class="markdown-to-html" style="padding: 10px;">' . $original_message . '</div>' );
	}

	// Message field.
	DrawHeader( TinyMCEInput(
		'',
		'message',
		_( 'Message' )
	) );


	// Search results.
	if ( ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' )
		&& isset( $_REQUEST['search_modfunc'] )
		&& ! $reply )
	{
		// Choose recipients checkboxes.
		$extra['SELECT'] = ",'' AS CHECKBOX";
		$extra['functions'] = array( 'CHECKBOX' => '_makeWriteChooseCheckbox' );
		$extra['columns_before'] = array(
			'CHECKBOX' => '</a><input type="checkbox" value="Y" checked name="controller" onclick="checkAll(this.form,this.checked,\'recipients_ids\');" /><A>'
		);

		// Force search.
		$extra['new'] = true;

		// No ListOutput search form.
		$extra['options']['search'] = false;

		// No link for name.
		$extra['link']['FULL_NAME'] = false;

		if ( $recipients_key === 'staff_id' )
		{
			// Do not send message to self.
			$extra['WHERE'] .= " AND s.STAFF_ID<>'" . User( 'STAFF_ID' ) . "' ";
		}

		// Deactivate Search All Schools.
		$_REQUEST['_search_all_schools'] = false;

		// Only for admins and teachers.
		// TODO: try to allow Admin search for Teachers.
		Search( $recipients_key, $extra );
	}
	elseif ( ! $reply )
	{
		$value = $allow_na = $div = false;

		// Multiple select input.
		$extra = 'multiple';

		$add_label = '';

		// TODO add current school / student for Teachers / Parents.
		/*if ( User( 'PROFILE' ) === 'teacher'
			&& SchoolInfo( 'SCHOOLS_NB' ) > 1 )
		{
			// If teaches in more than one school.
			$add_label = ' (' . SchoolInfo( 'TITLE' ) . ')';
		}
		elseif ( User( 'PROFILE' ) === 'parent' )
		{
			$student_name_RET = DBGet( DBQuery( "SELECT s.LAST_NAME||', '||s.FIRST_NAME AS NAME
					FROM STUDENTS s,STUDENT_ENROLLMENT se
					WHERE se.STUDENT_ID='" . UserStudentID() . "'
					AND s.STUDENT_ID=se.STUDENT_ID
					AND se.SYEAR='" . UserSyear() . "'" );

			// If more than one student.
			$add_label = ' (' . ')';
		}*/

		// Display Teachers select.
		$teachers_options = GetRecipientsInfo( User( 'PROFILE' ), 'teacher' );


		$teachers_label = _( 'Teachers' ) . $add_label;


		// Display Admins select.
		$admins_options = GetRecipientsInfo( User( 'PROFILE' ), 'admin' );

		$admins_label = _( 'Administrators' ) . $add_label;

		if ( function_exists( 'ChosenSelectInput' ) ) // @since 2.9.5.
		{
			$select_input_function = 'ChosenSelectInput';
		}
		else // Regular SelectInput().
		{
			$select_input_function = 'SelectInput';

			$extra .= ' title="' . _( 'Hold the CTRL key down to select multiple options' ) . '"';
		}

		$teachers_select = $select_input_function(
			$value,
			'recipients_ids[]',
			$teachers_label,
			$teachers_options,
			$allow_na,
			$extra,
			$div
		);

		$admins_select = $select_input_function(
			$value,
			'recipients_ids[]',
			$admins_label,
			$admins_options,
			$allow_na,
			$extra,
			$div
		);

		DrawHeader(	$teachers_select, $admins_select );
	}

	// Send button.
	echo '<br /><div class="center">' .
		SubmitButton( dgettext( 'Messaging', 'Send' ), 'send' ) .
		'</div></form>';
}
