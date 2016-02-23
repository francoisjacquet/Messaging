<?php
/**
 * Write (new Message or a Reply)
 *
 * @package Messaging module
 */

// Include Write functions.
require_once 'modules/Messaging/includes/Write.fnc.php';

if ( isset( $_POST['send'] ) )
{
	// Send message.
	if ( ( isset( $_REQUEST['reply_to_id'] )
			|| ( isset( $_REQUEST['recipients_key'] )
				&& isset( $_REQUEST['recipients_ids'] ) ) )
		&& isset( $_REQUEST['message'] )
		&& isset( $_REQUEST['subject'] ) )
	{
		$sent = SendMessage(
			array(
				'reply_to_id' => $_REQUEST['reply_to_id'],
				'recipients_key' => $_REQUEST['recipients_key'],
				'recipients_ids' => $_REQUEST['recipients_ids'],
				'message' => $_REQUEST['message'],
				'subject' => $_REQUEST['subject'],
		) );
	}
	else
	{
		$error[] = dgettext( 'Messaging', 'The message could not be sent. Form elements are missing.' );
	}
}

DrawHeader( ProgramTitle() );

if ( isset( $error ) )
{
	echo ErrorMessage( $error );
}

if ( isset( $note ) )
{
	echo ErrorMessage( $note, 'note' );
}

// Write form.
echo '<form method="POST" action="' . PreparePHP_SELF() . '">';

// TODO: test when changing SYEAR / SCHOOL
// Recipients key hidden field.
echo '<input type="hidden" name="recipients_key" value="staff_id" />';

// Recipients IDs hidden field.
echo '<input type="hidden" name="recipients_ids" value="1" />';

// Subject field.
echo TextInput();

// Message field.
echo TextAreaInput();

// Send button.
echo SubmitButton( dgettext( 'Send', 'Messaging' ), 'send' );

echo '</form>';
