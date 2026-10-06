--
-- PostgreSQL database dump
--

\restrict ey81ilz764kPborJNoyd2zChlhP47QMJtlWdcDgsM6NxvoPEWlig0aYCDIgIGjI

-- Dumped from database version 14.23 (Ubuntu 14.23-0ubuntu0.22.04.1)
-- Dumped by pg_dump version 18.0 (Ubuntu 18.0-1.pgdg22.04+3)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: postgres
--

-- *not* creating schema, since initdb creates it


ALTER SCHEMA public OWNER TO postgres;

--
-- Name: fn_recalculate_held_seats(bigint, bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_recalculate_held_seats(p_voyage_id bigint, p_class_id bigint) RETURNS void
    LANGUAGE plpgsql
    AS $$
                DECLARE
                    v_held_individual INT;
                    v_held_whole_room INT;
                BEGIN
                    -- Individual locks
                    SELECT COALESCE(SUM(quantity), 0) INTO v_held_individual
                    FROM booking_locks
                    WHERE voyage_id = p_voyage_id 
                      AND ferry_class_id = p_class_id
                      AND (is_whole_room = FALSE OR is_whole_room IS NULL)
                      AND expires_at > NOW();

                    -- Whole room locks
                    SELECT COALESCE(SUM(r.capacity), 0) INTO v_held_whole_room
                    FROM (
                        SELECT DISTINCT room_id
                        FROM booking_locks
                        WHERE voyage_id = p_voyage_id 
                          AND ferry_class_id = p_class_id
                          AND is_whole_room = TRUE
                          AND expires_at > NOW()
                    ) bl
                    JOIN ferry_class_rooms r ON bl.room_id = r.id;

                    UPDATE seat_inventories
                    SET held_seats = v_held_individual + v_held_whole_room
                    WHERE voyage_id = p_voyage_id AND travel_class_id = p_class_id;
                END;
                $$;


ALTER FUNCTION public.fn_recalculate_held_seats(p_voyage_id bigint, p_class_id bigint) OWNER TO postgres;

--
-- Name: fn_recalculate_sold_seats(bigint, bigint); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_recalculate_sold_seats(p_voyage_id bigint, p_class_id bigint) RETURNS void
    LANGUAGE plpgsql
    AS $$
                DECLARE
                    v_sold_individual INT;
                    v_sold_whole_room INT;
                BEGIN
                    -- Individual tickets (not whole room)
                    SELECT COALESCE(COUNT(*), 0) INTO v_sold_individual
                    FROM tickets bi
                    WHERE bi.voyage_id = p_voyage_id 
                      AND bi.ferry_class_id = p_class_id
                      AND (bi.is_whole_room = FALSE OR bi.is_whole_room IS NULL)
                      AND bi.status IN ('confirmed', 'held', 'checked_in', 'boarded')
                      AND (
                          bi.final_fare > 0 OR 
                          EXISTS (
                              SELECT 1 FROM passenger_types pt 
                              WHERE pt.id = bi.passenger_type_id 
                                AND pt.code != 'INFANT' 
                                AND pt.name != 'Infant'
                          )
                      );

                    -- Whole room tickets (count room capacity once per room)
                    SELECT COALESCE(SUM(r.capacity), 0) INTO v_sold_whole_room
                    FROM (
                        SELECT DISTINCT room_id
                        FROM tickets
                        WHERE voyage_id = p_voyage_id 
                          AND ferry_class_id = p_class_id
                          AND is_whole_room = TRUE
                          AND status IN ('confirmed', 'held', 'checked_in', 'boarded')
                    ) bi
                    JOIN ferry_class_rooms r ON bi.room_id = r.id;

                    UPDATE seat_inventories
                    SET sold_seats = v_sold_individual + v_sold_whole_room
                    WHERE voyage_id = p_voyage_id AND travel_class_id = p_class_id;
                END;
                $$;


ALTER FUNCTION public.fn_recalculate_sold_seats(p_voyage_id bigint, p_class_id bigint) OWNER TO postgres;

--
-- Name: fn_tr_booking_items_after_delete(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_tr_booking_items_after_delete() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
                BEGIN
                    IF OLD.status IN ('confirmed', 'held', 'checked_in', 'boarded') THEN
                        UPDATE seat_inventories
                        SET sold_seats = GREATEST(0, sold_seats - 1)
                        WHERE voyage_id = OLD.voyage_id
                          AND travel_class_id = OLD.ferry_class_id;
                    END IF;
                    RETURN OLD;
                END;
                $$;


ALTER FUNCTION public.fn_tr_booking_items_after_delete() OWNER TO postgres;

--
-- Name: fn_tr_booking_items_after_insert(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_tr_booking_items_after_insert() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
                BEGIN
                    IF NEW.status IN ('confirmed', 'held', 'checked_in', 'boarded') THEN
                        UPDATE seat_inventories SET sold_seats = sold_seats + 1 WHERE voyage_id = NEW.voyage_id AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                    RETURN NEW;
                END;
                $$;


ALTER FUNCTION public.fn_tr_booking_items_after_insert() OWNER TO postgres;

--
-- Name: fn_tr_booking_items_after_update(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_tr_booking_items_after_update() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
                DECLARE
                    was_sold BOOLEAN;
                    is_sold BOOLEAN;
                BEGIN
                    was_sold := (OLD.status IN ('confirmed', 'held', 'checked_in', 'boarded'));
                    is_sold := (NEW.status IN ('confirmed', 'held', 'checked_in', 'boarded'));

                    IF is_sold = TRUE AND was_sold = FALSE THEN
                        UPDATE seat_inventories
                        SET sold_seats = sold_seats + 1
                        WHERE voyage_id = NEW.voyage_id
                          AND travel_class_id = NEW.ferry_class_id;
                    ELSIF is_sold = FALSE AND was_sold = TRUE THEN
                        UPDATE seat_inventories
                        SET sold_seats = GREATEST(0, sold_seats - 1)
                        WHERE voyage_id = NEW.voyage_id
                          AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                    RETURN NEW;
                END;
                $$;


ALTER FUNCTION public.fn_tr_booking_items_after_update() OWNER TO postgres;

--
-- Name: fn_tr_booking_locks_after_change(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_tr_booking_locks_after_change() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
                BEGIN
                    IF TG_OP = 'UPDATE' THEN
                        IF OLD.voyage_id != NEW.voyage_id OR OLD.ferry_class_id != NEW.ferry_class_id THEN
                            PERFORM fn_recalculate_held_seats(OLD.voyage_id, OLD.ferry_class_id);
                        END IF;
                        PERFORM fn_recalculate_held_seats(NEW.voyage_id, NEW.ferry_class_id);
                        RETURN NEW;
                    ELSIF TG_OP = 'DELETE' THEN
                        PERFORM fn_recalculate_held_seats(OLD.voyage_id, OLD.ferry_class_id);
                        RETURN OLD;
                    ELSE
                        PERFORM fn_recalculate_held_seats(NEW.voyage_id, NEW.ferry_class_id);
                        RETURN NEW;
                    END IF;
                END;
                $$;


ALTER FUNCTION public.fn_tr_booking_locks_after_change() OWNER TO postgres;

--
-- Name: fn_tr_booking_locks_after_delete(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_tr_booking_locks_after_delete() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
                BEGIN
                    UPDATE seat_inventories
                    SET held_seats = GREATEST(0, held_seats - OLD.quantity)
                    WHERE voyage_id = OLD.voyage_id
                      AND travel_class_id = OLD.ferry_class_id;
                    RETURN OLD;
                END;
                $$;


ALTER FUNCTION public.fn_tr_booking_locks_after_delete() OWNER TO postgres;

--
-- Name: fn_tr_booking_locks_after_insert(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_tr_booking_locks_after_insert() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
                BEGIN
                    UPDATE seat_inventories SET held_seats = held_seats + NEW.quantity WHERE voyage_id = NEW.voyage_id AND travel_class_id = NEW.ferry_class_id;
                    RETURN NEW;
                END;
                $$;


ALTER FUNCTION public.fn_tr_booking_locks_after_insert() OWNER TO postgres;

--
-- Name: fn_tr_booking_locks_after_update(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_tr_booking_locks_after_update() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
                BEGIN
                    IF NEW.quantity != OLD.quantity THEN
                        UPDATE seat_inventories
                        SET held_seats = GREATEST(0, held_seats - OLD.quantity + NEW.quantity)
                        WHERE voyage_id = NEW.voyage_id
                          AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                    RETURN NEW;
                END;
                $$;


ALTER FUNCTION public.fn_tr_booking_locks_after_update() OWNER TO postgres;

--
-- Name: fn_tr_tickets_after_change(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_tr_tickets_after_change() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
                BEGIN
                    IF TG_OP = 'UPDATE' THEN
                        IF OLD.voyage_id != NEW.voyage_id OR OLD.ferry_class_id != NEW.ferry_class_id THEN
                            PERFORM fn_recalculate_sold_seats(OLD.voyage_id, OLD.ferry_class_id);
                        END IF;
                        PERFORM fn_recalculate_sold_seats(NEW.voyage_id, NEW.ferry_class_id);
                        RETURN NEW;
                    ELSIF TG_OP = 'DELETE' THEN
                        PERFORM fn_recalculate_sold_seats(OLD.voyage_id, OLD.ferry_class_id);
                        RETURN OLD;
                    ELSE
                        PERFORM fn_recalculate_sold_seats(NEW.voyage_id, NEW.ferry_class_id);
                        RETURN NEW;
                    END IF;
                END;
                $$;


ALTER FUNCTION public.fn_tr_tickets_after_change() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: access_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.access_logs (
    id bigint NOT NULL,
    user_id bigint,
    event_type character varying(50) NOT NULL,
    ip_address character varying(45),
    user_agent text,
    session_id character varying(255),
    metadata json,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.access_logs OWNER TO postgres;

--
-- Name: access_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.access_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.access_logs_id_seq OWNER TO postgres;

--
-- Name: access_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.access_logs_id_seq OWNED BY public.access_logs.id;


--
-- Name: agent_accounts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.agent_accounts (
    id bigint NOT NULL,
    uuid character(36) NOT NULL,
    agent_id bigint NOT NULL,
    account_number character varying(50) NOT NULL,
    currency character varying(3) DEFAULT 'TZS'::character varying NOT NULL,
    balance numeric(18,4) DEFAULT '0'::numeric NOT NULL,
    security_deposit_balance numeric(18,4) DEFAULT '0'::numeric NOT NULL,
    minimum_balance numeric(18,4) DEFAULT '0'::numeric NOT NULL,
    maximum_balance numeric(18,4),
    credit_limit numeric(18,4) DEFAULT '0'::numeric,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.agent_accounts OWNER TO postgres;

--
-- Name: agent_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.agent_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.agent_accounts_id_seq OWNER TO postgres;

--
-- Name: agent_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.agent_accounts_id_seq OWNED BY public.agent_accounts.id;


--
-- Name: agent_ferries; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.agent_ferries (
    id bigint NOT NULL,
    agent_id bigint NOT NULL,
    ferry_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL
);


ALTER TABLE public.agent_ferries OWNER TO postgres;

--
-- Name: agent_ferries_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.agent_ferries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.agent_ferries_id_seq OWNER TO postgres;

--
-- Name: agent_ferries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.agent_ferries_id_seq OWNED BY public.agent_ferries.id;


--
-- Name: agent_routes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.agent_routes (
    id bigint NOT NULL,
    agent_id bigint NOT NULL,
    route_id bigint NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.agent_routes OWNER TO postgres;

--
-- Name: agent_routes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.agent_routes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.agent_routes_id_seq OWNER TO postgres;

--
-- Name: agent_routes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.agent_routes_id_seq OWNED BY public.agent_routes.id;


--
-- Name: agents; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.agents (
    id bigint NOT NULL,
    uuid character(36) NOT NULL,
    user_id bigint NOT NULL,
    agency_name character varying(150) NOT NULL,
    agent_code character varying(50) NOT NULL,
    agent_type character varying(30) DEFAULT 'direct'::character varying NOT NULL,
    commission_rate numeric(5,2),
    commission_method character varying(20),
    contact_mobile character varying(50),
    email character varying(100),
    address text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    cancellation_refund_mode character varying(255),
    can_book_foreigners boolean DEFAULT false NOT NULL,
    CONSTRAINT agents_cancellation_refund_mode_check CHECK (((cancellation_refund_mode)::text = ANY ((ARRAY['immediate'::character varying, 'hold_until_submission'::character varying])::text[])))
);


ALTER TABLE public.agents OWNER TO postgres;

--
-- Name: agents_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.agents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.agents_id_seq OWNER TO postgres;

--
-- Name: agents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.agents_id_seq OWNED BY public.agents.id;


--
-- Name: announcements; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.announcements (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    message text NOT NULL,
    type character varying(30) DEFAULT 'info'::character varying NOT NULL,
    target_audience character varying(30) DEFAULT 'all'::character varying NOT NULL,
    route_id bigint,
    start_date date NOT NULL,
    end_date date,
    is_active boolean DEFAULT true NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.announcements OWNER TO postgres;

--
-- Name: announcements_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.announcements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.announcements_id_seq OWNER TO postgres;

--
-- Name: announcements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.announcements_id_seq OWNED BY public.announcements.id;


--
-- Name: associate_agents; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.associate_agents (
    id bigint NOT NULL,
    uuid character(36) NOT NULL,
    user_id bigint NOT NULL,
    agent_id bigint NOT NULL,
    associate_name character varying(150) NOT NULL,
    contact_mobile character varying(50),
    email character varying(100),
    daily_credit_limit numeric(18,4) DEFAULT '0'::numeric,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.associate_agents OWNER TO postgres;

--
-- Name: associate_agents_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.associate_agents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.associate_agents_id_seq OWNER TO postgres;

--
-- Name: associate_agents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.associate_agents_id_seq OWNED BY public.associate_agents.id;


--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    user_id bigint,
    action character varying(50) NOT NULL,
    entity_type character varying(100) NOT NULL,
    entity_id character varying(50),
    before_state json,
    after_state json,
    ip_address character varying(45),
    hash character varying(64) NOT NULL,
    previous_hash character varying(64),
    sequence_number bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.audit_logs OWNER TO postgres;

--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.audit_logs_id_seq OWNER TO postgres;

--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: boarding_events; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.boarding_events (
    id bigint NOT NULL,
    ticket_id bigint,
    voyage_id bigint NOT NULL,
    scanner_id bigint,
    scanned_by bigint,
    port_id bigint,
    scan_type character varying(20) DEFAULT 'boarding'::character varying NOT NULL,
    qr_data character varying(500),
    result character varying(20) NOT NULL,
    notes text,
    scanned_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.boarding_events OWNER TO postgres;

--
-- Name: boarding_events_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.boarding_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.boarding_events_id_seq OWNER TO postgres;

--
-- Name: boarding_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.boarding_events_id_seq OWNED BY public.boarding_events.id;


--
-- Name: tickets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tickets (
    id bigint NOT NULL,
    booking_id bigint NOT NULL,
    ticket_number character varying(60) NOT NULL,
    voyage_id bigint NOT NULL,
    ferry_class_id bigint NOT NULL,
    passenger_id bigint,
    seat_number character varying(20),
    base_fare numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    discount_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    final_fare numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(30) DEFAULT 'held'::character varying NOT NULL,
    qr_code_token character varying(100) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    print_count integer DEFAULT 0 NOT NULL,
    last_printed_at timestamp(0) without time zone,
    cancellation_fee_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    edit_count integer DEFAULT 0 NOT NULL,
    leg_type character varying(20) DEFAULT 'outbound'::character varying NOT NULL,
    currency character varying(10) DEFAULT 'TZS'::character varying NOT NULL,
    nationality_type smallint DEFAULT '1'::smallint NOT NULL,
    passenger_name character varying(255),
    gender character varying(20),
    nationality_id bigint,
    country_id bigint,
    identity_type_id bigint,
    identity_number character varying(255),
    currency_code character varying(10) DEFAULT 'TZS'::character varying NOT NULL,
    exchange_rate numeric(12,6) DEFAULT '1'::numeric NOT NULL,
    is_complimentary boolean DEFAULT false NOT NULL,
    complimentary_reason character varying(255),
    status_id bigint,
    checked_in_at timestamp(0) without time zone,
    boarded_at timestamp(0) without time zone,
    cancelled_at timestamp(0) without time zone,
    cancelled_by_type character varying(255),
    cancelled_by_id bigint,
    qr_code_data text,
    passenger_type_id bigint,
    corporate_work_id character varying(100),
    room_id bigint,
    is_whole_room boolean DEFAULT false NOT NULL
);


ALTER TABLE public.tickets OWNER TO postgres;

--
-- Name: COLUMN tickets.leg_type; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.tickets.leg_type IS 'outbound or inbound';


--
-- Name: COLUMN tickets.currency; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.tickets.currency IS 'TZS or USD';


--
-- Name: COLUMN tickets.nationality_type; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.tickets.nationality_type IS '1 = Resident, 2 = Foreigner';


--
-- Name: booking_items_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.booking_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.booking_items_id_seq OWNER TO postgres;

--
-- Name: booking_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.booking_items_id_seq OWNED BY public.tickets.id;


--
-- Name: booking_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.booking_locks (
    id bigint NOT NULL,
    voyage_id bigint NOT NULL,
    ferry_class_id bigint NOT NULL,
    seat_number character varying(50),
    quantity integer DEFAULT 1 NOT NULL,
    user_id bigint,
    session_id character varying(100),
    expires_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    agent_account_id bigint,
    amount_held numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    room_id bigint,
    is_whole_room boolean DEFAULT false NOT NULL
);


ALTER TABLE public.booking_locks OWNER TO postgres;

--
-- Name: COLUMN booking_locks.seat_number; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.booking_locks.seat_number IS 'Specific seat like 14A if has_layout=1, or null if open pool';


--
-- Name: COLUMN booking_locks.quantity; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.booking_locks.quantity IS 'Number of seats locked by this hold';


--
-- Name: COLUMN booking_locks.user_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.booking_locks.user_id IS 'Maker user who created hold';


--
-- Name: COLUMN booking_locks.session_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.booking_locks.session_id IS 'Browser session ID or counter terminal ID';


--
-- Name: COLUMN booking_locks.expires_at; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.booking_locks.expires_at IS 'When this lock expires (e.g., now + 5 mins)';


--
-- Name: booking_locks_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.booking_locks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.booking_locks_id_seq OWNER TO postgres;

--
-- Name: booking_locks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.booking_locks_id_seq OWNED BY public.booking_locks.id;


--
-- Name: booking_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.booking_logs (
    id bigint NOT NULL,
    booking_id bigint NOT NULL,
    ticket_id bigint,
    action character varying(255) NOT NULL,
    description text,
    performed_by_user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.booking_logs OWNER TO postgres;

--
-- Name: booking_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.booking_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.booking_logs_id_seq OWNER TO postgres;

--
-- Name: booking_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.booking_logs_id_seq OWNED BY public.booking_logs.id;


--
-- Name: booking_sources; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.booking_sources (
    id bigint NOT NULL,
    slug character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.booking_sources OWNER TO postgres;

--
-- Name: booking_sources_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.booking_sources_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.booking_sources_id_seq OWNER TO postgres;

--
-- Name: booking_sources_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.booking_sources_id_seq OWNED BY public.booking_sources.id;


--
-- Name: booking_statuses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.booking_statuses (
    id bigint NOT NULL,
    slug character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    color character varying(50) DEFAULT 'slate'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.booking_statuses OWNER TO postgres;

--
-- Name: booking_statuses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.booking_statuses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.booking_statuses_id_seq OWNER TO postgres;

--
-- Name: booking_statuses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.booking_statuses_id_seq OWNED BY public.booking_statuses.id;


--
-- Name: bookings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bookings (
    id bigint NOT NULL,
    booking_reference character varying(50) NOT NULL,
    user_id bigint,
    booked_by_user_id bigint,
    booking_source character varying(30) DEFAULT 'web'::character varying NOT NULL,
    status character varying(30) DEFAULT 'held'::character varying NOT NULL,
    total_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    discount_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    net_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    currency character varying(10) DEFAULT 'TZS'::character varying NOT NULL,
    expires_at timestamp(0) without time zone,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    payment_channel character varying(255),
    transaction_reference character varying(255),
    completed_by_user_id bigint,
    edit_count integer DEFAULT 0 NOT NULL,
    trip_type character varying(20) DEFAULT 'one_way'::character varying NOT NULL,
    total_amount_tzs numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    total_amount_usd numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    exchange_rate numeric(15,6) DEFAULT '1'::numeric NOT NULL,
    route_id bigint,
    vessel_id bigint,
    departure_date date,
    departure_time time(0) without time zone,
    contact_name character varying(255),
    contact_phone character varying(30),
    contact_email character varying(255),
    passenger_count integer DEFAULT 1 NOT NULL,
    status_id bigint,
    payment_status_id bigint,
    source_id bigint,
    booking_agent_id bigint,
    completed_at timestamp(0) without time zone,
    cancelled_at timestamp(0) without time zone,
    cancelled_by bigint,
    cancellation_reason text,
    is_legacy boolean DEFAULT false NOT NULL,
    corporate_id bigint,
    payment_status character varying(30) DEFAULT 'pending'::character varying NOT NULL
);


ALTER TABLE public.bookings OWNER TO postgres;

--
-- Name: COLUMN bookings.trip_type; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.bookings.trip_type IS 'one_way or round_trip';


--
-- Name: COLUMN bookings.total_amount_tzs; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.bookings.total_amount_tzs IS 'Exact TZS collected/subtotal';


--
-- Name: COLUMN bookings.total_amount_usd; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.bookings.total_amount_usd IS 'Exact USD collected/subtotal';


--
-- Name: bookings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.bookings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.bookings_id_seq OWNER TO postgres;

--
-- Name: bookings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.bookings_id_seq OWNED BY public.bookings.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: complimentary_tickets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.complimentary_tickets (
    id bigint NOT NULL,
    booking_id bigint NOT NULL,
    ticket_id bigint,
    approved_by bigint NOT NULL,
    reason character varying(255) NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.complimentary_tickets OWNER TO postgres;

--
-- Name: complimentary_tickets_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.complimentary_tickets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.complimentary_tickets_id_seq OWNER TO postgres;

--
-- Name: complimentary_tickets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.complimentary_tickets_id_seq OWNED BY public.complimentary_tickets.id;


--
-- Name: corporate_invoices; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.corporate_invoices (
    id bigint NOT NULL,
    corporate_id bigint NOT NULL,
    invoice_number character varying(50) NOT NULL,
    period_start date NOT NULL,
    period_end date NOT NULL,
    total_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(20) DEFAULT 'draft'::character varying NOT NULL,
    due_date date NOT NULL,
    paid_at timestamp(0) without time zone,
    paid_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.corporate_invoices OWNER TO postgres;

--
-- Name: corporate_invoices_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.corporate_invoices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.corporate_invoices_id_seq OWNER TO postgres;

--
-- Name: corporate_invoices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.corporate_invoices_id_seq OWNED BY public.corporate_invoices.id;


--
-- Name: corporates; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.corporates (
    id bigint NOT NULL,
    name character varying(200) NOT NULL,
    registration_number character varying(100),
    contact_person character varying(150),
    email character varying(150),
    phone character varying(30),
    address text,
    payment_terms_days integer DEFAULT 30 NOT NULL,
    credit_limit numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    current_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.corporates OWNER TO postgres;

--
-- Name: corporates_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.corporates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.corporates_id_seq OWNER TO postgres;

--
-- Name: corporates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.corporates_id_seq OWNED BY public.corporates.id;


--
-- Name: countries; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.countries (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    code character varying(3) NOT NULL,
    phone_code character varying(10),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.countries OWNER TO postgres;

--
-- Name: countries_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.countries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.countries_id_seq OWNER TO postgres;

--
-- Name: countries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.countries_id_seq OWNED BY public.countries.id;


--
-- Name: coupon_usages; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.coupon_usages (
    id bigint NOT NULL,
    coupon_id bigint NOT NULL,
    booking_id bigint NOT NULL,
    user_id bigint,
    discount_applied numeric(10,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.coupon_usages OWNER TO postgres;

--
-- Name: coupon_usages_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.coupon_usages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.coupon_usages_id_seq OWNER TO postgres;

--
-- Name: coupon_usages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.coupon_usages_id_seq OWNED BY public.coupon_usages.id;


--
-- Name: coupons; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.coupons (
    id bigint NOT NULL,
    code character varying(50) NOT NULL,
    promotion_id bigint,
    discount_type character varying(30),
    discount_value numeric(10,2),
    max_discount numeric(10,2),
    max_uses integer DEFAULT 1 NOT NULL,
    current_uses integer DEFAULT 0 NOT NULL,
    valid_from date,
    valid_to date,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    target_audience character varying(255) DEFAULT 'all'::character varying NOT NULL,
    discount_value_usd numeric(15,2),
    CONSTRAINT coupons_target_audience_check CHECK (((target_audience)::text = ANY ((ARRAY['all'::character varying, 'residents'::character varying, 'foreigners'::character varying])::text[])))
);


ALTER TABLE public.coupons OWNER TO postgres;

--
-- Name: coupons_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.coupons_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.coupons_id_seq OWNER TO postgres;

--
-- Name: coupons_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.coupons_id_seq OWNED BY public.coupons.id;


--
-- Name: currencies; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.currencies (
    id bigint NOT NULL,
    code character varying(3) NOT NULL,
    name character varying(100) NOT NULL,
    symbol character varying(10),
    is_active boolean DEFAULT true NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.currencies OWNER TO postgres;

--
-- Name: currencies_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.currencies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.currencies_id_seq OWNER TO postgres;

--
-- Name: currencies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.currencies_id_seq OWNED BY public.currencies.id;


--
-- Name: customer_statuses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.customer_statuses (
    id bigint NOT NULL,
    code character varying(50) NOT NULL,
    label character varying(100) NOT NULL,
    color_badge character varying(50) DEFAULT 'slate'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.customer_statuses OWNER TO postgres;

--
-- Name: customer_statuses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.customer_statuses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.customer_statuses_id_seq OWNER TO postgres;

--
-- Name: customer_statuses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.customer_statuses_id_seq OWNED BY public.customer_statuses.id;


--
-- Name: dual_control_configs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.dual_control_configs (
    id bigint NOT NULL,
    operation_type character varying(100) NOT NULL,
    description character varying(255),
    requires_dual_control boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    module character varying(255) DEFAULT 'General'::character varying NOT NULL
);


ALTER TABLE public.dual_control_configs OWNER TO postgres;

--
-- Name: dual_control_configs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.dual_control_configs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.dual_control_configs_id_seq OWNER TO postgres;

--
-- Name: dual_control_configs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.dual_control_configs_id_seq OWNED BY public.dual_control_configs.id;


--
-- Name: dual_control_requests; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.dual_control_requests (
    id bigint NOT NULL,
    maker_id bigint NOT NULL,
    checker_id bigint,
    operation_type character varying(50) NOT NULL,
    resource_type character varying(50) NOT NULL,
    resource_id bigint NOT NULL,
    payload_before json,
    payload_after json,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    checker_comment text,
    expires_at timestamp(0) without time zone,
    resolved_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT dual_control_requests_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying, 'expired'::character varying, 'cancelled'::character varying])::text[])))
);


ALTER TABLE public.dual_control_requests OWNER TO postgres;

--
-- Name: dual_control_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.dual_control_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.dual_control_requests_id_seq OWNER TO postgres;

--
-- Name: dual_control_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.dual_control_requests_id_seq OWNED BY public.dual_control_requests.id;


--
-- Name: exchange_rates; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.exchange_rates (
    id bigint NOT NULL,
    from_currency character varying(3) NOT NULL,
    to_currency character varying(3) NOT NULL,
    rate numeric(12,6) NOT NULL,
    effective_from date NOT NULL,
    effective_to date,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.exchange_rates OWNER TO postgres;

--
-- Name: exchange_rates_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.exchange_rates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.exchange_rates_id_seq OWNER TO postgres;

--
-- Name: exchange_rates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.exchange_rates_id_seq OWNED BY public.exchange_rates.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: fares; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.fares (
    id bigint NOT NULL,
    route_id bigint NOT NULL,
    ferry_id bigint,
    ferry_class_id bigint,
    adult_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    child_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    adult_price_usd numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    child_price_usd numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    room_id bigint,
    whole_room_price numeric(10,2),
    whole_room_price_usd numeric(10,2)
);


ALTER TABLE public.fares OWNER TO postgres;

--
-- Name: fares_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.fares_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.fares_id_seq OWNER TO postgres;

--
-- Name: fares_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.fares_id_seq OWNED BY public.fares.id;


--
-- Name: ferries; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ferries (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    type character varying(50),
    port_of_registry character varying(100),
    capacity integer DEFAULT 0 NOT NULL,
    has_layout boolean DEFAULT false NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    layout_image_path character varying(255),
    seat_layout_json json,
    CONSTRAINT ferries_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'maintenance'::character varying, 'inactive'::character varying])::text[])))
);


ALTER TABLE public.ferries OWNER TO postgres;

--
-- Name: ferries_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ferries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ferries_id_seq OWNER TO postgres;

--
-- Name: ferries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ferries_id_seq OWNED BY public.ferries.id;


--
-- Name: ferry_certificates; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ferry_certificates (
    id bigint NOT NULL,
    ferry_id bigint NOT NULL,
    certificate_number character varying(100) NOT NULL,
    certificate_type character varying(100) NOT NULL,
    issued_by character varying(100),
    issued_date date NOT NULL,
    expiry_date date NOT NULL,
    max_passengers integer,
    max_crew integer,
    status character varying(30) DEFAULT 'valid'::character varying NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.ferry_certificates OWNER TO postgres;

--
-- Name: ferry_class_rooms; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ferry_class_rooms (
    id bigint NOT NULL,
    ferry_class_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    capacity integer NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.ferry_class_rooms OWNER TO postgres;

--
-- Name: ferry_class_rooms_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ferry_class_rooms_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ferry_class_rooms_id_seq OWNER TO postgres;

--
-- Name: ferry_class_rooms_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ferry_class_rooms_id_seq OWNED BY public.ferry_class_rooms.id;


--
-- Name: ferry_classes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ferry_classes (
    id bigint NOT NULL,
    ferry_id bigint NOT NULL,
    no_seats integer NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    class_id bigint,
    is_room_based boolean DEFAULT false NOT NULL,
    CONSTRAINT ferry_classes_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[])))
);


ALTER TABLE public.ferry_classes OWNER TO postgres;

--
-- Name: ferry_classes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ferry_classes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ferry_classes_id_seq OWNER TO postgres;

--
-- Name: ferry_classes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ferry_classes_id_seq OWNED BY public.ferry_classes.id;


--
-- Name: id_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.id_types (
    id bigint NOT NULL,
    code character varying(50) NOT NULL,
    label character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.id_types OWNER TO postgres;

--
-- Name: id_types_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.id_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.id_types_id_seq OWNER TO postgres;

--
-- Name: id_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.id_types_id_seq OWNED BY public.id_types.id;


--
-- Name: identity_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.identity_types (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    slug character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.identity_types OWNER TO postgres;

--
-- Name: identity_types_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.identity_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.identity_types_id_seq OWNER TO postgres;

--
-- Name: identity_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.identity_types_id_seq OWNED BY public.identity_types.id;


--
-- Name: integration_configs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.integration_configs (
    id uuid NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    provider character varying(100),
    channel character varying(30) NOT NULL,
    description character varying(500),
    environment character varying(255) DEFAULT 'sandbox'::character varying NOT NULL,
    base_url character varying(500),
    sandbox_url character varying(500),
    production_url character varying(500),
    api_key text,
    api_secret text,
    access_token text,
    merchant_id text,
    passkey text,
    extra_credentials text,
    callback_url character varying(500),
    timeout_url character varying(500),
    result_url character varying(500),
    is_active boolean DEFAULT false NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    currency character varying(5),
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    icon character varying(100),
    country_code character varying(5),
    metadata json,
    updated_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT integration_configs_environment_check CHECK (((environment)::text = ANY ((ARRAY['sandbox'::character varying, 'production'::character varying])::text[])))
);


ALTER TABLE public.integration_configs OWNER TO postgres;

--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: languages; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.languages (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    is_active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.languages OWNER TO postgres;

--
-- Name: languages_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.languages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.languages_id_seq OWNER TO postgres;

--
-- Name: languages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.languages_id_seq OWNED BY public.languages.id;


--
-- Name: locations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.locations (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    code character varying(20) NOT NULL,
    country_id bigint,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.locations OWNER TO postgres;

--
-- Name: locations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.locations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.locations_id_seq OWNER TO postgres;

--
-- Name: locations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.locations_id_seq OWNED BY public.locations.id;


--
-- Name: menus; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.menus (
    id bigint NOT NULL,
    title character varying(100) NOT NULL,
    route character varying(100),
    icon character varying(50),
    parent_id bigint,
    permission_name character varying(100),
    section character varying(50),
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.menus OWNER TO postgres;

--
-- Name: menus_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.menus_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.menus_id_seq OWNER TO postgres;

--
-- Name: menus_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.menus_id_seq OWNED BY public.menus.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: nationalities; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.nationalities (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    code character varying(3) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.nationalities OWNER TO postgres;

--
-- Name: nationalities_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.nationalities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.nationalities_id_seq OWNER TO postgres;

--
-- Name: nationalities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.nationalities_id_seq OWNED BY public.nationalities.id;


--
-- Name: notification_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notification_logs (
    id bigint NOT NULL,
    user_id bigint,
    channel character varying(20) NOT NULL,
    event character varying(100) NOT NULL,
    recipient character varying(255) NOT NULL,
    content text,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    provider character varying(50),
    provider_message_id character varying(100),
    error_message text,
    cost numeric(8,4),
    sent_at timestamp(0) without time zone,
    delivered_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.notification_logs OWNER TO postgres;

--
-- Name: notification_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.notification_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notification_logs_id_seq OWNER TO postgres;

--
-- Name: notification_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.notification_logs_id_seq OWNED BY public.notification_logs.id;


--
-- Name: notification_templates; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notification_templates (
    id bigint NOT NULL,
    event character varying(100) NOT NULL,
    channel character varying(20) NOT NULL,
    locale character varying(5) DEFAULT 'en'::character varying NOT NULL,
    subject character varying(255),
    body text NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.notification_templates OWNER TO postgres;

--
-- Name: notification_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.notification_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notification_templates_id_seq OWNER TO postgres;

--
-- Name: notification_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.notification_templates_id_seq OWNED BY public.notification_templates.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notifications (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    type character varying(50) NOT NULL,
    channel character varying(20) DEFAULT 'in_app'::character varying NOT NULL,
    title character varying(255) NOT NULL,
    body text,
    data json,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.notifications OWNER TO postgres;

--
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notifications_id_seq OWNER TO postgres;

--
-- Name: notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.notifications_id_seq OWNED BY public.notifications.id;


--
-- Name: passenger_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.passenger_types (
    id bigint NOT NULL,
    name character varying(50) NOT NULL,
    code character varying(20) NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    min_age integer,
    max_age integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.passenger_types OWNER TO postgres;

--
-- Name: passenger_types_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.passenger_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.passenger_types_id_seq OWNER TO postgres;

--
-- Name: passenger_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.passenger_types_id_seq OWNED BY public.passenger_types.id;


--
-- Name: passengers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.passengers (
    id bigint NOT NULL,
    user_id bigint,
    first_name character varying(100) NOT NULL,
    last_name character varying(100) NOT NULL,
    id_type_id bigint,
    id_number character varying(100),
    dob date,
    gender character varying(255),
    nationality character varying(100) DEFAULT 'Tanzanian'::character varying NOT NULL,
    phone character varying(30),
    email character varying(150),
    is_saved_profile boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    passenger_type_id bigint,
    CONSTRAINT passengers_gender_check CHECK (((gender)::text = ANY ((ARRAY['male'::character varying, 'female'::character varying, 'other'::character varying])::text[])))
);


ALTER TABLE public.passengers OWNER TO postgres;

--
-- Name: passengers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.passengers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.passengers_id_seq OWNER TO postgres;

--
-- Name: passengers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.passengers_id_seq OWNED BY public.passengers.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- Name: payment_gateway_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payment_gateway_logs (
    id bigint NOT NULL,
    payment_id bigint,
    booking_id bigint,
    gateway character varying(50) NOT NULL,
    event_type character varying(50) NOT NULL,
    direction character varying(10) DEFAULT 'outgoing'::character varying NOT NULL,
    http_method character varying(10),
    url text,
    request_headers json,
    request_payload json,
    response_payload json,
    http_status_code smallint,
    transaction_reference character varying(255),
    amount numeric(12,2),
    currency character varying(3),
    is_success boolean DEFAULT false NOT NULL,
    error_message text,
    response_time_ms integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payment_gateway_logs OWNER TO postgres;

--
-- Name: payment_gateway_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payment_gateway_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.payment_gateway_logs_id_seq OWNER TO postgres;

--
-- Name: payment_gateway_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payment_gateway_logs_id_seq OWNED BY public.payment_gateway_logs.id;


--
-- Name: payment_methods; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payment_methods (
    id bigint NOT NULL,
    slug character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payment_methods OWNER TO postgres;

--
-- Name: payment_methods_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payment_methods_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.payment_methods_id_seq OWNER TO postgres;

--
-- Name: payment_methods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payment_methods_id_seq OWNED BY public.payment_methods.id;


--
-- Name: payment_statuses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payment_statuses (
    id bigint NOT NULL,
    slug character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    color character varying(50) DEFAULT 'slate'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payment_statuses OWNER TO postgres;

--
-- Name: payment_statuses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payment_statuses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.payment_statuses_id_seq OWNER TO postgres;

--
-- Name: payment_statuses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payment_statuses_id_seq OWNED BY public.payment_statuses.id;


--
-- Name: payment_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payment_types (
    id bigint NOT NULL,
    slug character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payment_types OWNER TO postgres;

--
-- Name: payment_types_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payment_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.payment_types_id_seq OWNER TO postgres;

--
-- Name: payment_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payment_types_id_seq OWNED BY public.payment_types.id;


--
-- Name: payments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payments (
    id bigint NOT NULL,
    booking_id bigint NOT NULL,
    payment_method_id bigint,
    transaction_reference character varying(255),
    amount numeric(12,2) NOT NULL,
    currency character varying(3) DEFAULT 'TZS'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    gateway_response json,
    paid_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payments OWNER TO postgres;

--
-- Name: payments_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.payments_id_seq OWNER TO postgres;

--
-- Name: payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payments_id_seq OWNED BY public.payments.id;


--
-- Name: pending_refunds; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pending_refunds (
    id bigint NOT NULL,
    booking_id bigint NOT NULL,
    ticket_id bigint,
    agent_account_id bigint NOT NULL,
    amount numeric(15,4) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    submitted_at timestamp(0) without time zone,
    released_at timestamp(0) without time zone,
    released_by bigint,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.pending_refunds OWNER TO postgres;

--
-- Name: pending_refunds_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pending_refunds_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pending_refunds_id_seq OWNER TO postgres;

--
-- Name: pending_refunds_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pending_refunds_id_seq OWNED BY public.pending_refunds.id;


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    display_name character varying(150) NOT NULL,
    module character varying(50) NOT NULL,
    description character varying(255),
    is_system boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.permissions OWNER TO postgres;

--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.permissions_id_seq OWNER TO postgres;

--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.personal_access_tokens OWNER TO postgres;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.personal_access_tokens_id_seq OWNER TO postgres;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: port_operators; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.port_operators (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    port_id bigint NOT NULL,
    operator_number character varying(50) NOT NULL,
    badge_number character varying(50),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    name character varying(255),
    pin character varying(255)
);


ALTER TABLE public.port_operators OWNER TO postgres;

--
-- Name: port_operators_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.port_operators_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.port_operators_id_seq OWNER TO postgres;

--
-- Name: port_operators_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.port_operators_id_seq OWNED BY public.port_operators.id;


--
-- Name: portal_translations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.portal_translations (
    id bigint NOT NULL,
    key character varying(255) NOT NULL,
    value json NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.portal_translations OWNER TO postgres;

--
-- Name: portal_translations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.portal_translations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.portal_translations_id_seq OWNER TO postgres;

--
-- Name: portal_translations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.portal_translations_id_seq OWNED BY public.portal_translations.id;


--
-- Name: ports; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ports (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    code character varying(10) NOT NULL,
    city character varying(100),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT ports_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[])))
);


ALTER TABLE public.ports OWNER TO postgres;

--
-- Name: ports_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ports_id_seq OWNER TO postgres;

--
-- Name: ports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ports_id_seq OWNED BY public.ports.id;


--
-- Name: price_discounts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.price_discounts (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    discount_type character varying(30) NOT NULL,
    discount_value numeric(10,2) NOT NULL,
    applies_to character varying(30) DEFAULT 'all'::character varying NOT NULL,
    passenger_type_id bigint,
    travel_class_id bigint,
    route_id bigint,
    min_passengers numeric(5,0),
    valid_from date,
    valid_to date,
    is_active boolean DEFAULT true NOT NULL,
    priority integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    target_audience character varying(255) DEFAULT 'all'::character varying NOT NULL,
    discount_value_usd numeric(15,2),
    CONSTRAINT price_discounts_target_audience_check CHECK (((target_audience)::text = ANY ((ARRAY['all'::character varying, 'residents'::character varying, 'foreigners'::character varying])::text[])))
);


ALTER TABLE public.price_discounts OWNER TO postgres;

--
-- Name: price_discounts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.price_discounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.price_discounts_id_seq OWNER TO postgres;

--
-- Name: price_discounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.price_discounts_id_seq OWNED BY public.price_discounts.id;


--
-- Name: promotions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.promotions (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    discount_type character varying(30) NOT NULL,
    discount_value numeric(10,2) NOT NULL,
    max_discount numeric(10,2),
    start_date date,
    end_date date,
    budget numeric(12,2),
    budget_used numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    max_uses integer,
    current_uses integer DEFAULT 0 NOT NULL,
    route_id bigint,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    target_audience character varying(255) DEFAULT 'all'::character varying NOT NULL,
    discount_value_usd numeric(15,2),
    CONSTRAINT promotions_target_audience_check CHECK (((target_audience)::text = ANY ((ARRAY['all'::character varying, 'residents'::character varying, 'foreigners'::character varying])::text[])))
);


ALTER TABLE public.promotions OWNER TO postgres;

--
-- Name: promotions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.promotions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.promotions_id_seq OWNER TO postgres;

--
-- Name: promotions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.promotions_id_seq OWNED BY public.promotions.id;


--
-- Name: receipts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.receipts (
    id bigint NOT NULL,
    receipt_number character varying(50) NOT NULL,
    booking_id bigint NOT NULL,
    payment_id bigint,
    issued_by bigint,
    amount numeric(12,2) NOT NULL,
    currency character varying(3) DEFAULT 'TZS'::character varying NOT NULL,
    payment_method character varying(50),
    status character varying(20) DEFAULT 'issued'::character varying NOT NULL,
    issued_at timestamp(0) without time zone,
    voided_at timestamp(0) without time zone,
    print_count integer DEFAULT 0 NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.receipts OWNER TO postgres;

--
-- Name: receipts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.receipts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.receipts_id_seq OWNER TO postgres;

--
-- Name: receipts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.receipts_id_seq OWNED BY public.receipts.id;


--
-- Name: report_snapshots; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.report_snapshots (
    id bigint NOT NULL,
    snapshot_date date NOT NULL,
    report_type character varying(255) NOT NULL,
    report_data json NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.report_snapshots OWNER TO postgres;

--
-- Name: report_snapshots_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.report_snapshots_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.report_snapshots_id_seq OWNER TO postgres;

--
-- Name: report_snapshots_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.report_snapshots_id_seq OWNED BY public.report_snapshots.id;


--
-- Name: role_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.role_permissions (
    role_id bigint NOT NULL,
    permission_id bigint NOT NULL
);


ALTER TABLE public.role_permissions OWNER TO postgres;

--
-- Name: roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(50) NOT NULL,
    slug character varying(50) NOT NULL,
    display_name character varying(100) NOT NULL,
    description character varying(255),
    is_system boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.roles OWNER TO postgres;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO postgres;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: routes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.routes (
    id bigint NOT NULL,
    origin_port_id bigint NOT NULL,
    destination_port_id bigint NOT NULL,
    name character varying(150) NOT NULL,
    distance_nm numeric(8,2),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    code character varying(255),
    CONSTRAINT routes_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[])))
);


ALTER TABLE public.routes OWNER TO postgres;

--
-- Name: routes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.routes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.routes_id_seq OWNER TO postgres;

--
-- Name: routes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.routes_id_seq OWNED BY public.routes.id;


--
-- Name: scanners; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.scanners (
    id bigint NOT NULL,
    serial_number character varying(50) NOT NULL,
    imei character varying(20),
    sim_number character varying(20),
    device_model character varying(100),
    assigned_port_id bigint,
    assigned_operator_id bigint,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    last_seen_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.scanners OWNER TO postgres;

--
-- Name: scanners_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.scanners_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.scanners_id_seq OWNER TO postgres;

--
-- Name: scanners_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.scanners_id_seq OWNED BY public.scanners.id;


--
-- Name: schedules; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.schedules (
    id bigint NOT NULL,
    trip_id bigint NOT NULL,
    ferry_id bigint NOT NULL,
    start_date date NOT NULL,
    end_date date,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    frequency_pattern character varying(255),
    CONSTRAINT schedules_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'suspended'::character varying, 'inactive'::character varying])::text[])))
);


ALTER TABLE public.schedules OWNER TO postgres;

--
-- Name: schedules_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.schedules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.schedules_id_seq OWNER TO postgres;

--
-- Name: schedules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.schedules_id_seq OWNED BY public.schedules.id;


--
-- Name: seat_inventories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.seat_inventories (
    id bigint NOT NULL,
    travel_class_id bigint NOT NULL,
    total_seats integer DEFAULT 0 NOT NULL,
    sold_seats integer DEFAULT 0 NOT NULL,
    held_seats integer DEFAULT 0 NOT NULL,
    blocked_seats integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    voyage_id bigint NOT NULL
);


ALTER TABLE public.seat_inventories OWNER TO postgres;

--
-- Name: seat_inventories_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.seat_inventories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.seat_inventories_id_seq OWNER TO postgres;

--
-- Name: seat_inventories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.seat_inventories_id_seq OWNED BY public.seat_inventories.id;


--
-- Name: seat_inventory_allocations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.seat_inventory_allocations (
    id bigint NOT NULL,
    seat_inventory_id bigint NOT NULL,
    agent_id bigint NOT NULL,
    allocated_seats integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.seat_inventory_allocations OWNER TO postgres;

--
-- Name: seat_inventory_allocations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.seat_inventory_allocations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.seat_inventory_allocations_id_seq OWNER TO postgres;

--
-- Name: seat_inventory_allocations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.seat_inventory_allocations_id_seq OWNED BY public.seat_inventory_allocations.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- Name: shift_overtime_requests; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.shift_overtime_requests (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    requested_hours numeric(5,2) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approved_by bigint,
    approved_at timestamp(0) without time zone,
    reason text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT shift_overtime_requests_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


ALTER TABLE public.shift_overtime_requests OWNER TO postgres;

--
-- Name: shift_overtime_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.shift_overtime_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.shift_overtime_requests_id_seq OWNER TO postgres;

--
-- Name: shift_overtime_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.shift_overtime_requests_id_seq OWNED BY public.shift_overtime_requests.id;


--
-- Name: shifts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.shifts (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    start_time timestamp(0) without time zone NOT NULL,
    end_time timestamp(0) without time zone,
    actual_end_time timestamp(0) without time zone,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT shifts_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'ended'::character varying])::text[])))
);


ALTER TABLE public.shifts OWNER TO postgres;

--
-- Name: shifts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.shifts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.shifts_id_seq OWNER TO postgres;

--
-- Name: shifts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.shifts_id_seq OWNED BY public.shifts.id;


--
-- Name: system_configs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.system_configs (
    id bigint NOT NULL,
    key_name character varying(100) NOT NULL,
    value text,
    description character varying(255),
    is_sensitive boolean DEFAULT false NOT NULL,
    "group" character varying(50) DEFAULT 'general'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.system_configs OWNER TO postgres;

--
-- Name: system_configs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.system_configs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.system_configs_id_seq OWNER TO postgres;

--
-- Name: system_configs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.system_configs_id_seq OWNED BY public.system_configs.id;


--
-- Name: system_users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.system_users (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    employee_id character varying(255),
    department character varying(255),
    "position" character varying(255),
    hire_date date,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT system_users_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[])))
);


ALTER TABLE public.system_users OWNER TO postgres;

--
-- Name: system_users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.system_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.system_users_id_seq OWNER TO postgres;

--
-- Name: system_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.system_users_id_seq OWNED BY public.system_users.id;


--
-- Name: ticket_statuses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ticket_statuses (
    id bigint NOT NULL,
    slug character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    color character varying(50) DEFAULT 'slate'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.ticket_statuses OWNER TO postgres;

--
-- Name: ticket_statuses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ticket_statuses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ticket_statuses_id_seq OWNER TO postgres;

--
-- Name: ticket_statuses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ticket_statuses_id_seq OWNED BY public.ticket_statuses.id;


--
-- Name: transaction_categories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transaction_categories (
    id bigint NOT NULL,
    name character varying(50) NOT NULL,
    slug character varying(50) NOT NULL,
    type character varying(10) NOT NULL,
    color character varying(20),
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.transaction_categories OWNER TO postgres;

--
-- Name: transaction_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.transaction_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.transaction_categories_id_seq OWNER TO postgres;

--
-- Name: transaction_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.transaction_categories_id_seq OWNED BY public.transaction_categories.id;


--
-- Name: transaction_statuses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transaction_statuses (
    id bigint NOT NULL,
    name character varying(50) NOT NULL,
    slug character varying(50) NOT NULL,
    color character varying(20),
    icon character varying(50),
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.transaction_statuses OWNER TO postgres;

--
-- Name: transaction_statuses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.transaction_statuses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.transaction_statuses_id_seq OWNER TO postgres;

--
-- Name: transaction_statuses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.transaction_statuses_id_seq OWNED BY public.transaction_statuses.id;


--
-- Name: transactions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transactions (
    id bigint NOT NULL,
    transaction_number character varying(64) NOT NULL,
    transaction_category_id bigint NOT NULL,
    amount numeric(12,2) NOT NULL,
    currency_code character varying(10) DEFAULT 'TZS'::character varying NOT NULL,
    exchange_rate numeric(12,6) DEFAULT '1'::numeric NOT NULL,
    amount_tzs numeric(12,2) NOT NULL,
    balance_before numeric(12,2),
    balance_after numeric(12,2),
    payment_method_id bigint,
    payment_reference character varying(100),
    booking_id bigint,
    ticket_id bigint,
    user_id bigint,
    transaction_status_id bigint DEFAULT '4'::bigint NOT NULL,
    description text,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    payment_type_id bigint
);


ALTER TABLE public.transactions OWNER TO postgres;

--
-- Name: transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.transactions_id_seq OWNER TO postgres;

--
-- Name: transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.transactions_id_seq OWNED BY public.transactions.id;


--
-- Name: travel_classes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.travel_classes (
    id bigint NOT NULL,
    name character varying(50) NOT NULL,
    color character varying(20),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT travel_classes_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[])))
);


ALTER TABLE public.travel_classes OWNER TO postgres;

--
-- Name: travel_classes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.travel_classes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.travel_classes_id_seq OWNER TO postgres;

--
-- Name: travel_classes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.travel_classes_id_seq OWNED BY public.travel_classes.id;


--
-- Name: trips; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.trips (
    id bigint NOT NULL,
    route_id bigint NOT NULL,
    departure_time time(0) without time zone NOT NULL,
    arrival_time time(0) without time zone NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT trips_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[])))
);


ALTER TABLE public.trips OWNER TO postgres;

--
-- Name: trips_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.trips_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.trips_id_seq OWNER TO postgres;

--
-- Name: trips_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.trips_id_seq OWNED BY public.trips.id;


--
-- Name: user_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_permissions (
    user_id bigint NOT NULL,
    permission_id bigint NOT NULL,
    granted boolean DEFAULT true NOT NULL,
    start_date date,
    end_date date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.user_permissions OWNER TO postgres;

--
-- Name: user_roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_roles (
    user_id bigint NOT NULL,
    role_id bigint NOT NULL,
    assigned_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.user_roles OWNER TO postgres;

--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    email character varying(255) NOT NULL,
    phone character varying(20),
    national_id character varying(50),
    password character varying(255) NOT NULL,
    first_name character varying(100) NOT NULL,
    last_name character varying(100) NOT NULL,
    domain character varying(255) DEFAULT 'passenger'::character varying NOT NULL,
    mfa_enabled boolean DEFAULT false NOT NULL,
    mfa_secret character varying(255),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    failed_login_attempts smallint DEFAULT '0'::smallint NOT NULL,
    locked_until timestamp(0) without time zone,
    password_changed_at timestamp(0) without time zone,
    photo_url character varying(255),
    bio text,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    agent_code character varying(3),
    is_system_account boolean DEFAULT false NOT NULL,
    CONSTRAINT users_domain_check CHECK (((domain)::text = ANY ((ARRAY['system_user'::character varying, 'agent'::character varying, 'associate_agent'::character varying, 'passenger'::character varying])::text[]))),
    CONSTRAINT users_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'locked'::character varying, 'suspended'::character varying, 'inactive'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: vessel_certificates_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.vessel_certificates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.vessel_certificates_id_seq OWNER TO postgres;

--
-- Name: vessel_certificates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.vessel_certificates_id_seq OWNED BY public.ferry_certificates.id;


--
-- Name: voyages; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.voyages (
    id bigint NOT NULL,
    schedule_id bigint NOT NULL,
    route_id bigint NOT NULL,
    ferry_id bigint NOT NULL,
    departure_date date NOT NULL,
    departure_time time(0) without time zone NOT NULL,
    status character varying(255) DEFAULT 'scheduled'::character varying NOT NULL,
    weather_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT voyages_status_check CHECK (((status)::text = ANY ((ARRAY['scheduled'::character varying, 'boarding'::character varying, 'departed'::character varying, 'arrived'::character varying, 'cancelled'::character varying])::text[])))
);


ALTER TABLE public.voyages OWNER TO postgres;

--
-- Name: voyages_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.voyages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.voyages_id_seq OWNER TO postgres;

--
-- Name: voyages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.voyages_id_seq OWNED BY public.voyages.id;


--
-- Name: access_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.access_logs ALTER COLUMN id SET DEFAULT nextval('public.access_logs_id_seq'::regclass);


--
-- Name: agent_accounts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_accounts ALTER COLUMN id SET DEFAULT nextval('public.agent_accounts_id_seq'::regclass);


--
-- Name: agent_ferries id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_ferries ALTER COLUMN id SET DEFAULT nextval('public.agent_ferries_id_seq'::regclass);


--
-- Name: agent_routes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_routes ALTER COLUMN id SET DEFAULT nextval('public.agent_routes_id_seq'::regclass);


--
-- Name: agents id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agents ALTER COLUMN id SET DEFAULT nextval('public.agents_id_seq'::regclass);


--
-- Name: announcements id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.announcements ALTER COLUMN id SET DEFAULT nextval('public.announcements_id_seq'::regclass);


--
-- Name: associate_agents id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.associate_agents ALTER COLUMN id SET DEFAULT nextval('public.associate_agents_id_seq'::regclass);


--
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: boarding_events id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.boarding_events ALTER COLUMN id SET DEFAULT nextval('public.boarding_events_id_seq'::regclass);


--
-- Name: booking_locks id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_locks ALTER COLUMN id SET DEFAULT nextval('public.booking_locks_id_seq'::regclass);


--
-- Name: booking_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_logs ALTER COLUMN id SET DEFAULT nextval('public.booking_logs_id_seq'::regclass);


--
-- Name: booking_sources id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_sources ALTER COLUMN id SET DEFAULT nextval('public.booking_sources_id_seq'::regclass);


--
-- Name: booking_statuses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_statuses ALTER COLUMN id SET DEFAULT nextval('public.booking_statuses_id_seq'::regclass);


--
-- Name: bookings id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings ALTER COLUMN id SET DEFAULT nextval('public.bookings_id_seq'::regclass);


--
-- Name: complimentary_tickets id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.complimentary_tickets ALTER COLUMN id SET DEFAULT nextval('public.complimentary_tickets_id_seq'::regclass);


--
-- Name: corporate_invoices id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.corporate_invoices ALTER COLUMN id SET DEFAULT nextval('public.corporate_invoices_id_seq'::regclass);


--
-- Name: corporates id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.corporates ALTER COLUMN id SET DEFAULT nextval('public.corporates_id_seq'::regclass);


--
-- Name: countries id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.countries ALTER COLUMN id SET DEFAULT nextval('public.countries_id_seq'::regclass);


--
-- Name: coupon_usages id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupon_usages ALTER COLUMN id SET DEFAULT nextval('public.coupon_usages_id_seq'::regclass);


--
-- Name: coupons id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupons ALTER COLUMN id SET DEFAULT nextval('public.coupons_id_seq'::regclass);


--
-- Name: currencies id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.currencies ALTER COLUMN id SET DEFAULT nextval('public.currencies_id_seq'::regclass);


--
-- Name: customer_statuses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customer_statuses ALTER COLUMN id SET DEFAULT nextval('public.customer_statuses_id_seq'::regclass);


--
-- Name: dual_control_configs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.dual_control_configs ALTER COLUMN id SET DEFAULT nextval('public.dual_control_configs_id_seq'::regclass);


--
-- Name: dual_control_requests id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.dual_control_requests ALTER COLUMN id SET DEFAULT nextval('public.dual_control_requests_id_seq'::regclass);


--
-- Name: exchange_rates id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.exchange_rates ALTER COLUMN id SET DEFAULT nextval('public.exchange_rates_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: fares id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fares ALTER COLUMN id SET DEFAULT nextval('public.fares_id_seq'::regclass);


--
-- Name: ferries id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferries ALTER COLUMN id SET DEFAULT nextval('public.ferries_id_seq'::regclass);


--
-- Name: ferry_certificates id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferry_certificates ALTER COLUMN id SET DEFAULT nextval('public.vessel_certificates_id_seq'::regclass);


--
-- Name: ferry_class_rooms id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferry_class_rooms ALTER COLUMN id SET DEFAULT nextval('public.ferry_class_rooms_id_seq'::regclass);


--
-- Name: ferry_classes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferry_classes ALTER COLUMN id SET DEFAULT nextval('public.ferry_classes_id_seq'::regclass);


--
-- Name: id_types id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.id_types ALTER COLUMN id SET DEFAULT nextval('public.id_types_id_seq'::regclass);


--
-- Name: identity_types id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.identity_types ALTER COLUMN id SET DEFAULT nextval('public.identity_types_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: languages id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.languages ALTER COLUMN id SET DEFAULT nextval('public.languages_id_seq'::regclass);


--
-- Name: locations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.locations ALTER COLUMN id SET DEFAULT nextval('public.locations_id_seq'::regclass);


--
-- Name: menus id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menus ALTER COLUMN id SET DEFAULT nextval('public.menus_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: nationalities id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.nationalities ALTER COLUMN id SET DEFAULT nextval('public.nationalities_id_seq'::regclass);


--
-- Name: notification_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notification_logs ALTER COLUMN id SET DEFAULT nextval('public.notification_logs_id_seq'::regclass);


--
-- Name: notification_templates id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notification_templates ALTER COLUMN id SET DEFAULT nextval('public.notification_templates_id_seq'::regclass);


--
-- Name: notifications id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications ALTER COLUMN id SET DEFAULT nextval('public.notifications_id_seq'::regclass);


--
-- Name: passenger_types id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passenger_types ALTER COLUMN id SET DEFAULT nextval('public.passenger_types_id_seq'::regclass);


--
-- Name: passengers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passengers ALTER COLUMN id SET DEFAULT nextval('public.passengers_id_seq'::regclass);


--
-- Name: payment_gateway_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_gateway_logs ALTER COLUMN id SET DEFAULT nextval('public.payment_gateway_logs_id_seq'::regclass);


--
-- Name: payment_methods id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_methods ALTER COLUMN id SET DEFAULT nextval('public.payment_methods_id_seq'::regclass);


--
-- Name: payment_statuses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_statuses ALTER COLUMN id SET DEFAULT nextval('public.payment_statuses_id_seq'::regclass);


--
-- Name: payment_types id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_types ALTER COLUMN id SET DEFAULT nextval('public.payment_types_id_seq'::regclass);


--
-- Name: payments id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments ALTER COLUMN id SET DEFAULT nextval('public.payments_id_seq'::regclass);


--
-- Name: pending_refunds id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pending_refunds ALTER COLUMN id SET DEFAULT nextval('public.pending_refunds_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: port_operators id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.port_operators ALTER COLUMN id SET DEFAULT nextval('public.port_operators_id_seq'::regclass);


--
-- Name: portal_translations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.portal_translations ALTER COLUMN id SET DEFAULT nextval('public.portal_translations_id_seq'::regclass);


--
-- Name: ports id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ports ALTER COLUMN id SET DEFAULT nextval('public.ports_id_seq'::regclass);


--
-- Name: price_discounts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.price_discounts ALTER COLUMN id SET DEFAULT nextval('public.price_discounts_id_seq'::regclass);


--
-- Name: promotions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promotions ALTER COLUMN id SET DEFAULT nextval('public.promotions_id_seq'::regclass);


--
-- Name: receipts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.receipts ALTER COLUMN id SET DEFAULT nextval('public.receipts_id_seq'::regclass);


--
-- Name: report_snapshots id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_snapshots ALTER COLUMN id SET DEFAULT nextval('public.report_snapshots_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: routes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes ALTER COLUMN id SET DEFAULT nextval('public.routes_id_seq'::regclass);


--
-- Name: scanners id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.scanners ALTER COLUMN id SET DEFAULT nextval('public.scanners_id_seq'::regclass);


--
-- Name: schedules id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.schedules ALTER COLUMN id SET DEFAULT nextval('public.schedules_id_seq'::regclass);


--
-- Name: seat_inventories id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seat_inventories ALTER COLUMN id SET DEFAULT nextval('public.seat_inventories_id_seq'::regclass);


--
-- Name: seat_inventory_allocations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seat_inventory_allocations ALTER COLUMN id SET DEFAULT nextval('public.seat_inventory_allocations_id_seq'::regclass);


--
-- Name: shift_overtime_requests id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shift_overtime_requests ALTER COLUMN id SET DEFAULT nextval('public.shift_overtime_requests_id_seq'::regclass);


--
-- Name: shifts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shifts ALTER COLUMN id SET DEFAULT nextval('public.shifts_id_seq'::regclass);


--
-- Name: system_configs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_configs ALTER COLUMN id SET DEFAULT nextval('public.system_configs_id_seq'::regclass);


--
-- Name: system_users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_users ALTER COLUMN id SET DEFAULT nextval('public.system_users_id_seq'::regclass);


--
-- Name: ticket_statuses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ticket_statuses ALTER COLUMN id SET DEFAULT nextval('public.ticket_statuses_id_seq'::regclass);


--
-- Name: tickets id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets ALTER COLUMN id SET DEFAULT nextval('public.booking_items_id_seq'::regclass);


--
-- Name: transaction_categories id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaction_categories ALTER COLUMN id SET DEFAULT nextval('public.transaction_categories_id_seq'::regclass);


--
-- Name: transaction_statuses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaction_statuses ALTER COLUMN id SET DEFAULT nextval('public.transaction_statuses_id_seq'::regclass);


--
-- Name: transactions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions ALTER COLUMN id SET DEFAULT nextval('public.transactions_id_seq'::regclass);


--
-- Name: travel_classes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.travel_classes ALTER COLUMN id SET DEFAULT nextval('public.travel_classes_id_seq'::regclass);


--
-- Name: trips id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trips ALTER COLUMN id SET DEFAULT nextval('public.trips_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: voyages id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.voyages ALTER COLUMN id SET DEFAULT nextval('public.voyages_id_seq'::regclass);


--
-- Name: access_logs access_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.access_logs
    ADD CONSTRAINT access_logs_pkey PRIMARY KEY (id);


--
-- Name: agent_accounts agent_accounts_account_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_accounts
    ADD CONSTRAINT agent_accounts_account_number_unique UNIQUE (account_number);


--
-- Name: agent_accounts agent_accounts_agent_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_accounts
    ADD CONSTRAINT agent_accounts_agent_id_unique UNIQUE (agent_id);


--
-- Name: agent_accounts agent_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_accounts
    ADD CONSTRAINT agent_accounts_pkey PRIMARY KEY (id);


--
-- Name: agent_accounts agent_accounts_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_accounts
    ADD CONSTRAINT agent_accounts_uuid_unique UNIQUE (uuid);


--
-- Name: agent_ferries agent_ferries_agent_id_ferry_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_ferries
    ADD CONSTRAINT agent_ferries_agent_id_ferry_id_unique UNIQUE (agent_id, ferry_id);


--
-- Name: agent_ferries agent_ferries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_ferries
    ADD CONSTRAINT agent_ferries_pkey PRIMARY KEY (id);


--
-- Name: agent_routes agent_routes_agent_id_route_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_routes
    ADD CONSTRAINT agent_routes_agent_id_route_id_unique UNIQUE (agent_id, route_id);


--
-- Name: agent_routes agent_routes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_routes
    ADD CONSTRAINT agent_routes_pkey PRIMARY KEY (id);


--
-- Name: agents agents_agent_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agents
    ADD CONSTRAINT agents_agent_code_unique UNIQUE (agent_code);


--
-- Name: agents agents_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agents
    ADD CONSTRAINT agents_pkey PRIMARY KEY (id);


--
-- Name: agents agents_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agents
    ADD CONSTRAINT agents_user_id_unique UNIQUE (user_id);


--
-- Name: agents agents_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agents
    ADD CONSTRAINT agents_uuid_unique UNIQUE (uuid);


--
-- Name: announcements announcements_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_pkey PRIMARY KEY (id);


--
-- Name: associate_agents associate_agents_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.associate_agents
    ADD CONSTRAINT associate_agents_pkey PRIMARY KEY (id);


--
-- Name: associate_agents associate_agents_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.associate_agents
    ADD CONSTRAINT associate_agents_user_id_unique UNIQUE (user_id);


--
-- Name: associate_agents associate_agents_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.associate_agents
    ADD CONSTRAINT associate_agents_uuid_unique UNIQUE (uuid);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: audit_logs audit_logs_sequence_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_sequence_number_unique UNIQUE (sequence_number);


--
-- Name: boarding_events boarding_events_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.boarding_events
    ADD CONSTRAINT boarding_events_pkey PRIMARY KEY (id);


--
-- Name: tickets booking_items_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT booking_items_pkey PRIMARY KEY (id);


--
-- Name: tickets booking_items_qr_code_token_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT booking_items_qr_code_token_unique UNIQUE (qr_code_token);


--
-- Name: tickets booking_items_ticket_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT booking_items_ticket_number_unique UNIQUE (ticket_number);


--
-- Name: booking_locks booking_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_locks
    ADD CONSTRAINT booking_locks_pkey PRIMARY KEY (id);


--
-- Name: booking_logs booking_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_logs
    ADD CONSTRAINT booking_logs_pkey PRIMARY KEY (id);


--
-- Name: booking_sources booking_sources_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_sources
    ADD CONSTRAINT booking_sources_pkey PRIMARY KEY (id);


--
-- Name: booking_sources booking_sources_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_sources
    ADD CONSTRAINT booking_sources_slug_unique UNIQUE (slug);


--
-- Name: booking_statuses booking_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_statuses
    ADD CONSTRAINT booking_statuses_pkey PRIMARY KEY (id);


--
-- Name: booking_statuses booking_statuses_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_statuses
    ADD CONSTRAINT booking_statuses_slug_unique UNIQUE (slug);


--
-- Name: bookings bookings_booking_reference_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_booking_reference_unique UNIQUE (booking_reference);


--
-- Name: bookings bookings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: complimentary_tickets complimentary_tickets_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.complimentary_tickets
    ADD CONSTRAINT complimentary_tickets_pkey PRIMARY KEY (id);


--
-- Name: corporate_invoices corporate_invoices_invoice_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.corporate_invoices
    ADD CONSTRAINT corporate_invoices_invoice_number_unique UNIQUE (invoice_number);


--
-- Name: corporate_invoices corporate_invoices_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.corporate_invoices
    ADD CONSTRAINT corporate_invoices_pkey PRIMARY KEY (id);


--
-- Name: corporates corporates_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.corporates
    ADD CONSTRAINT corporates_pkey PRIMARY KEY (id);


--
-- Name: countries countries_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.countries
    ADD CONSTRAINT countries_code_unique UNIQUE (code);


--
-- Name: countries countries_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.countries
    ADD CONSTRAINT countries_name_unique UNIQUE (name);


--
-- Name: countries countries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.countries
    ADD CONSTRAINT countries_pkey PRIMARY KEY (id);


--
-- Name: coupon_usages coupon_usages_coupon_id_booking_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupon_usages
    ADD CONSTRAINT coupon_usages_coupon_id_booking_id_unique UNIQUE (coupon_id, booking_id);


--
-- Name: coupon_usages coupon_usages_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupon_usages
    ADD CONSTRAINT coupon_usages_pkey PRIMARY KEY (id);


--
-- Name: coupons coupons_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupons
    ADD CONSTRAINT coupons_code_unique UNIQUE (code);


--
-- Name: coupons coupons_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupons
    ADD CONSTRAINT coupons_pkey PRIMARY KEY (id);


--
-- Name: currencies currencies_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.currencies
    ADD CONSTRAINT currencies_code_unique UNIQUE (code);


--
-- Name: currencies currencies_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.currencies
    ADD CONSTRAINT currencies_pkey PRIMARY KEY (id);


--
-- Name: customer_statuses customer_statuses_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customer_statuses
    ADD CONSTRAINT customer_statuses_code_unique UNIQUE (code);


--
-- Name: customer_statuses customer_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customer_statuses
    ADD CONSTRAINT customer_statuses_pkey PRIMARY KEY (id);


--
-- Name: dual_control_configs dual_control_configs_operation_type_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.dual_control_configs
    ADD CONSTRAINT dual_control_configs_operation_type_unique UNIQUE (operation_type);


--
-- Name: dual_control_configs dual_control_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.dual_control_configs
    ADD CONSTRAINT dual_control_configs_pkey PRIMARY KEY (id);


--
-- Name: dual_control_requests dual_control_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.dual_control_requests
    ADD CONSTRAINT dual_control_requests_pkey PRIMARY KEY (id);


--
-- Name: exchange_rates exchange_rates_from_currency_to_currency_effective_from_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.exchange_rates
    ADD CONSTRAINT exchange_rates_from_currency_to_currency_effective_from_unique UNIQUE (from_currency, to_currency, effective_from);


--
-- Name: exchange_rates exchange_rates_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.exchange_rates
    ADD CONSTRAINT exchange_rates_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: fares fares_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fares
    ADD CONSTRAINT fares_pkey PRIMARY KEY (id);


--
-- Name: ferries ferries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferries
    ADD CONSTRAINT ferries_pkey PRIMARY KEY (id);


--
-- Name: ferry_class_rooms ferry_class_rooms_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferry_class_rooms
    ADD CONSTRAINT ferry_class_rooms_pkey PRIMARY KEY (id);


--
-- Name: ferry_classes ferry_classes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferry_classes
    ADD CONSTRAINT ferry_classes_pkey PRIMARY KEY (id);


--
-- Name: id_types id_types_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.id_types
    ADD CONSTRAINT id_types_code_unique UNIQUE (code);


--
-- Name: id_types id_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.id_types
    ADD CONSTRAINT id_types_pkey PRIMARY KEY (id);


--
-- Name: identity_types identity_types_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.identity_types
    ADD CONSTRAINT identity_types_name_unique UNIQUE (name);


--
-- Name: identity_types identity_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.identity_types
    ADD CONSTRAINT identity_types_pkey PRIMARY KEY (id);


--
-- Name: identity_types identity_types_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.identity_types
    ADD CONSTRAINT identity_types_slug_unique UNIQUE (slug);


--
-- Name: integration_configs integration_configs_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.integration_configs
    ADD CONSTRAINT integration_configs_code_unique UNIQUE (code);


--
-- Name: integration_configs integration_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.integration_configs
    ADD CONSTRAINT integration_configs_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: languages languages_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.languages
    ADD CONSTRAINT languages_code_unique UNIQUE (code);


--
-- Name: languages languages_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.languages
    ADD CONSTRAINT languages_pkey PRIMARY KEY (id);


--
-- Name: locations locations_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_code_unique UNIQUE (code);


--
-- Name: locations locations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_pkey PRIMARY KEY (id);


--
-- Name: menus menus_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menus
    ADD CONSTRAINT menus_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: nationalities nationalities_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.nationalities
    ADD CONSTRAINT nationalities_code_unique UNIQUE (code);


--
-- Name: nationalities nationalities_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.nationalities
    ADD CONSTRAINT nationalities_name_unique UNIQUE (name);


--
-- Name: nationalities nationalities_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.nationalities
    ADD CONSTRAINT nationalities_pkey PRIMARY KEY (id);


--
-- Name: notification_logs notification_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notification_logs
    ADD CONSTRAINT notification_logs_pkey PRIMARY KEY (id);


--
-- Name: notification_templates notification_templates_event_channel_locale_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notification_templates
    ADD CONSTRAINT notification_templates_event_channel_locale_unique UNIQUE (event, channel, locale);


--
-- Name: notification_templates notification_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notification_templates
    ADD CONSTRAINT notification_templates_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: passenger_types passenger_types_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passenger_types
    ADD CONSTRAINT passenger_types_code_unique UNIQUE (code);


--
-- Name: passenger_types passenger_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passenger_types
    ADD CONSTRAINT passenger_types_pkey PRIMARY KEY (id);


--
-- Name: passengers passengers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passengers
    ADD CONSTRAINT passengers_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: payment_gateway_logs payment_gateway_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_gateway_logs
    ADD CONSTRAINT payment_gateway_logs_pkey PRIMARY KEY (id);


--
-- Name: payment_methods payment_methods_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_methods
    ADD CONSTRAINT payment_methods_pkey PRIMARY KEY (id);


--
-- Name: payment_methods payment_methods_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_methods
    ADD CONSTRAINT payment_methods_slug_unique UNIQUE (slug);


--
-- Name: payment_statuses payment_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_statuses
    ADD CONSTRAINT payment_statuses_pkey PRIMARY KEY (id);


--
-- Name: payment_statuses payment_statuses_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_statuses
    ADD CONSTRAINT payment_statuses_slug_unique UNIQUE (slug);


--
-- Name: payment_types payment_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_types
    ADD CONSTRAINT payment_types_pkey PRIMARY KEY (id);


--
-- Name: payment_types payment_types_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_types
    ADD CONSTRAINT payment_types_slug_unique UNIQUE (slug);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: payments payments_transaction_reference_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_transaction_reference_unique UNIQUE (transaction_reference);


--
-- Name: pending_refunds pending_refunds_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pending_refunds
    ADD CONSTRAINT pending_refunds_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_unique UNIQUE (name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: port_operators port_operators_operator_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.port_operators
    ADD CONSTRAINT port_operators_operator_number_unique UNIQUE (operator_number);


--
-- Name: port_operators port_operators_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.port_operators
    ADD CONSTRAINT port_operators_pkey PRIMARY KEY (id);


--
-- Name: port_operators port_operators_user_id_port_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.port_operators
    ADD CONSTRAINT port_operators_user_id_port_id_unique UNIQUE (user_id, port_id);


--
-- Name: portal_translations portal_translations_key_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.portal_translations
    ADD CONSTRAINT portal_translations_key_unique UNIQUE (key);


--
-- Name: portal_translations portal_translations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.portal_translations
    ADD CONSTRAINT portal_translations_pkey PRIMARY KEY (id);


--
-- Name: ports ports_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ports
    ADD CONSTRAINT ports_code_unique UNIQUE (code);


--
-- Name: ports ports_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ports
    ADD CONSTRAINT ports_pkey PRIMARY KEY (id);


--
-- Name: price_discounts price_discounts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.price_discounts
    ADD CONSTRAINT price_discounts_pkey PRIMARY KEY (id);


--
-- Name: promotions promotions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promotions
    ADD CONSTRAINT promotions_pkey PRIMARY KEY (id);


--
-- Name: receipts receipts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.receipts
    ADD CONSTRAINT receipts_pkey PRIMARY KEY (id);


--
-- Name: receipts receipts_receipt_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.receipts
    ADD CONSTRAINT receipts_receipt_number_unique UNIQUE (receipt_number);


--
-- Name: report_snapshots report_snapshots_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_snapshots
    ADD CONSTRAINT report_snapshots_pkey PRIMARY KEY (id);


--
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_pkey PRIMARY KEY (role_id, permission_id);


--
-- Name: roles roles_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_unique UNIQUE (name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: roles roles_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_slug_unique UNIQUE (slug);


--
-- Name: routes routes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_pkey PRIMARY KEY (id);


--
-- Name: scanners scanners_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.scanners
    ADD CONSTRAINT scanners_pkey PRIMARY KEY (id);


--
-- Name: scanners scanners_serial_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.scanners
    ADD CONSTRAINT scanners_serial_number_unique UNIQUE (serial_number);


--
-- Name: schedules schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.schedules
    ADD CONSTRAINT schedules_pkey PRIMARY KEY (id);


--
-- Name: seat_inventory_allocations seat_inv_agent_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seat_inventory_allocations
    ADD CONSTRAINT seat_inv_agent_unique UNIQUE (seat_inventory_id, agent_id);


--
-- Name: seat_inventories seat_inv_voyage_class_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seat_inventories
    ADD CONSTRAINT seat_inv_voyage_class_unique UNIQUE (voyage_id, travel_class_id);


--
-- Name: seat_inventories seat_inventories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seat_inventories
    ADD CONSTRAINT seat_inventories_pkey PRIMARY KEY (id);


--
-- Name: seat_inventory_allocations seat_inventory_allocations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seat_inventory_allocations
    ADD CONSTRAINT seat_inventory_allocations_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: shift_overtime_requests shift_overtime_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shift_overtime_requests
    ADD CONSTRAINT shift_overtime_requests_pkey PRIMARY KEY (id);


--
-- Name: shifts shifts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shifts
    ADD CONSTRAINT shifts_pkey PRIMARY KEY (id);


--
-- Name: system_configs system_configs_key_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_configs
    ADD CONSTRAINT system_configs_key_name_unique UNIQUE (key_name);


--
-- Name: system_configs system_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_configs
    ADD CONSTRAINT system_configs_pkey PRIMARY KEY (id);


--
-- Name: system_users system_users_employee_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_users
    ADD CONSTRAINT system_users_employee_id_unique UNIQUE (employee_id);


--
-- Name: system_users system_users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_users
    ADD CONSTRAINT system_users_pkey PRIMARY KEY (id);


--
-- Name: ticket_statuses ticket_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ticket_statuses
    ADD CONSTRAINT ticket_statuses_pkey PRIMARY KEY (id);


--
-- Name: ticket_statuses ticket_statuses_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ticket_statuses
    ADD CONSTRAINT ticket_statuses_slug_unique UNIQUE (slug);


--
-- Name: transaction_categories transaction_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaction_categories
    ADD CONSTRAINT transaction_categories_pkey PRIMARY KEY (id);


--
-- Name: transaction_categories transaction_categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaction_categories
    ADD CONSTRAINT transaction_categories_slug_unique UNIQUE (slug);


--
-- Name: transaction_statuses transaction_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaction_statuses
    ADD CONSTRAINT transaction_statuses_pkey PRIMARY KEY (id);


--
-- Name: transaction_statuses transaction_statuses_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaction_statuses
    ADD CONSTRAINT transaction_statuses_slug_unique UNIQUE (slug);


--
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);


--
-- Name: transactions transactions_transaction_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_transaction_number_unique UNIQUE (transaction_number);


--
-- Name: travel_classes travel_classes_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.travel_classes
    ADD CONSTRAINT travel_classes_name_unique UNIQUE (name);


--
-- Name: travel_classes travel_classes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.travel_classes
    ADD CONSTRAINT travel_classes_pkey PRIMARY KEY (id);


--
-- Name: trips trips_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trips
    ADD CONSTRAINT trips_pkey PRIMARY KEY (id);


--
-- Name: user_permissions user_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_permissions
    ADD CONSTRAINT user_permissions_pkey PRIMARY KEY (user_id, permission_id);


--
-- Name: user_roles user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_pkey PRIMARY KEY (user_id, role_id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_national_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_national_id_unique UNIQUE (national_id);


--
-- Name: users users_phone_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_phone_unique UNIQUE (phone);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: ferry_certificates vessel_certificates_certificate_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferry_certificates
    ADD CONSTRAINT vessel_certificates_certificate_number_unique UNIQUE (certificate_number);


--
-- Name: ferry_certificates vessel_certificates_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferry_certificates
    ADD CONSTRAINT vessel_certificates_pkey PRIMARY KEY (id);


--
-- Name: voyages voyages_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.voyages
    ADD CONSTRAINT voyages_pkey PRIMARY KEY (id);


--
-- Name: access_logs_user_id_event_type_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX access_logs_user_id_event_type_index ON public.access_logs USING btree (user_id, event_type);


--
-- Name: announcements_is_active_start_date_end_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX announcements_is_active_start_date_end_date_index ON public.announcements USING btree (is_active, start_date, end_date);


--
-- Name: audit_logs_entity_type_entity_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX audit_logs_entity_type_entity_id_index ON public.audit_logs USING btree (entity_type, entity_id);


--
-- Name: audit_logs_user_id_action_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX audit_logs_user_id_action_index ON public.audit_logs USING btree (user_id, action);


--
-- Name: boarding_events_booking_item_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX boarding_events_booking_item_id_index ON public.boarding_events USING btree (ticket_id);


--
-- Name: boarding_events_voyage_id_scanned_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX boarding_events_voyage_id_scanned_at_index ON public.boarding_events USING btree (voyage_id, scanned_at);


--
-- Name: booking_items_booking_id_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX booking_items_booking_id_status_index ON public.tickets USING btree (booking_id, status);


--
-- Name: booking_items_ticket_number_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX booking_items_ticket_number_index ON public.tickets USING btree (ticket_number);


--
-- Name: booking_items_voyage_id_ferry_class_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX booking_items_voyage_id_ferry_class_id_index ON public.tickets USING btree (voyage_id, ferry_class_id);


--
-- Name: booking_locks_expires_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX booking_locks_expires_at_index ON public.booking_locks USING btree (expires_at);


--
-- Name: booking_locks_ferry_class_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX booking_locks_ferry_class_id_index ON public.booking_locks USING btree (ferry_class_id);


--
-- Name: booking_locks_seat_number_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX booking_locks_seat_number_index ON public.booking_locks USING btree (seat_number);


--
-- Name: booking_locks_session_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX booking_locks_session_id_index ON public.booking_locks USING btree (session_id);


--
-- Name: booking_locks_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX booking_locks_user_id_index ON public.booking_locks USING btree (user_id);


--
-- Name: booking_locks_voyage_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX booking_locks_voyage_id_index ON public.booking_locks USING btree (voyage_id);


--
-- Name: bookings_booking_reference_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX bookings_booking_reference_status_index ON public.bookings USING btree (booking_reference, status);


--
-- Name: bookings_expires_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX bookings_expires_at_index ON public.bookings USING btree (expires_at);


--
-- Name: bookings_user_id_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX bookings_user_id_status_index ON public.bookings USING btree (user_id, status);


--
-- Name: corporate_invoices_corporate_id_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX corporate_invoices_corporate_id_status_index ON public.corporate_invoices USING btree (corporate_id, status);


--
-- Name: corporates_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX corporates_status_index ON public.corporates USING btree (status);


--
-- Name: coupons_code_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX coupons_code_index ON public.coupons USING btree (code);


--
-- Name: dual_control_requests_status_expires_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX dual_control_requests_status_expires_at_index ON public.dual_control_requests USING btree (status, expires_at);


--
-- Name: idx_sys_tx_bk_cat; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_sys_tx_bk_cat ON public.transactions USING btree (booking_id, transaction_category_id);


--
-- Name: idx_sys_tx_cat_created; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_sys_tx_cat_created ON public.transactions USING btree (transaction_category_id, created_at);


--
-- Name: idx_sys_tx_status_cr; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_sys_tx_status_cr ON public.transactions USING btree (transaction_status_id, created_at);


--
-- Name: idx_sys_tx_usr_cat_cr; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_sys_tx_usr_cat_cr ON public.transactions USING btree (user_id, transaction_category_id, created_at);


--
-- Name: integration_configs_channel_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX integration_configs_channel_index ON public.integration_configs USING btree (channel);


--
-- Name: integration_configs_channel_is_active_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX integration_configs_channel_is_active_index ON public.integration_configs USING btree (channel, is_active);


--
-- Name: integration_configs_channel_is_default_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX integration_configs_channel_is_default_index ON public.integration_configs USING btree (channel, is_default);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: notification_logs_channel_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX notification_logs_channel_status_index ON public.notification_logs USING btree (channel, status);


--
-- Name: notification_logs_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX notification_logs_user_id_index ON public.notification_logs USING btree (user_id);


--
-- Name: notifications_user_id_read_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX notifications_user_id_read_at_index ON public.notifications USING btree (user_id, read_at);


--
-- Name: passengers_user_id_is_saved_profile_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX passengers_user_id_is_saved_profile_index ON public.passengers USING btree (user_id, is_saved_profile);


--
-- Name: payment_gateway_logs_created_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX payment_gateway_logs_created_at_index ON public.payment_gateway_logs USING btree (created_at);


--
-- Name: payment_gateway_logs_gateway_event_type_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX payment_gateway_logs_gateway_event_type_index ON public.payment_gateway_logs USING btree (gateway, event_type);


--
-- Name: payment_gateway_logs_transaction_reference_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX payment_gateway_logs_transaction_reference_index ON public.payment_gateway_logs USING btree (transaction_reference);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: price_discounts_is_active_valid_from_valid_to_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX price_discounts_is_active_valid_from_valid_to_index ON public.price_discounts USING btree (is_active, valid_from, valid_to);


--
-- Name: promotions_is_active_start_date_end_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX promotions_is_active_start_date_end_date_index ON public.promotions USING btree (is_active, start_date, end_date);


--
-- Name: receipts_booking_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX receipts_booking_id_index ON public.receipts USING btree (booking_id);


--
-- Name: receipts_issued_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX receipts_issued_at_index ON public.receipts USING btree (issued_at);


--
-- Name: report_snapshots_snapshot_date_report_type_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX report_snapshots_snapshot_date_report_type_index ON public.report_snapshots USING btree (snapshot_date, report_type);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: transactions_payment_reference_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX transactions_payment_reference_index ON public.transactions USING btree (payment_reference);


--
-- Name: users_email_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX users_email_status_index ON public.users USING btree (email, status);


--
-- Name: users_is_system_account_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX users_is_system_account_index ON public.users USING btree (is_system_account);


--
-- Name: vessel_certificates_expiry_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX vessel_certificates_expiry_date_index ON public.ferry_certificates USING btree (expiry_date);


--
-- Name: vessel_certificates_vessel_id_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX vessel_certificates_vessel_id_status_index ON public.ferry_certificates USING btree (ferry_id, status);


--
-- Name: tickets tr_booking_items_after_delete; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER tr_booking_items_after_delete AFTER DELETE ON public.tickets FOR EACH ROW EXECUTE FUNCTION public.fn_tr_booking_items_after_delete();


--
-- Name: tickets tr_booking_items_after_insert; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER tr_booking_items_after_insert AFTER INSERT ON public.tickets FOR EACH ROW EXECUTE FUNCTION public.fn_tr_booking_items_after_insert();


--
-- Name: tickets tr_booking_items_after_update; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER tr_booking_items_after_update AFTER UPDATE ON public.tickets FOR EACH ROW EXECUTE FUNCTION public.fn_tr_booking_items_after_update();


--
-- Name: booking_locks tr_booking_locks_after_change; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER tr_booking_locks_after_change AFTER INSERT OR DELETE OR UPDATE ON public.booking_locks FOR EACH ROW EXECUTE FUNCTION public.fn_tr_booking_locks_after_change();


--
-- Name: tickets tr_tickets_after_change; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER tr_tickets_after_change AFTER INSERT OR DELETE OR UPDATE ON public.tickets FOR EACH ROW EXECUTE FUNCTION public.fn_tr_tickets_after_change();


--
-- Name: access_logs access_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.access_logs
    ADD CONSTRAINT access_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: agent_accounts agent_accounts_agent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_accounts
    ADD CONSTRAINT agent_accounts_agent_id_foreign FOREIGN KEY (agent_id) REFERENCES public.agents(id) ON DELETE CASCADE;


--
-- Name: agent_ferries agent_ferries_agent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_ferries
    ADD CONSTRAINT agent_ferries_agent_id_foreign FOREIGN KEY (agent_id) REFERENCES public.agents(id) ON DELETE CASCADE;


--
-- Name: agent_ferries agent_ferries_ferry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_ferries
    ADD CONSTRAINT agent_ferries_ferry_id_foreign FOREIGN KEY (ferry_id) REFERENCES public.ferries(id) ON DELETE CASCADE;


--
-- Name: agent_routes agent_routes_agent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_routes
    ADD CONSTRAINT agent_routes_agent_id_foreign FOREIGN KEY (agent_id) REFERENCES public.agents(id) ON DELETE CASCADE;


--
-- Name: agent_routes agent_routes_route_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_routes
    ADD CONSTRAINT agent_routes_route_id_foreign FOREIGN KEY (route_id) REFERENCES public.routes(id) ON DELETE CASCADE;


--
-- Name: agents agents_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agents
    ADD CONSTRAINT agents_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: announcements announcements_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: associate_agents associate_agents_agent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.associate_agents
    ADD CONSTRAINT associate_agents_agent_id_foreign FOREIGN KEY (agent_id) REFERENCES public.agents(id) ON DELETE CASCADE;


--
-- Name: associate_agents associate_agents_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.associate_agents
    ADD CONSTRAINT associate_agents_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: audit_logs audit_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: boarding_events boarding_events_booking_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.boarding_events
    ADD CONSTRAINT boarding_events_booking_item_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: boarding_events boarding_events_port_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.boarding_events
    ADD CONSTRAINT boarding_events_port_id_foreign FOREIGN KEY (port_id) REFERENCES public.ports(id) ON DELETE SET NULL;


--
-- Name: boarding_events boarding_events_scanned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.boarding_events
    ADD CONSTRAINT boarding_events_scanned_by_foreign FOREIGN KEY (scanned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: boarding_events boarding_events_scanner_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.boarding_events
    ADD CONSTRAINT boarding_events_scanner_id_foreign FOREIGN KEY (scanner_id) REFERENCES public.scanners(id) ON DELETE SET NULL;


--
-- Name: boarding_events boarding_events_voyage_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.boarding_events
    ADD CONSTRAINT boarding_events_voyage_id_foreign FOREIGN KEY (voyage_id) REFERENCES public.voyages(id) ON DELETE CASCADE;


--
-- Name: tickets booking_items_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT booking_items_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: tickets booking_items_country_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT booking_items_country_id_foreign FOREIGN KEY (country_id) REFERENCES public.countries(id) ON DELETE SET NULL;


--
-- Name: tickets booking_items_ferry_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT booking_items_ferry_class_id_foreign FOREIGN KEY (ferry_class_id) REFERENCES public.ferry_classes(id) ON DELETE CASCADE;


--
-- Name: tickets booking_items_identity_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT booking_items_identity_type_id_foreign FOREIGN KEY (identity_type_id) REFERENCES public.id_types(id) ON DELETE SET NULL;


--
-- Name: tickets booking_items_nationality_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT booking_items_nationality_id_foreign FOREIGN KEY (nationality_id) REFERENCES public.nationalities(id) ON DELETE SET NULL;


--
-- Name: tickets booking_items_passenger_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT booking_items_passenger_id_foreign FOREIGN KEY (passenger_id) REFERENCES public.passengers(id) ON DELETE SET NULL;


--
-- Name: tickets booking_items_passenger_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT booking_items_passenger_type_id_foreign FOREIGN KEY (passenger_type_id) REFERENCES public.passenger_types(id) ON DELETE SET NULL;


--
-- Name: tickets booking_items_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT booking_items_status_id_foreign FOREIGN KEY (status_id) REFERENCES public.ticket_statuses(id) ON DELETE SET NULL;


--
-- Name: tickets booking_items_voyage_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT booking_items_voyage_id_foreign FOREIGN KEY (voyage_id) REFERENCES public.voyages(id) ON DELETE CASCADE;


--
-- Name: booking_locks booking_locks_agent_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_locks
    ADD CONSTRAINT booking_locks_agent_account_id_foreign FOREIGN KEY (agent_account_id) REFERENCES public.agent_accounts(id) ON DELETE SET NULL;


--
-- Name: booking_locks booking_locks_ferry_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_locks
    ADD CONSTRAINT booking_locks_ferry_class_id_foreign FOREIGN KEY (ferry_class_id) REFERENCES public.ferry_classes(id) ON DELETE CASCADE;


--
-- Name: booking_locks booking_locks_voyage_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_locks
    ADD CONSTRAINT booking_locks_voyage_id_foreign FOREIGN KEY (voyage_id) REFERENCES public.voyages(id) ON DELETE CASCADE;


--
-- Name: booking_logs booking_logs_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_logs
    ADD CONSTRAINT booking_logs_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: booking_logs booking_logs_booking_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_logs
    ADD CONSTRAINT booking_logs_booking_item_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE SET NULL;


--
-- Name: booking_logs booking_logs_performed_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking_logs
    ADD CONSTRAINT booking_logs_performed_by_user_id_foreign FOREIGN KEY (performed_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_booked_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_booked_by_user_id_foreign FOREIGN KEY (booked_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_booking_agent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_booking_agent_id_foreign FOREIGN KEY (booking_agent_id) REFERENCES public.agents(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_cancelled_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_cancelled_by_foreign FOREIGN KEY (cancelled_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_completed_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_completed_by_user_id_foreign FOREIGN KEY (completed_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_corporate_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_corporate_id_foreign FOREIGN KEY (corporate_id) REFERENCES public.corporates(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_payment_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_payment_status_id_foreign FOREIGN KEY (payment_status_id) REFERENCES public.payment_statuses(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_route_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_route_id_foreign FOREIGN KEY (route_id) REFERENCES public.routes(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_source_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_source_id_foreign FOREIGN KEY (source_id) REFERENCES public.booking_sources(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_status_id_foreign FOREIGN KEY (status_id) REFERENCES public.booking_statuses(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bookings bookings_vessel_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_vessel_id_foreign FOREIGN KEY (vessel_id) REFERENCES public.ferries(id) ON DELETE SET NULL;


--
-- Name: complimentary_tickets complimentary_tickets_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.complimentary_tickets
    ADD CONSTRAINT complimentary_tickets_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: complimentary_tickets complimentary_tickets_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.complimentary_tickets
    ADD CONSTRAINT complimentary_tickets_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: complimentary_tickets complimentary_tickets_booking_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.complimentary_tickets
    ADD CONSTRAINT complimentary_tickets_booking_item_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE SET NULL;


--
-- Name: corporate_invoices corporate_invoices_corporate_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.corporate_invoices
    ADD CONSTRAINT corporate_invoices_corporate_id_foreign FOREIGN KEY (corporate_id) REFERENCES public.corporates(id) ON DELETE CASCADE;


--
-- Name: coupon_usages coupon_usages_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupon_usages
    ADD CONSTRAINT coupon_usages_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: coupon_usages coupon_usages_coupon_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupon_usages
    ADD CONSTRAINT coupon_usages_coupon_id_foreign FOREIGN KEY (coupon_id) REFERENCES public.coupons(id) ON DELETE CASCADE;


--
-- Name: coupon_usages coupon_usages_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupon_usages
    ADD CONSTRAINT coupon_usages_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: coupons coupons_promotion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coupons
    ADD CONSTRAINT coupons_promotion_id_foreign FOREIGN KEY (promotion_id) REFERENCES public.promotions(id) ON DELETE SET NULL;


--
-- Name: dual_control_requests dual_control_requests_checker_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.dual_control_requests
    ADD CONSTRAINT dual_control_requests_checker_id_foreign FOREIGN KEY (checker_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: dual_control_requests dual_control_requests_maker_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.dual_control_requests
    ADD CONSTRAINT dual_control_requests_maker_id_foreign FOREIGN KEY (maker_id) REFERENCES public.users(id);


--
-- Name: exchange_rates exchange_rates_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.exchange_rates
    ADD CONSTRAINT exchange_rates_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: fares fares_ferry_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fares
    ADD CONSTRAINT fares_ferry_class_id_foreign FOREIGN KEY (ferry_class_id) REFERENCES public.ferry_classes(id) ON DELETE CASCADE;


--
-- Name: fares fares_ferry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fares
    ADD CONSTRAINT fares_ferry_id_foreign FOREIGN KEY (ferry_id) REFERENCES public.ferries(id) ON DELETE CASCADE;


--
-- Name: fares fares_room_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fares
    ADD CONSTRAINT fares_room_id_foreign FOREIGN KEY (room_id) REFERENCES public.ferry_class_rooms(id) ON DELETE CASCADE;


--
-- Name: fares fares_route_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fares
    ADD CONSTRAINT fares_route_id_foreign FOREIGN KEY (route_id) REFERENCES public.routes(id) ON DELETE CASCADE;


--
-- Name: ferry_class_rooms ferry_class_rooms_ferry_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferry_class_rooms
    ADD CONSTRAINT ferry_class_rooms_ferry_class_id_foreign FOREIGN KEY (ferry_class_id) REFERENCES public.ferry_classes(id) ON DELETE CASCADE;


--
-- Name: ferry_classes ferry_classes_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferry_classes
    ADD CONSTRAINT ferry_classes_class_id_foreign FOREIGN KEY (class_id) REFERENCES public.travel_classes(id) ON DELETE SET NULL;


--
-- Name: ferry_classes ferry_classes_ferry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferry_classes
    ADD CONSTRAINT ferry_classes_ferry_id_foreign FOREIGN KEY (ferry_id) REFERENCES public.ferries(id) ON DELETE CASCADE;


--
-- Name: locations locations_country_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_country_id_foreign FOREIGN KEY (country_id) REFERENCES public.countries(id) ON DELETE SET NULL;


--
-- Name: menus menus_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.menus
    ADD CONSTRAINT menus_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.menus(id) ON DELETE CASCADE;


--
-- Name: notification_logs notification_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notification_logs
    ADD CONSTRAINT notification_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: notifications notifications_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: passengers passengers_id_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passengers
    ADD CONSTRAINT passengers_id_type_id_foreign FOREIGN KEY (id_type_id) REFERENCES public.id_types(id) ON DELETE SET NULL;


--
-- Name: passengers passengers_passenger_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passengers
    ADD CONSTRAINT passengers_passenger_type_id_foreign FOREIGN KEY (passenger_type_id) REFERENCES public.passenger_types(id) ON DELETE SET NULL;


--
-- Name: passengers passengers_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.passengers
    ADD CONSTRAINT passengers_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: payment_gateway_logs payment_gateway_logs_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_gateway_logs
    ADD CONSTRAINT payment_gateway_logs_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE SET NULL;


--
-- Name: payment_gateway_logs payment_gateway_logs_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_gateway_logs
    ADD CONSTRAINT payment_gateway_logs_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE SET NULL;


--
-- Name: payments payments_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: payments payments_payment_method_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_payment_method_id_foreign FOREIGN KEY (payment_method_id) REFERENCES public.payment_methods(id) ON DELETE SET NULL;


--
-- Name: pending_refunds pending_refunds_agent_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pending_refunds
    ADD CONSTRAINT pending_refunds_agent_account_id_foreign FOREIGN KEY (agent_account_id) REFERENCES public.agent_accounts(id) ON DELETE CASCADE;


--
-- Name: pending_refunds pending_refunds_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pending_refunds
    ADD CONSTRAINT pending_refunds_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: pending_refunds pending_refunds_booking_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pending_refunds
    ADD CONSTRAINT pending_refunds_booking_item_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE SET NULL;


--
-- Name: pending_refunds pending_refunds_released_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pending_refunds
    ADD CONSTRAINT pending_refunds_released_by_foreign FOREIGN KEY (released_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: port_operators port_operators_port_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.port_operators
    ADD CONSTRAINT port_operators_port_id_foreign FOREIGN KEY (port_id) REFERENCES public.ports(id) ON DELETE CASCADE;


--
-- Name: port_operators port_operators_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.port_operators
    ADD CONSTRAINT port_operators_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: receipts receipts_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.receipts
    ADD CONSTRAINT receipts_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- Name: receipts receipts_issued_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.receipts
    ADD CONSTRAINT receipts_issued_by_foreign FOREIGN KEY (issued_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: receipts receipts_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.receipts
    ADD CONSTRAINT receipts_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE SET NULL;


--
-- Name: role_permissions role_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_permissions role_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: routes routes_destination_port_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_destination_port_id_foreign FOREIGN KEY (destination_port_id) REFERENCES public.ports(id);


--
-- Name: routes routes_origin_port_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_origin_port_id_foreign FOREIGN KEY (origin_port_id) REFERENCES public.ports(id);


--
-- Name: scanners scanners_assigned_operator_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.scanners
    ADD CONSTRAINT scanners_assigned_operator_id_foreign FOREIGN KEY (assigned_operator_id) REFERENCES public.port_operators(id) ON DELETE SET NULL;


--
-- Name: scanners scanners_assigned_port_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.scanners
    ADD CONSTRAINT scanners_assigned_port_id_foreign FOREIGN KEY (assigned_port_id) REFERENCES public.ports(id) ON DELETE SET NULL;


--
-- Name: schedules schedules_ferry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.schedules
    ADD CONSTRAINT schedules_ferry_id_foreign FOREIGN KEY (ferry_id) REFERENCES public.ferries(id) ON DELETE CASCADE;


--
-- Name: schedules schedules_trip_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.schedules
    ADD CONSTRAINT schedules_trip_id_foreign FOREIGN KEY (trip_id) REFERENCES public.trips(id) ON DELETE CASCADE;


--
-- Name: seat_inventories seat_inventories_travel_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seat_inventories
    ADD CONSTRAINT seat_inventories_travel_class_id_foreign FOREIGN KEY (travel_class_id) REFERENCES public.ferry_classes(id) ON DELETE CASCADE;


--
-- Name: seat_inventories seat_inventories_voyage_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seat_inventories
    ADD CONSTRAINT seat_inventories_voyage_id_foreign FOREIGN KEY (voyage_id) REFERENCES public.voyages(id) ON DELETE CASCADE;


--
-- Name: seat_inventory_allocations seat_inventory_allocations_agent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seat_inventory_allocations
    ADD CONSTRAINT seat_inventory_allocations_agent_id_foreign FOREIGN KEY (agent_id) REFERENCES public.agents(id) ON DELETE CASCADE;


--
-- Name: seat_inventory_allocations seat_inventory_allocations_seat_inventory_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.seat_inventory_allocations
    ADD CONSTRAINT seat_inventory_allocations_seat_inventory_id_foreign FOREIGN KEY (seat_inventory_id) REFERENCES public.seat_inventories(id) ON DELETE CASCADE;


--
-- Name: shift_overtime_requests shift_overtime_requests_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shift_overtime_requests
    ADD CONSTRAINT shift_overtime_requests_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: shift_overtime_requests shift_overtime_requests_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shift_overtime_requests
    ADD CONSTRAINT shift_overtime_requests_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: shifts shifts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shifts
    ADD CONSTRAINT shifts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: system_users system_users_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_users
    ADD CONSTRAINT system_users_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_booking_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_booking_id_foreign FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_booking_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_booking_item_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_payment_method_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_payment_method_id_foreign FOREIGN KEY (payment_method_id) REFERENCES public.payment_methods(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_payment_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_payment_type_id_foreign FOREIGN KEY (payment_type_id) REFERENCES public.payment_types(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_transaction_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_transaction_category_id_foreign FOREIGN KEY (transaction_category_id) REFERENCES public.transaction_categories(id);


--
-- Name: transactions transactions_transaction_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_transaction_status_id_foreign FOREIGN KEY (transaction_status_id) REFERENCES public.transaction_statuses(id);


--
-- Name: transactions transactions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: trips trips_route_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.trips
    ADD CONSTRAINT trips_route_id_foreign FOREIGN KEY (route_id) REFERENCES public.routes(id) ON DELETE CASCADE;


--
-- Name: user_permissions user_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_permissions
    ADD CONSTRAINT user_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: user_permissions user_permissions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_permissions
    ADD CONSTRAINT user_permissions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: user_roles user_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ferry_certificates vessel_certificates_vessel_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ferry_certificates
    ADD CONSTRAINT vessel_certificates_vessel_id_foreign FOREIGN KEY (ferry_id) REFERENCES public.ferries(id) ON DELETE CASCADE;


--
-- Name: voyages voyages_ferry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.voyages
    ADD CONSTRAINT voyages_ferry_id_foreign FOREIGN KEY (ferry_id) REFERENCES public.ferries(id) ON DELETE CASCADE;


--
-- Name: voyages voyages_route_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.voyages
    ADD CONSTRAINT voyages_route_id_foreign FOREIGN KEY (route_id) REFERENCES public.routes(id) ON DELETE CASCADE;


--
-- Name: voyages voyages_schedule_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.voyages
    ADD CONSTRAINT voyages_schedule_id_foreign FOREIGN KEY (schedule_id) REFERENCES public.schedules(id) ON DELETE CASCADE;


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE USAGE ON SCHEMA public FROM PUBLIC;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

\unrestrict ey81ilz764kPborJNoyd2zChlhP47QMJtlWdcDgsM6NxvoPEWlig0aYCDIgIGjI

