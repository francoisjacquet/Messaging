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
/**
 * User cross message table
 */
--
-- Name: userxmessage; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--
DROP INDEX userxmessage_ind;

DROP TABLE userxmessage;


/**
 * Messages table
 */
DROP INDEX messages_ind;

ALTER TABLE ONLY messages DROP CONSTRAINT messages_pkey;

DROP SEQUENCE messages_seq;


--
-- Name: messages; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

DROP TABLE messages;

