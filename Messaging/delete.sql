/**
 * Delete SQL
 *
 * Required if install.sql file present
 * - Delete profile exceptions
 * - Delete module specific tables
 * (and their eventual sequences & indexes) if any
 *
 * @package Messaging module
 */

--
-- Delete from profile_exceptions table
--

DELETE FROM profile_exceptions WHERE modname='Messaging/Messages.php';
DELETE FROM profile_exceptions WHERE modname='Messaging/Write.php';


/**
 * Delete module tables
 */

/**
 * Student cross message table
 */
--
-- Name: studentxmessage; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

DROP TABLE studentxmessage;


/**
 * User cross message table
 */
--
-- Name: userxmessage; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

DROP TABLE userxmessage;


/**
 * Messages table
 */
--
-- Name: messages; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

DROP TABLE messages;
