-- ============================================================================
-- Sistema escolar · I.E. Divino Maestro
-- Estructura completa de la base de datos + catálogos (sin datos personales).
-- Generado con pg_dump (PostgreSQL 18) el 2026-10-31.
-- Uso:  createdb -U postgres db_apafa_colegio
--       psql -U postgres -d db_apafa_colegio -f database/sistema_escolar.sql
-- Luego crea el primer usuario con: php bin/crear-usuario.php
-- ============================================================================
--
-- PostgreSQL database dump
--

\restrict lebJlXTK1I08tr8URblhGAF4G0q93vvJxhtlmCNmXxSaozkaW5Q3FBSE7rHUsgN

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

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

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: anio; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.anio (
    id bigint NOT NULL,
    anio smallint NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT anio_anio_check CHECK (((anio >= 2020) AND (anio <= 2100)))
);


--
-- Name: anio_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.anio_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: anio_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.anio_id_seq OWNED BY public.anio.id;


--
-- Name: api_token; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.api_token (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    token_hash character(64) NOT NULL,
    dispositivo character varying(150),
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    ultimo_uso_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    expira_en timestamp without time zone NOT NULL
);


--
-- Name: api_token_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.api_token_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: api_token_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.api_token_id_seq OWNED BY public.api_token.id;


--
-- Name: auditoria_movimiento; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.auditoria_movimiento (
    id bigint NOT NULL,
    usuario_id bigint,
    accion character varying(100) NOT NULL,
    detalles text,
    ip_origen inet,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: auditoria_movimiento_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.auditoria_movimiento_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: auditoria_movimiento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.auditoria_movimiento_id_seq OWNED BY public.auditoria_movimiento.id;


--
-- Name: calificacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.calificacion (
    id bigint NOT NULL,
    matricula_id bigint NOT NULL,
    curso_id bigint NOT NULL,
    periodo_id bigint NOT NULL,
    nota numeric(4,2) NOT NULL,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT calificacion_nota_check CHECK (((nota >= (0)::numeric) AND (nota <= (20)::numeric)))
);


--
-- Name: calificacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.calificacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: calificacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.calificacion_id_seq OWNED BY public.calificacion.id;


--
-- Name: concepto_pago; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.concepto_pago (
    id bigint NOT NULL,
    descripcion character varying(80) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    monto_default numeric(10,2),
    monto_total numeric(10,2),
    tipo_cobro character varying(20) DEFAULT 'estudiante'::character varying NOT NULL,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT concepto_pago_monto_default_check CHECK ((monto_default >= (0)::numeric)),
    CONSTRAINT concepto_pago_monto_total_check CHECK ((monto_total >= (0)::numeric)),
    CONSTRAINT concepto_pago_tipo_cobro_check CHECK (((tipo_cobro)::text = ANY ((ARRAY['estudiante'::character varying, 'familia'::character varying])::text[])))
);


--
-- Name: concepto_pago_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.concepto_pago_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: concepto_pago_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.concepto_pago_id_seq OWNED BY public.concepto_pago.id;


--
-- Name: cuenta_pago; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cuenta_pago (
    id bigint NOT NULL,
    matricula_id bigint NOT NULL,
    responsable_id bigint,
    concepto_id bigint NOT NULL,
    monto_total numeric(10,2) NOT NULL,
    monto_pagado numeric(10,2) DEFAULT 0 NOT NULL,
    saldo_restante numeric(10,2) NOT NULL,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT cuenta_pago_monto_pagado_check CHECK ((monto_pagado >= (0)::numeric)),
    CONSTRAINT cuenta_pago_monto_total_check CHECK ((monto_total > (0)::numeric)),
    CONSTRAINT cuenta_pago_saldo_restante_check CHECK ((saldo_restante >= (0)::numeric))
);


--
-- Name: cuenta_pago_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cuenta_pago_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cuenta_pago_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cuenta_pago_id_seq OWNED BY public.cuenta_pago.id;


--
-- Name: curso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.curso (
    id bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    grado character varying(50) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: curso_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.curso_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: curso_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.curso_id_seq OWNED BY public.curso.id;


--
-- Name: estudiante; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.estudiante (
    id bigint NOT NULL,
    dni character varying(8) NOT NULL,
    nombres character varying(100) NOT NULL,
    apellido_paterno character varying(100) NOT NULL,
    apellido_materno character varying(100) NOT NULL,
    fecha_nacimiento date,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    estado_academico character varying(20) DEFAULT 'Activo'::character varying NOT NULL,
    CONSTRAINT estudiante_estado_academico_check CHECK (((estado_academico)::text = ANY ((ARRAY['Activo'::character varying, 'Retirado'::character varying, 'Trasladado'::character varying])::text[])))
);


--
-- Name: estudiante_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.estudiante_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: estudiante_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.estudiante_id_seq OWNED BY public.estudiante.id;


--
-- Name: estudiante_responsable; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.estudiante_responsable (
    id bigint NOT NULL,
    estudiante_id bigint NOT NULL,
    responsable_id bigint NOT NULL,
    parentesco character varying(60) NOT NULL,
    tipo character varying(20) NOT NULL,
    es_apoderado boolean DEFAULT false NOT NULL,
    creado_en timestamp without time zone,
    actualizado_en timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT estudiante_responsable_tipo_check CHECK (((tipo)::text = ANY ((ARRAY['padre'::character varying, 'madre'::character varying, 'otro'::character varying, 'emergencia'::character varying])::text[])))
);


--
-- Name: estudiante_responsable_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.estudiante_responsable_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: estudiante_responsable_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.estudiante_responsable_id_seq OWNED BY public.estudiante_responsable.id;


--
-- Name: grado; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.grado (
    id bigint NOT NULL,
    nivel_id bigint NOT NULL,
    numero smallint NOT NULL,
    nombre character varying(30) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT grado_numero_check CHECK (((numero >= 1) AND (numero <= 6)))
);


--
-- Name: grado_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.grado_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: grado_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.grado_id_seq OWNED BY public.grado.id;


--
-- Name: grado_seccion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.grado_seccion (
    id bigint NOT NULL,
    grado_id bigint NOT NULL,
    seccion_id bigint NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: grado_seccion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.grado_seccion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: grado_seccion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.grado_seccion_id_seq OWNED BY public.grado_seccion.id;


--
-- Name: matricula; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.matricula (
    id bigint NOT NULL,
    estudiante_id bigint,
    anio_id bigint,
    nivel_id bigint,
    grado_seccion_id bigint,
    apoderado_principal_id bigint,
    es_estudiante_nuevo boolean DEFAULT true NOT NULL,
    registrado_por bigint,
    nombres character varying(100),
    apellido_paterno character varying(100),
    apellido_materno character varying(100),
    dni character varying(8),
    nivel character varying(30),
    estado character varying(20) DEFAULT 'Activa'::character varying NOT NULL,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: matricula_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.matricula_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: matricula_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.matricula_id_seq OWNED BY public.matricula.id;


--
-- Name: nivel; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.nivel (
    id bigint NOT NULL,
    nombre character varying(30) NOT NULL,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: nivel_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.nivel_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: nivel_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.nivel_id_seq OWNED BY public.nivel.id;


--
-- Name: pago; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pago (
    id bigint NOT NULL,
    matricula_id bigint,
    concepto_id bigint,
    monto numeric(10,2) NOT NULL,
    abono_hoy numeric(10,2) DEFAULT 0 NOT NULL,
    entregado_por character varying(150),
    monto_total numeric(10,2),
    monto_pagado numeric(10,2) DEFAULT 0 NOT NULL,
    saldo_restante numeric(10,2) DEFAULT 0 NOT NULL,
    nro_recibo character varying(30),
    talonario character varying(30),
    estado_pago character varying(20) DEFAULT 'pagado'::character varying NOT NULL,
    fecha_pago date NOT NULL,
    registrado_por bigint,
    responsable character varying(150),
    concepto character varying(80),
    estado character varying(20) DEFAULT 'Pagado'::character varying,
    observacion character varying(250),
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    responsable_id bigint,
    cuenta_pago_id bigint,
    CONSTRAINT pago_abono_hoy_check CHECK ((abono_hoy >= (0)::numeric)),
    CONSTRAINT pago_estado_pago_check CHECK (((estado_pago)::text = ANY ((ARRAY['pagado'::character varying, 'pendiente'::character varying, 'anulado'::character varying])::text[]))),
    CONSTRAINT pago_monto_check CHECK ((monto >= (0)::numeric))
);


--
-- Name: pago_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pago_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pago_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pago_id_seq OWNED BY public.pago.id;


--
-- Name: periodo_academico; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.periodo_academico (
    id bigint NOT NULL,
    nombre character varying(50) NOT NULL,
    fecha_inicio date NOT NULL,
    fecha_fin date NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT periodo_academico_check CHECK ((fecha_inicio <= fecha_fin))
);


--
-- Name: periodo_academico_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.periodo_academico_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: periodo_academico_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.periodo_academico_id_seq OWNED BY public.periodo_academico.id;


--
-- Name: permiso_usuario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permiso_usuario (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    permiso character varying(50) NOT NULL,
    otorgado boolean DEFAULT false NOT NULL,
    otorgado_por bigint,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: permiso_usuario_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permiso_usuario_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permiso_usuario_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permiso_usuario_id_seq OWNED BY public.permiso_usuario.id;


--
-- Name: permisos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permisos (
    id bigint NOT NULL,
    codigo character varying(50) NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion character varying(255),
    activo boolean DEFAULT true NOT NULL,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: permisos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permisos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permisos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permisos_id_seq OWNED BY public.permisos.id;


--
-- Name: responsable; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.responsable (
    id bigint NOT NULL,
    dni character varying(8) NOT NULL,
    nombres character varying(100) NOT NULL,
    apellido_paterno character varying(100) NOT NULL,
    apellido_materno character varying(100),
    direccion text,
    celular character varying(20),
    correo character varying(150),
    vinculo character varying(60),
    celular_actual character varying(20),
    celular_padre character varying(20),
    celular_madre character varying(20),
    numero_tarjeta character varying(30),
    nro_tarjeta_apafa character varying(30),
    cuota_apafa numeric(10,2) DEFAULT 0,
    nombre_padre_adicional character varying(150),
    creado_en timestamp without time zone,
    actualizado_en timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT responsable_cuota_apafa_check CHECK ((cuota_apafa >= (0)::numeric))
);


--
-- Name: responsable_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.responsable_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: responsable_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.responsable_id_seq OWNED BY public.responsable.id;


--
-- Name: schema_migracion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.schema_migracion (
    archivo character varying(255) NOT NULL,
    aplicado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: seccion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.seccion (
    id bigint NOT NULL,
    nombre character varying(10) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: seccion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.seccion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: seccion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.seccion_id_seq OWNED BY public.seccion.id;


--
-- Name: usuario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuario (
    id bigint NOT NULL,
    nombres character varying(150) NOT NULL,
    correo character varying(150) NOT NULL,
    password_hash character varying(255) NOT NULL,
    rol character varying(20) NOT NULL,
    estado boolean DEFAULT true NOT NULL,
    permiso_matricular boolean DEFAULT false NOT NULL,
    permiso_cobrar boolean DEFAULT false NOT NULL,
    permiso_editar boolean DEFAULT false NOT NULL,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    actualizado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    foto character varying(100),
    CONSTRAINT usuario_rol_check CHECK (((rol)::text = ANY ((ARRAY['director'::character varying, 'asistente'::character varying, 'hiperadmin'::character varying])::text[])))
);


--
-- Name: usuario_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usuario_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usuario_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usuario_id_seq OWNED BY public.usuario.id;


--
-- Name: usuario_permisos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuario_permisos (
    usuario_id bigint NOT NULL,
    permiso_id bigint NOT NULL,
    otorgado_por bigint,
    creado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: anio id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.anio ALTER COLUMN id SET DEFAULT nextval('public.anio_id_seq'::regclass);


--
-- Name: api_token id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_token ALTER COLUMN id SET DEFAULT nextval('public.api_token_id_seq'::regclass);


--
-- Name: auditoria_movimiento id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria_movimiento ALTER COLUMN id SET DEFAULT nextval('public.auditoria_movimiento_id_seq'::regclass);


--
-- Name: calificacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calificacion ALTER COLUMN id SET DEFAULT nextval('public.calificacion_id_seq'::regclass);


--
-- Name: concepto_pago id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.concepto_pago ALTER COLUMN id SET DEFAULT nextval('public.concepto_pago_id_seq'::regclass);


--
-- Name: cuenta_pago id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cuenta_pago ALTER COLUMN id SET DEFAULT nextval('public.cuenta_pago_id_seq'::regclass);


--
-- Name: curso id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curso ALTER COLUMN id SET DEFAULT nextval('public.curso_id_seq'::regclass);


--
-- Name: estudiante id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante ALTER COLUMN id SET DEFAULT nextval('public.estudiante_id_seq'::regclass);


--
-- Name: estudiante_responsable id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_responsable ALTER COLUMN id SET DEFAULT nextval('public.estudiante_responsable_id_seq'::regclass);


--
-- Name: grado id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.grado ALTER COLUMN id SET DEFAULT nextval('public.grado_id_seq'::regclass);


--
-- Name: grado_seccion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.grado_seccion ALTER COLUMN id SET DEFAULT nextval('public.grado_seccion_id_seq'::regclass);


--
-- Name: matricula id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matricula ALTER COLUMN id SET DEFAULT nextval('public.matricula_id_seq'::regclass);


--
-- Name: nivel id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nivel ALTER COLUMN id SET DEFAULT nextval('public.nivel_id_seq'::regclass);


--
-- Name: pago id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago ALTER COLUMN id SET DEFAULT nextval('public.pago_id_seq'::regclass);


--
-- Name: periodo_academico id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.periodo_academico ALTER COLUMN id SET DEFAULT nextval('public.periodo_academico_id_seq'::regclass);


--
-- Name: permiso_usuario id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permiso_usuario ALTER COLUMN id SET DEFAULT nextval('public.permiso_usuario_id_seq'::regclass);


--
-- Name: permisos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permisos ALTER COLUMN id SET DEFAULT nextval('public.permisos_id_seq'::regclass);


--
-- Name: responsable id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.responsable ALTER COLUMN id SET DEFAULT nextval('public.responsable_id_seq'::regclass);


--
-- Name: seccion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seccion ALTER COLUMN id SET DEFAULT nextval('public.seccion_id_seq'::regclass);


--
-- Name: usuario id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario ALTER COLUMN id SET DEFAULT nextval('public.usuario_id_seq'::regclass);


--
-- Name: anio anio_anio_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.anio
    ADD CONSTRAINT anio_anio_key UNIQUE (anio);


--
-- Name: anio anio_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.anio
    ADD CONSTRAINT anio_pkey PRIMARY KEY (id);


--
-- Name: api_token api_token_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_token
    ADD CONSTRAINT api_token_pkey PRIMARY KEY (id);


--
-- Name: api_token api_token_token_hash_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_token
    ADD CONSTRAINT api_token_token_hash_key UNIQUE (token_hash);


--
-- Name: auditoria_movimiento auditoria_movimiento_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria_movimiento
    ADD CONSTRAINT auditoria_movimiento_pkey PRIMARY KEY (id);


--
-- Name: calificacion calificacion_matricula_id_curso_id_periodo_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calificacion
    ADD CONSTRAINT calificacion_matricula_id_curso_id_periodo_id_key UNIQUE (matricula_id, curso_id, periodo_id);


--
-- Name: calificacion calificacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calificacion
    ADD CONSTRAINT calificacion_pkey PRIMARY KEY (id);


--
-- Name: concepto_pago concepto_pago_descripcion_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.concepto_pago
    ADD CONSTRAINT concepto_pago_descripcion_key UNIQUE (descripcion);


--
-- Name: concepto_pago concepto_pago_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.concepto_pago
    ADD CONSTRAINT concepto_pago_pkey PRIMARY KEY (id);


--
-- Name: cuenta_pago cuenta_pago_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cuenta_pago
    ADD CONSTRAINT cuenta_pago_pkey PRIMARY KEY (id);


--
-- Name: curso curso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curso
    ADD CONSTRAINT curso_pkey PRIMARY KEY (id);


--
-- Name: estudiante estudiante_dni_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante
    ADD CONSTRAINT estudiante_dni_key UNIQUE (dni);


--
-- Name: estudiante estudiante_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante
    ADD CONSTRAINT estudiante_pkey PRIMARY KEY (id);


--
-- Name: estudiante_responsable estudiante_responsable_estudiante_id_responsable_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_responsable
    ADD CONSTRAINT estudiante_responsable_estudiante_id_responsable_id_key UNIQUE (estudiante_id, responsable_id);


--
-- Name: estudiante_responsable estudiante_responsable_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_responsable
    ADD CONSTRAINT estudiante_responsable_pkey PRIMARY KEY (id);


--
-- Name: grado grado_nivel_id_numero_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.grado
    ADD CONSTRAINT grado_nivel_id_numero_key UNIQUE (nivel_id, numero);


--
-- Name: grado grado_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.grado
    ADD CONSTRAINT grado_pkey PRIMARY KEY (id);


--
-- Name: grado_seccion grado_seccion_grado_id_seccion_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.grado_seccion
    ADD CONSTRAINT grado_seccion_grado_id_seccion_id_key UNIQUE (grado_id, seccion_id);


--
-- Name: grado_seccion grado_seccion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.grado_seccion
    ADD CONSTRAINT grado_seccion_pkey PRIMARY KEY (id);


--
-- Name: matricula matricula_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matricula
    ADD CONSTRAINT matricula_pkey PRIMARY KEY (id);


--
-- Name: nivel nivel_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nivel
    ADD CONSTRAINT nivel_nombre_key UNIQUE (nombre);


--
-- Name: nivel nivel_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nivel
    ADD CONSTRAINT nivel_pkey PRIMARY KEY (id);


--
-- Name: pago pago_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_pkey PRIMARY KEY (id);


--
-- Name: periodo_academico periodo_academico_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.periodo_academico
    ADD CONSTRAINT periodo_academico_pkey PRIMARY KEY (id);


--
-- Name: permiso_usuario permiso_usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permiso_usuario
    ADD CONSTRAINT permiso_usuario_pkey PRIMARY KEY (id);


--
-- Name: permiso_usuario permiso_usuario_usuario_id_permiso_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permiso_usuario
    ADD CONSTRAINT permiso_usuario_usuario_id_permiso_key UNIQUE (usuario_id, permiso);


--
-- Name: permisos permisos_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permisos
    ADD CONSTRAINT permisos_codigo_key UNIQUE (codigo);


--
-- Name: permisos permisos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permisos
    ADD CONSTRAINT permisos_pkey PRIMARY KEY (id);


--
-- Name: responsable responsable_dni_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.responsable
    ADD CONSTRAINT responsable_dni_key UNIQUE (dni);


--
-- Name: responsable responsable_nro_tarjeta_apafa_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.responsable
    ADD CONSTRAINT responsable_nro_tarjeta_apafa_key UNIQUE (nro_tarjeta_apafa);


--
-- Name: responsable responsable_numero_tarjeta_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.responsable
    ADD CONSTRAINT responsable_numero_tarjeta_key UNIQUE (numero_tarjeta);


--
-- Name: responsable responsable_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.responsable
    ADD CONSTRAINT responsable_pkey PRIMARY KEY (id);


--
-- Name: schema_migracion schema_migracion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.schema_migracion
    ADD CONSTRAINT schema_migracion_pkey PRIMARY KEY (archivo);


--
-- Name: seccion seccion_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seccion
    ADD CONSTRAINT seccion_nombre_key UNIQUE (nombre);


--
-- Name: seccion seccion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seccion
    ADD CONSTRAINT seccion_pkey PRIMARY KEY (id);


--
-- Name: usuario usuario_correo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_correo_key UNIQUE (correo);


--
-- Name: usuario_permisos usuario_permisos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario_permisos
    ADD CONSTRAINT usuario_permisos_pkey PRIMARY KEY (usuario_id, permiso_id);


--
-- Name: usuario usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (id);


--
-- Name: cuenta_pago_matricula_concepto_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX cuenta_pago_matricula_concepto_unique ON public.cuenta_pago USING btree (matricula_id, concepto_id) WHERE (matricula_id IS NOT NULL);


--
-- Name: estudiante_responsable_estudiante_tipo_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX estudiante_responsable_estudiante_tipo_idx ON public.estudiante_responsable USING btree (estudiante_id, tipo);


--
-- Name: estudiante_unica_madre_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX estudiante_unica_madre_idx ON public.estudiante_responsable USING btree (estudiante_id) WHERE ((tipo)::text = 'madre'::text);


--
-- Name: estudiante_unico_apoderado_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX estudiante_unico_apoderado_idx ON public.estudiante_responsable USING btree (estudiante_id) WHERE (es_apoderado = true);


--
-- Name: estudiante_unico_padre_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX estudiante_unico_padre_idx ON public.estudiante_responsable USING btree (estudiante_id) WHERE ((tipo)::text = 'padre'::text);


--
-- Name: idx_api_token_expira; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_api_token_expira ON public.api_token USING btree (expira_en);


--
-- Name: idx_api_token_usuario; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_api_token_usuario ON public.api_token USING btree (usuario_id);


--
-- Name: matricula_estudiante_anio_id_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX matricula_estudiante_anio_id_unique ON public.matricula USING btree (estudiante_id, anio_id) WHERE (estudiante_id IS NOT NULL);


--
-- Name: matricula_estudiante_anio_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX matricula_estudiante_anio_unique ON public.matricula USING btree (estudiante_id, anio_id) WHERE (estudiante_id IS NOT NULL);


--
-- Name: responsable_nro_tarjeta_apafa_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX responsable_nro_tarjeta_apafa_unique ON public.responsable USING btree (nro_tarjeta_apafa) WHERE (nro_tarjeta_apafa IS NOT NULL);


--
-- Name: responsable_numero_tarjeta_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX responsable_numero_tarjeta_unique ON public.responsable USING btree (numero_tarjeta) WHERE (numero_tarjeta IS NOT NULL);


--
-- Name: api_token api_token_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_token
    ADD CONSTRAINT api_token_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuario(id) ON DELETE CASCADE;


--
-- Name: auditoria_movimiento auditoria_movimiento_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria_movimiento
    ADD CONSTRAINT auditoria_movimiento_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuario(id) ON DELETE SET NULL;


--
-- Name: calificacion calificacion_curso_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calificacion
    ADD CONSTRAINT calificacion_curso_id_fkey FOREIGN KEY (curso_id) REFERENCES public.curso(id) ON DELETE RESTRICT;


--
-- Name: calificacion calificacion_matricula_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calificacion
    ADD CONSTRAINT calificacion_matricula_id_fkey FOREIGN KEY (matricula_id) REFERENCES public.matricula(id) ON DELETE CASCADE;


--
-- Name: calificacion calificacion_periodo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.calificacion
    ADD CONSTRAINT calificacion_periodo_id_fkey FOREIGN KEY (periodo_id) REFERENCES public.periodo_academico(id) ON DELETE RESTRICT;


--
-- Name: cuenta_pago cuenta_pago_concepto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cuenta_pago
    ADD CONSTRAINT cuenta_pago_concepto_id_fkey FOREIGN KEY (concepto_id) REFERENCES public.concepto_pago(id) ON DELETE RESTRICT;


--
-- Name: cuenta_pago cuenta_pago_matricula_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cuenta_pago
    ADD CONSTRAINT cuenta_pago_matricula_id_fkey FOREIGN KEY (matricula_id) REFERENCES public.matricula(id) ON DELETE CASCADE;


--
-- Name: cuenta_pago cuenta_pago_responsable_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cuenta_pago
    ADD CONSTRAINT cuenta_pago_responsable_id_fkey FOREIGN KEY (responsable_id) REFERENCES public.responsable(id) ON DELETE SET NULL;


--
-- Name: estudiante_responsable estudiante_responsable_estudiante_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_responsable
    ADD CONSTRAINT estudiante_responsable_estudiante_id_fkey FOREIGN KEY (estudiante_id) REFERENCES public.estudiante(id) ON DELETE CASCADE;


--
-- Name: estudiante_responsable estudiante_responsable_responsable_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiante_responsable
    ADD CONSTRAINT estudiante_responsable_responsable_id_fkey FOREIGN KEY (responsable_id) REFERENCES public.responsable(id) ON DELETE CASCADE;


--
-- Name: grado grado_nivel_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.grado
    ADD CONSTRAINT grado_nivel_id_fkey FOREIGN KEY (nivel_id) REFERENCES public.nivel(id) ON DELETE CASCADE;


--
-- Name: grado_seccion grado_seccion_grado_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.grado_seccion
    ADD CONSTRAINT grado_seccion_grado_id_fkey FOREIGN KEY (grado_id) REFERENCES public.grado(id) ON DELETE CASCADE;


--
-- Name: grado_seccion grado_seccion_seccion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.grado_seccion
    ADD CONSTRAINT grado_seccion_seccion_id_fkey FOREIGN KEY (seccion_id) REFERENCES public.seccion(id) ON DELETE CASCADE;


--
-- Name: matricula matricula_anio_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matricula
    ADD CONSTRAINT matricula_anio_id_fkey FOREIGN KEY (anio_id) REFERENCES public.anio(id) ON DELETE RESTRICT;


--
-- Name: matricula matricula_apoderado_principal_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matricula
    ADD CONSTRAINT matricula_apoderado_principal_id_fkey FOREIGN KEY (apoderado_principal_id) REFERENCES public.responsable(id) ON DELETE SET NULL;


--
-- Name: matricula matricula_estudiante_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matricula
    ADD CONSTRAINT matricula_estudiante_id_fkey FOREIGN KEY (estudiante_id) REFERENCES public.estudiante(id) ON DELETE CASCADE;


--
-- Name: matricula matricula_grado_seccion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matricula
    ADD CONSTRAINT matricula_grado_seccion_id_fkey FOREIGN KEY (grado_seccion_id) REFERENCES public.grado_seccion(id) ON DELETE RESTRICT;


--
-- Name: matricula matricula_nivel_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matricula
    ADD CONSTRAINT matricula_nivel_id_fkey FOREIGN KEY (nivel_id) REFERENCES public.nivel(id) ON DELETE RESTRICT;


--
-- Name: matricula matricula_registrado_por_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matricula
    ADD CONSTRAINT matricula_registrado_por_fkey FOREIGN KEY (registrado_por) REFERENCES public.usuario(id) ON DELETE SET NULL;


--
-- Name: pago pago_concepto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_concepto_id_fkey FOREIGN KEY (concepto_id) REFERENCES public.concepto_pago(id) ON DELETE RESTRICT;


--
-- Name: pago pago_cuenta_pago_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_cuenta_pago_id_fkey FOREIGN KEY (cuenta_pago_id) REFERENCES public.cuenta_pago(id) ON DELETE SET NULL;


--
-- Name: pago pago_matricula_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_matricula_id_fkey FOREIGN KEY (matricula_id) REFERENCES public.matricula(id) ON DELETE RESTRICT;


--
-- Name: pago pago_registrado_por_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_registrado_por_fkey FOREIGN KEY (registrado_por) REFERENCES public.usuario(id) ON DELETE SET NULL;


--
-- Name: pago pago_responsable_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_responsable_id_fkey FOREIGN KEY (responsable_id) REFERENCES public.responsable(id) ON DELETE SET NULL;


--
-- Name: permiso_usuario permiso_usuario_otorgado_por_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permiso_usuario
    ADD CONSTRAINT permiso_usuario_otorgado_por_fkey FOREIGN KEY (otorgado_por) REFERENCES public.usuario(id) ON DELETE SET NULL;


--
-- Name: permiso_usuario permiso_usuario_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permiso_usuario
    ADD CONSTRAINT permiso_usuario_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuario(id) ON DELETE CASCADE;


--
-- Name: usuario_permisos usuario_permisos_otorgado_por_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario_permisos
    ADD CONSTRAINT usuario_permisos_otorgado_por_fkey FOREIGN KEY (otorgado_por) REFERENCES public.usuario(id) ON DELETE SET NULL;


--
-- Name: usuario_permisos usuario_permisos_permiso_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario_permisos
    ADD CONSTRAINT usuario_permisos_permiso_id_fkey FOREIGN KEY (permiso_id) REFERENCES public.permisos(id) ON DELETE CASCADE;


--
-- Name: usuario_permisos usuario_permisos_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario_permisos
    ADD CONSTRAINT usuario_permisos_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuario(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict lebJlXTK1I08tr8URblhGAF4G0q93vvJxhtlmCNmXxSaozkaW5Q3FBSE7rHUsgN


-- Catálogos
-- PostgreSQL database dump
\restrict bzuo3PdD8fqYj5lIXUseEJwlc48FvgOQsV7qMTGYGPUPXwJzn9f0d9KMmwsffyS
-- Data for Name: anio; Type: TABLE DATA; Schema: public; Owner: -
INSERT INTO public.anio VALUES
	(1, 2020, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(2, 2021, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(3, 2022, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(4, 2023, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(5, 2024, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(6, 2025, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(7, 2026, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(8, 2027, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942');
-- Data for Name: nivel; Type: TABLE DATA; Schema: public; Owner: -
INSERT INTO public.nivel VALUES
	(1, 'Inicial', '2026-10-30 14:46:50.385942'),
	(2, 'Primaria', '2026-10-30 14:46:50.385942'),
	(3, 'Secundaria', '2026-10-30 14:46:50.385942');
-- Data for Name: grado; Type: TABLE DATA; Schema: public; Owner: -
INSERT INTO public.grado VALUES
	(1, 1, 5, '5 años', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(2, 1, 4, '4 años', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(3, 1, 3, '3 años', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(4, 2, 6, '6to', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(5, 2, 5, '5to', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(6, 2, 4, '4to', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(7, 2, 3, '3ro', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(8, 2, 2, '2do', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(9, 2, 1, '1ro', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(10, 3, 5, '5to', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(11, 3, 4, '4to', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(12, 3, 3, '3ro', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(13, 3, 2, '2do', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(14, 3, 1, '1ro', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942');
-- Data for Name: seccion; Type: TABLE DATA; Schema: public; Owner: -
INSERT INTO public.seccion VALUES
	(1, 'A', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(2, 'B', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(3, 'C', true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942');
-- Data for Name: grado_seccion; Type: TABLE DATA; Schema: public; Owner: -
INSERT INTO public.grado_seccion VALUES
	(1, 1, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(2, 2, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(3, 3, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(4, 4, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(5, 5, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(6, 6, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(7, 7, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(8, 8, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(9, 9, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(10, 10, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(11, 11, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(12, 12, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(13, 13, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(14, 14, 1, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(15, 1, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(16, 2, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(17, 3, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(18, 4, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(19, 5, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(20, 6, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(21, 7, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(22, 8, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(23, 9, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(24, 10, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(25, 11, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(26, 12, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(27, 13, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(28, 14, 2, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(29, 1, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(30, 2, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(31, 3, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(32, 4, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(33, 5, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(34, 6, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(35, 7, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(36, 8, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(37, 9, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(38, 10, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(39, 11, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(40, 12, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(41, 13, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942'),
	(42, 14, 3, true, '2026-10-30 14:46:50.385942', '2026-10-30 14:46:50.385942');
-- Data for Name: permisos; Type: TABLE DATA; Schema: public; Owner: -
INSERT INTO public.permisos VALUES
	(1, 'p_editar', 'Editar responsables', 'Crear y actualizar padres y apoderados.', true, '2026-10-30 14:46:50.385942'),
	(2, 'p_matricular', 'Gestionar matrículas', 'Registrar y actualizar matrículas.', true, '2026-10-30 14:46:50.385942'),
	(3, 'p_cobrar', 'Gestionar pagos', 'Registrar y administrar pagos escolares.', true, '2026-10-30 14:46:50.385942'),
	(4, 'p_auditoria', 'Gestionar auditoría', 'Eliminar registros de auditoría.', true, '2026-10-30 14:46:50.385942');
-- Data for Name: schema_migracion; Type: TABLE DATA; Schema: public; Owner: -
INSERT INTO public.schema_migracion VALUES
	('schema.sql', '2026-10-30 14:46:50.385942'),
	('permissions.sql', '2026-10-30 14:46:50.81918'),
	('migraciones/001_cursos_periodos_calificaciones.sql', '2026-10-30 14:46:50.830965'),
	('migraciones/002_ajustes_pagos_matricula.sql', '2026-10-30 14:46:50.861586'),
	('migraciones/003_api_token.sql', '2026-10-30 15:25:03.360842'),
	('migraciones/004_usuario_foto.sql', '2026-10-31 12:21:01.463369');
-- Name: anio_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
SELECT pg_catalog.setval('public.anio_id_seq', 8, true);
-- Name: grado_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
SELECT pg_catalog.setval('public.grado_id_seq', 14, true);
-- Name: grado_seccion_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
SELECT pg_catalog.setval('public.grado_seccion_id_seq', 42, true);
-- Name: nivel_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
SELECT pg_catalog.setval('public.nivel_id_seq', 3, true);
-- Name: permisos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
SELECT pg_catalog.setval('public.permisos_id_seq', 8, true);
-- Name: seccion_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
SELECT pg_catalog.setval('public.seccion_id_seq', 3, true);
-- PostgreSQL database dump complete
\unrestrict bzuo3PdD8fqYj5lIXUseEJwlc48FvgOQsV7qMTGYGPUPXwJzn9f0d9KMmwsffyS

-- Conceptos de pago de ejemplo
INSERT INTO public.concepto_pago (descripcion, activo, monto_default, monto_total, tipo_cobro) VALUES ('Cuota APAFA', true, NULL, NULL, 'estudiante');
INSERT INTO public.concepto_pago (descripcion, activo, monto_default, monto_total, tipo_cobro) VALUES ('Internet', true, 5.00, 5.00, 'estudiante');
INSERT INTO public.concepto_pago (descripcion, activo, monto_default, monto_total, tipo_cobro) VALUES ('Multa', true, NULL, NULL, 'estudiante');
INSERT INTO public.concepto_pago (descripcion, activo, monto_default, monto_total, tipo_cobro) VALUES ('Apoyo a la gestión', true, 30.00, 30.00, 'estudiante');
INSERT INTO public.concepto_pago (descripcion, activo, monto_default, monto_total, tipo_cobro) VALUES ('Otro', true, NULL, NULL, 'estudiante');
INSERT INTO public.concepto_pago (descripcion, activo, monto_default, monto_total, tipo_cobro) VALUES ('Nuevo Estudiante', true, 150.00, 150.00, 'estudiante');
INSERT INTO public.concepto_pago (descripcion, activo, monto_default, monto_total, tipo_cobro) VALUES ('Extraordinaria', true, 50.00, 50.00, 'estudiante');

-- Ajusta las secuencias a los datos cargados
SELECT pg_catalog.setval('public.anio_id_seq', COALESCE((SELECT MAX(id) FROM public.anio), 0) + 1, false);
SELECT pg_catalog.setval('public.api_token_id_seq', COALESCE((SELECT MAX(id) FROM public.api_token), 0) + 1, false);
SELECT pg_catalog.setval('public.auditoria_movimiento_id_seq', COALESCE((SELECT MAX(id) FROM public.auditoria_movimiento), 0) + 1, false);
SELECT pg_catalog.setval('public.calificacion_id_seq', COALESCE((SELECT MAX(id) FROM public.calificacion), 0) + 1, false);
SELECT pg_catalog.setval('public.concepto_pago_id_seq', COALESCE((SELECT MAX(id) FROM public.concepto_pago), 0) + 1, false);
SELECT pg_catalog.setval('public.cuenta_pago_id_seq', COALESCE((SELECT MAX(id) FROM public.cuenta_pago), 0) + 1, false);
SELECT pg_catalog.setval('public.curso_id_seq', COALESCE((SELECT MAX(id) FROM public.curso), 0) + 1, false);
SELECT pg_catalog.setval('public.estudiante_id_seq', COALESCE((SELECT MAX(id) FROM public.estudiante), 0) + 1, false);
SELECT pg_catalog.setval('public.estudiante_responsable_id_seq', COALESCE((SELECT MAX(id) FROM public.estudiante_responsable), 0) + 1, false);
SELECT pg_catalog.setval('public.grado_id_seq', COALESCE((SELECT MAX(id) FROM public.grado), 0) + 1, false);
SELECT pg_catalog.setval('public.grado_seccion_id_seq', COALESCE((SELECT MAX(id) FROM public.grado_seccion), 0) + 1, false);
SELECT pg_catalog.setval('public.matricula_id_seq', COALESCE((SELECT MAX(id) FROM public.matricula), 0) + 1, false);
SELECT pg_catalog.setval('public.nivel_id_seq', COALESCE((SELECT MAX(id) FROM public.nivel), 0) + 1, false);
SELECT pg_catalog.setval('public.pago_id_seq', COALESCE((SELECT MAX(id) FROM public.pago), 0) + 1, false);
SELECT pg_catalog.setval('public.periodo_academico_id_seq', COALESCE((SELECT MAX(id) FROM public.periodo_academico), 0) + 1, false);
SELECT pg_catalog.setval('public.permiso_usuario_id_seq', COALESCE((SELECT MAX(id) FROM public.permiso_usuario), 0) + 1, false);
SELECT pg_catalog.setval('public.permisos_id_seq', COALESCE((SELECT MAX(id) FROM public.permisos), 0) + 1, false);
SELECT pg_catalog.setval('public.responsable_id_seq', COALESCE((SELECT MAX(id) FROM public.responsable), 0) + 1, false);
SELECT pg_catalog.setval('public.seccion_id_seq', COALESCE((SELECT MAX(id) FROM public.seccion), 0) + 1, false);
SELECT pg_catalog.setval('public.usuario_id_seq', COALESCE((SELECT MAX(id) FROM public.usuario), 0) + 1, false);
