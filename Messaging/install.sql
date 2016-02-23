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

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
1, 'Messaging/Messages.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
1, 'Messaging/Write.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
2, 'Messaging/Messages.php', 'Y', null);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
2, 'Messaging/Write.php', 'Y', null);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
3, 'Messaging/Messages.php', 'Y', null);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
3, 'Messaging/Write.php', 'Y', null);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
0, 'Messaging/Messages.php', 'Y', null);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
0, 'Messaging/Write.php', 'Y', null);



/**
 * Add module tables
 */

/**
 * Student cross message table
 */
--
-- Name: studentxmessage; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE studentxmessage (
    student_id numeric NOT NULL,
    message_id numeric NOT NULL,
    status character varying(10)
);


/**
 * User cross message table
 */
--
-- Name: userxmessage; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE userxmessage (
    user_id numeric NOT NULL,
    message_id numeric NOT NULL,
    status character varying(10)
);


/**
 * Messages table
 */
--
-- Name: messages; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE messages (
    message_id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric NOT NULL,
    recipients text,
    subject character varying(100),
    "datetime" timestamp(0) without time zone,
    data text
);


--
-- Name: messages_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE messages_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;



--
-- Name: messages_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('messages_seq', 1, false);


--
-- Name: messages_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY messages
    ADD CONSTRAINT messages_pkey PRIMARY KEY (message_id);



--
-- Name: attendance_code_categories_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX messages_ind ON message USING btree (syear, school_id);
