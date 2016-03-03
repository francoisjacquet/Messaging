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
-- Name: messagexuser; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--
DROP INDEX IF EXISTS messagexuser_ind;

DROP TABLE IF EXISTS messagexuser;


/**
 * Messages table
 */
DROP INDEX IF EXISTS messages_ind;

ALTER TABLE ONLY messages DROP CONSTRAINT IF EXISTS messages_pkey;

DROP SEQUENCE IF EXISTS messages_seq;


--
-- Name: messages; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

DROP TABLE IF EXISTS messages;

