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
	else
	{
		$error[] = dgettext( 'Messaging', 'The message could not be sent.' );
	}
}

// Allow Edit if non admin.
if ( User( 'PROFILE' ) !== 'admin' )
{
	$_ROSARIO['allow_edit'] = true;
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
echo '<input type="hidden" name="recipients_key" value="" />';

// Recipients IDs hidden field.
echo '<input type="hidden" name="recipients_ids" value="" />';

$subject = $original_message = '';

// If is reply, get Subject as "Re: Original subject".
if ( isset( $_REQUEST['reply_to_id'] ) )
{
	$reply = GetReplySubjectMessage( $_REQUEST['reply_to_id'] );

	if ( $reply )
	{
		echo '<input type="hidden" name="reply_to_id" value="' . $_REQUEST['reply_to_id'] . '" />';

		$subject = $reply['subject'];

		$original_message = $reply['message'];
	}
}

// Subject field.
DrawHeader( TextInput(
	$subject,
	'subject',
	_( 'Subject' ),
	'required maxlength="100" class="width-100p"',
	false
) );

// Original message if Reply.
if ( $original_message )
{
	DrawHeader( '<div class="markdown-to-html" style="padding: 10px;">' . $original_message . '</div>' );
}

// Message field.
DrawHeader( TextAreaInput(
	'',
	'message',
	_( 'Message' ),
	'required'
) );

// Send button.
echo '<br /><div class="center">' .
	SubmitButton( dgettext( 'Messaging', 'Send' ), 'send' ) .
	'</div></form>';
