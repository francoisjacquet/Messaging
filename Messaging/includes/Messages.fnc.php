<?php
/**
 * Messages functions
 * List output
 * Views
 *
 * @package Messaging module
 */

function MessagesListOutput( $view )
{
	$views_data = GetMessagesViewsData();

	// Check View.
	if ( ! $view
		|| ! in_array( $view, array_keys( $views_data ) ) )
	{
		return false;
	}

	$view_data = $views_data[ $view ];

	$current_user = GetCurrentMessagingUser();

	$columns_sql = 'm.' . implode( ', m.', array_keys( $view_data['columns'] ) );

	$view_sql = "SELECT m.MESSAGE_ID, uxm.STATUS, " . $columns_sql . "
		FROM MESSAGES m, USERXMESSAGE uxm
		WHERE m.SYEAR='" . UserSyear() . "'
		AND m.SCHOOL_ID='" . UserSchool() . "'
		AND m.MESSAGE_ID=uxm.MESSAGE_ID
		AND uxm.KEY='" . $current_user['key'] . "'
		AND uxm.USER_ID='" . $current_user['user_id'] . "'
		AND uxm.STATUS='" . $view . "'";

	$view_RET = DBGet(
		DBQuery( $view_sql ),
		array(
			'DATETIME' => '_makeMessageDate',
			'FROM' => '_makeMessageFrom',
			'RECIPIENTS' => '_makeMessageRecipients',
			'SUBJECT' => '_makeMessageSubject',
		)
	);

	ListOutput( $view_RET, $view_data['columns'], $view_data['singular'], $view_data['plural'] );

	return true;
}


function GetMessagesViewsData()
{
	static $views_data = array();

	if ( ! $views_data )
	{
		$link_base = PreparePHP_SELF( array(), array( 'view', 'message_id' ) );

		$columns_sent = array(
			'SUBJECT' => _( 'Subject' ),
			'RECIPIENTS' => _( 'Recipients' ),
			'DATETIME' => _( 'Date' ),
		);

		$columns = array(
			'SUBJECT' => _( 'Subject' ),
			'FROM' => _( 'From' ),
			'DATETIME' => _( 'Date' ),
		);

		$views_data = array(
			'unread' => array(
				'label' => dgettext( 'Messaging', 'Unread' ),
				'singular' => dgettext( 'Messaging', 'Unread message' ),
				'plural' => dgettext( 'Messaging', 'Unread messages' ),
				'link' =>  $link_base . '&amp;view=unread',
				'columns' => $columns,
			),
			'read' => array(
				'label' => dgettext( 'Messaging', 'Read' ),
				'singular' => dgettext( 'Messaging', 'Read message' ),
				'plural' => dgettext( 'Messaging', 'Read messages' ),
				'link' =>  $link_base . '&amp;view=read',
				'columns' => $columns,
			),
			'archived' => array(
				'label' => dgettext( 'Messaging', 'Archived' ),
				'singular' => dgettext( 'Messaging', 'Archived message' ),
				'plural' => dgettext( 'Messaging', 'Archived messages' ),
				'link' =>  $link_base . '&amp;view=archived',
				'columns' => $columns,
			),
			'sent' => array(
				'label' => dgettext( 'Messaging', 'Sent' ),
				'singular' => dgettext( 'Messaging', 'Sent message' ),
				'plural' => dgettext( 'Messaging', 'Sent messages' ),
				'link' =>  $link_base . '&amp;view=sent',
				'columns' => $columns_sent,
			),
		);
	}

	return $views_data;
}


function _makeMessageDate( $value, $column )
{
	return ProperDate( mb_substr( $value, 0, 10 ) ) . mb_substr( $value, 10 );
}


function _makeMessageDateHeader( $value, $column )
{
	return _( 'Date' ) . ': ' . _makeMessageDate( $value, $column );
}


function _makeMessageFrom( $value, $column )
{
	$from = unserialize( $value );

	// TODO: add Photo tooltip (if function_exists())!
	return $from['name'];
}


function _makeMessageFromHeader( $value, $column )
{
	return _( 'From' ) . ': ' . _makeMessageFrom( $value, $column );
}


function _makeMessageRecipients( $value, $column )
{
	$len = mb_strlen( $value );

	if ( $len <= 40 )
	{
		return $value;
	}

	// Truncate value to 40 chars.
	return mb_substr( $value, 0, 37 ) . '...';
}


function _makeMessageRecipientsHeader( $value, $column )
{
	$recipients_trucated = _makeMessageRecipients( $value, $column );

	// TODO: give option to view ALL recipients.
	return _( 'To' ) . ': ' . $recipients_trucated;
}


function _makeMessageSubject( $value, $column )
{
	global $THIS_RET;

	$msg_id = $THIS_RET['MESSAGE_ID'];

	$status = $THIS_RET['STATUS'];

	$view_message_link = PreparePHP_SELF(
		array(),
		array(),
		array( 'view' => 'message', 'message_id' => $msg_id )
	);

	// TODO: add ColorBox link (if function_exists())!
	if ( function_exists( 'includeOnceColorBox' ) )
	{
		includeOnceColorBox();
	}

	$extra = '';

	if ( $status === 'unread' )
	{
		$extra = ' style="font-weight:bold;"';
	}

	return '<a href="' . $view_message_link . '"' . $extra . '>' .
		$value . '</a>';
}


function _makeMessageSubjectHeader( $value, $column )
{
	return _( 'Subject' ) . ': <b>' . $value . '</b>';
}


function _makeMessageData( $value, $column )
{
	$data = unserialize( $value );

	return '<div class="markdown-to-html" style="padding: 10px;">' . $data['message'] .	'</div>';
}


function MessageOutput( $msg_id )
{
	// Check message ID.
	if ( ! $msg_id
		|| (string) (int) $msg_id !== $msg_id
		|| $msg_id < 1 )
	{
		return false;
	}

	$current_user = GetCurrentMessagingUser();

	// Get Message data.
	$msg_sql = "SELECT m.DATETIME, m.FROM, m.RECIPIENTS, m.SUBJECT, m.DATA, uxm.STATUS
		FROM MESSAGES m, USERXMESSAGE uxm
		WHERE m.SYEAR='" . UserSyear() . "'
		AND m.SCHOOL_ID='" . UserSchool() . "'
		AND m.MESSAGE_ID='" . $msg_id . "'
		AND m.MESSAGE_ID=uxm.MESSAGE_ID
		AND uxm.KEY='" . $current_user['key'] . "'
		AND uxm.USER_ID='" . $current_user['user_id'] . "'
		LIMIT 1";

	$msg_RET = DBGet(
		DBQuery( $msg_sql ),
		array(
			'DATETIME' => '_makeMessageDateHeader',
			'FROM' => '_makeMessageFromHeader',
			'RECIPIENTS' => '_makeMessageRecipientsHeader',
			'SUBJECT' => '_makeMessageSubjectHeader',
			'DATA' => '_makeMessageData',
		)
	);

	if ( ! $msg_RET
		|| ! isset( $msg_RET[1] ) )
	{
		return false;
	}


	$msg = $msg_RET[1];

	// Back to ? text & link.
	$views_data = GetMessagesViewsData();

	$back_to_text = $views_data[ $msg['STATUS'] ]['plural'];

	$back_to_link = $views_data[ $msg['STATUS'] ]['link'];

	// Back to link & Reply link.
	DrawHeader(
		'<a href="' . $back_to_link . '">' .
			sprintf( dgettext( 'Messaging', 'Back to %s' ), $back_to_text ) . '</a>',
		( $msg['STATUS'] !== 'sent' ?
			'<a href="Modules.php?modname=Messaging/Write.php&reply_to_id=' . $msg_id . '">' .
				dgettext( 'Messaging', 'Reply' ) . '</a>' :
			'' )
	);

	DrawHeader( $msg['FROM'] );

	DrawHeader( $msg['SUBJECT'] );

	DrawHeader( $msg['RECIPIENTS'] );

	DrawHeader( $msg['DATETIME'] );

	echo $msg['DATA'];

	// If status === 'unread', change to 'read'.
	if ( $msg['STATUS'] === 'unread' )
	{
		_changeMessageStatus( $msg_id, 'read' );
	}

	return true;
}


function _changeMessageStatus( $msg_id, $status )
{
	// Check message ID.
	if ( ! $msg_id
		|| (string) (int) $msg_id !== $msg_id
		|| $msg_id < 1 )
	{
		return false;
	}

	$views_data = GetMessagesViewsData();

	$status_list = array_keys( $views_data );

	// Check status.
	if ( ! in_array( $status, $status_list ) )
	{
		return false;
	}

	$current_user = GetCurrentMessagingUser();

	$status_sql = "UPDATE USERXMESSAGE
		SET STATUS='" . $status . "'
		WHERE KEY='" . $current_user['key'] . "'
		AND USER_ID='" . $current_user['user_id'] . "'
		AND MESSAGE_ID='" . $msg_id . "'";

	$status_RET = DBGet( DBQuery( $status_sql ) );

	return (bool) $status_RET;
}