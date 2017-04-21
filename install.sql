/**
 * Install SQL
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Messaging module
 */

-- Fix #102 error language "plpgsql" does not exist
-- http://timmurphy.org/2011/08/27/create-language-if-it-doesnt-exist-in-postgresql/
--
-- Name: create_language_plpgsql(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION create_language_plpgsql()
RETURNS BOOLEAN AS $$
    CREATE LANGUAGE plpgsql;
    SELECT TRUE;
$$ LANGUAGE SQL;

SELECT CASE WHEN NOT (
    SELECT TRUE AS exists FROM pg_language
    WHERE lanname='plpgsql'
    UNION
    SELECT FALSE AS exists
    ORDER BY exists DESC
    LIMIT 1
) THEN
    create_language_plpgsql()
ELSE
    FALSE
END AS plpgsql_created;

DROP FUNCTION create_language_plpgsql();


/**
 * profile_exceptions Table
 *
 * profile_id:
 * - 0: student
 * - 1: admin
 * - 2: teacher
 * - 3: parent
 * modname: should match the Menu.php entries
 * can_use: 'Y'
 * can_edit: 'Y' or null (generally null for non admins)
 */
--
-- Data for Name: profile_exceptions; Type: TABLE DATA;
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Messaging/Messages.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Messages.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Messaging/Write.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Write.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Messaging/Messages.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Messages.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Messaging/Write.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Write.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Messaging/Messages.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Messages.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Messaging/Write.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Write.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Messaging/Messages.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Messages.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Messaging/Write.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Write.php'
    AND profile_id=0);



/**
 * Add module tables
 */

/**
 * User cross message table
 */
--
-- Name: messagexuser; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--


CREATE OR REPLACE FUNCTION create_table_messagexuser() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA
        AND tablename = 'messagexuser') THEN
    RAISE NOTICE 'Table "messagexuser" already exists.';
    ELSE
        CREATE TABLE messagexuser (
            user_id numeric NOT NULL,
            key character varying(10),
            message_id numeric NOT NULL,
            status character varying(10) NOT NULL
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_messagexuser();
DROP FUNCTION create_table_messagexuser();



--
-- Name: messagexuser_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_messagexuser_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='messagexuser_ind'
        AND n.nspname=CURRENT_SCHEMA
    ) THEN
        CREATE INDEX messagexuser_ind ON messagexuser (user_id, key, status);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_messagexuser_ind();
DROP FUNCTION create_index_messagexuser_ind();



/**
 * Messages table
 */
--
-- Name: messages; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_messages() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname=CURRENT_SCHEMA
        AND tablename='messages') THEN
    RAISE NOTICE 'Table "messages" already exists.';
    ELSE
        CREATE TABLE messages (
            message_id numeric PRIMARY KEY,
            syear numeric(4,0),
            school_id numeric NOT NULL,
            "from" character varying(255),
            recipients text,
            subject character varying(100),
            "datetime" timestamp(0) without time zone,
            data text
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_messages();
DROP FUNCTION create_table_messages();



--
-- Name: messages_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE OR REPLACE FUNCTION create_sequence_messages_seq() RETURNS void AS
$func$
BEGIN
    CREATE SEQUENCE messages_seq
        START WITH 1
        INCREMENT BY 1
        NO MINVALUE
        NO MAXVALUE
        CACHE 1;
EXCEPTION WHEN duplicate_table THEN
    RAISE NOTICE 'Sequence "messages_seq" already exists.';
END
$func$ LANGUAGE plpgsql;

SELECT create_sequence_messages_seq();
DROP FUNCTION create_sequence_messages_seq();



--
-- Name: messages_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_messages_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='messages_ind'
        AND n.nspname=CURRENT_SCHEMA
    ) THEN
    THEN
        CREATE INDEX messages_ind ON messages USING btree (syear, school_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_messages_ind();
DROP FUNCTION create_index_messages_ind();
