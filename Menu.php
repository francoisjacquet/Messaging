<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the Messaging module
 *
 * @package Messaging module
 */

/**
 * Use dgettext() function instead of _() for Module specific strings translation
 * see locale/MessagesME file for more information.
 */
$module_name = dgettext( 'Messaging', 'Messaging' );

// Menu entries for the Messaging module.
$menu['Messaging']['admin'] = array( // Admin menu.
	'default' => 'Messaging/Messages.php', // Program loaded by default when menu opened.
	'Messaging/Messages.php' => dgettext( 'Messaging', 'Messages' ),
	'Messaging/Write.php' => dgettext( 'Messaging', 'Write' ),
);

$menu['Messaging']['teacher'] = array( // Teacher menu
	'default' => 'Messaging/Messages.php', // Program loaded by default when menu opened.
	'Messaging/Messages.php' => dgettext( 'Messaging', 'Messages' ),
	'Messaging/Write.php' => dgettext( 'Messaging', 'Write' ),
);

$menu['Messaging']['parent'] = $menu['Messaging']['teacher']; // Parent & student menu.
