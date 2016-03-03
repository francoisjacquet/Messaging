<?php
/**
 * Messages
 *
 * @package Messaging module
 */

// Include Common functions.
require_once 'modules/Messaging/includes/Common.fnc.php';

// Include Messages functions.
require_once 'modules/Messaging/includes/Messages.fnc.php';

$title = ProgramTitle();

if ( SchoolInfo( 'SCHOOLS_NB' ) > 1
	&& ( User( 'PROFILE' ) === 'admin'
		|| User( 'PROFILE' ) === 'teacher' ) )
{
	// If more than 1 school, mention current school.
	$title .= ' (' . SchoolInfo( 'TITLE' ) . ')';
}

DrawHeader( $title );

if ( $_REQUEST['view'] === 'message' )
{
	MessageOutput( $_REQUEST['message_id'] );

	exit;
}

$views_data = GetMessagesViewsData();

$views_left = '<a href="' . $views_data['unread']['link'] . '">' . $views_data['unread']['label'] .
	'</a> |&nbsp;<a href="' . $views_data['read']['link'] . '">' . $views_data['read']['label'] .
	'</a> |&nbsp;<a href="' . $views_data['archived']['link'] . '">' . $views_data['archived']['label'] . '</a>';

$views_right = '<a href="' . $views_data['sent']['link'] . '">' . $views_data['sent']['label'] . '</a>';

DrawHeader( $views_left, $views_right );

// Get current view.
$current_view = 'unread';

if ( isset( $_REQUEST['view'] )
	&& in_array( $_REQUEST['view'], array_keys( $views_data ) ) )
{
	$current_view = $_REQUEST['view'];
}

// Display View header.
DrawHeader( $views_data[ $current_view ]['plural'] );

// Display View.
MessagesListOutput( $current_view );
