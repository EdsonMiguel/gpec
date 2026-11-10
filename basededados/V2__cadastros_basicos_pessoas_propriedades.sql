-- ============================================================
-- GESTÃO PECUÁRIA | BASELINE (edição 12) | PostgreSQL / Flyway
-- V1: Cadastros básicos, pessoas, papéis e propriedades
-- Migração de INSTALAÇÃO NOVA, não incremental de um banco existente.
-- A integridade e as regras originais foram preservadas.
-- ============================================================


CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE EXTENSION IF NOT EXISTS btree_gist;



-- ============================================================
-- ESTADO
-- ============================================================

CREATE TABLE estado (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    nome VARCHAR(100) NOT NULL,
    sigla CHAR(2) NOT NULL,
    codigo_ibge INTEGER,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uk_estado_sigla
        UNIQUE (sigla),

    CONSTRAINT uk_estado_codigo_ibge
        UNIQUE (codigo_ibge)
);



-- ============================================================
-- MUNICÍPIO
-- ============================================================

CREATE TABLE municipio (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    estado_id UUID NOT NULL,

    nome VARCHAR(150) NOT NULL,
    codigo_ibge INTEGER NOT NULL,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uk_municipio_codigo_ibge
        UNIQUE (codigo_ibge),

    CONSTRAINT fk_municipio_estado
        FOREIGN KEY (estado_id)
        REFERENCES estado(id)
);



-- ============================================================
-- PESSOA E PAPÉIS
-- Cadastro único: nome é nome civil ou razão social; documento é CPF/CNPJ.
-- ============================================================

CREATE TABLE pessoa (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tipo VARCHAR(10) NOT NULL,
    nome VARCHAR(150) NOT NULL,
    documento VARCHAR(30),
    email VARCHAR(150),
    telefone VARCHAR(30),
    nome_fantasia VARCHAR(150),
    inscricao_estadual VARCHAR(30),
    inscricao_municipal VARCHAR(30),
    data_nascimento DATE,
    data_abertura DATE,
    rg VARCHAR(30),
    rg_orgao_emissor VARCHAR(30),
    municipio_id UUID REFERENCES municipio(id) ON DELETE RESTRICT,
    cep VARCHAR(8),
    logradouro VARCHAR(150),
    numero VARCHAR(20),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    codigo_fornecedor VARCHAR(50) UNIQUE,
    observacao_fornecedor TEXT,
    conselho_profissional VARCHAR(30),
    numero_registro VARCHAR(40),
    uf_registro CHAR(2),
    observacao_profissional TEXT,
    observacao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_pessoa_campos_pf CHECK (tipo = 'FISICA' OR (data_nascimento IS NULL AND rg IS NULL AND rg_orgao_emissor IS NULL)),
    CONSTRAINT ck_pessoa_abertura CHECK (tipo = 'JURIDICA' OR data_abertura IS NULL),
    CONSTRAINT ck_pessoa_registro CHECK ((conselho_profissional IS NULL AND numero_registro IS NULL AND uf_registro IS NULL) OR (conselho_profissional IS NOT NULL AND numero_registro IS NOT NULL AND uf_registro IS NOT NULL)),
    CONSTRAINT ck_pessoa_tipo CHECK (tipo IN ('FISICA','JURIDICA')),
    CONSTRAINT ck_pessoa_nome CHECK (btrim(nome) <> ''),
    CONSTRAINT ck_pessoa_documento CHECK (documento IS NULL OR (documento = btrim(documento) AND documento <> '')),
    CONSTRAINT uk_pessoa_documento UNIQUE (tipo, documento)
);


CREATE TABLE papel_pessoa (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (btrim(codigo) <> '' AND btrim(nome) <> '')
);


CREATE TABLE pessoa_papel (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pessoa_id UUID NOT NULL REFERENCES pessoa(id) ON DELETE RESTRICT,
    papel_pessoa_id UUID NOT NULL REFERENCES papel_pessoa(id) ON DELETE RESTRICT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (pessoa_id, papel_pessoa_id)
);

CREATE INDEX idx_pessoa_papel_papel ON pessoa_papel(papel_pessoa_id);


-- ============================================================
-- PAPEL DE FORNECEDOR
-- Fornecedor é um papel de pessoa, sem cadastro de identidade separado.
-- ============================================================



-- ============================================================
-- PROPRIEDADE
-- Uma pessoa física ou jurídica pode possuir várias propriedades.
-- ============================================================

CREATE TABLE propriedade (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    pessoa_titular_id UUID NOT NULL,

    nome VARCHAR(150) NOT NULL,
    codigo_interno VARCHAR(50),

    municipio_id UUID,

    cep VARCHAR(8),
    logradouro VARCHAR(150),
    numero VARCHAR(20),
    complemento VARCHAR(100),
    bairro VARCHAR(100),

    area_total_ha NUMERIC(12,2),

    latitude NUMERIC(10,7),
    longitude NUMERIC(10,7),

    observacao TEXT,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uk_propriedade_pessoa_titular_id UNIQUE (pessoa_titular_id, id),
    CONSTRAINT uk_propriedade_codigo
        UNIQUE (codigo_interno),

    CONSTRAINT fk_propriedade_titular
        FOREIGN KEY (pessoa_titular_id)
        REFERENCES pessoa(id),

    CONSTRAINT fk_propriedade_municipio
        FOREIGN KEY (municipio_id)
        REFERENCES municipio(id),

    CONSTRAINT ck_propriedade_area
        CHECK (
            area_total_ha IS NULL
            OR area_total_ha >= 0
        ),

    CONSTRAINT ck_propriedade_coordenadas CHECK (
        (latitude IS NULL) = (longitude IS NULL)
    ),

    CONSTRAINT ck_propriedade_latitude
        CHECK (
            latitude IS NULL
            OR latitude BETWEEN -90 AND 90
        ),

    CONSTRAINT ck_propriedade_longitude
        CHECK (
            longitude IS NULL
            OR longitude BETWEEN -180 AND 180
        )
);



-- ============================================================
-- ÍNDICES
-- ============================================================

CREATE INDEX idx_pessoa_municipio
    ON pessoa(municipio_id);



CREATE INDEX idx_propriedade_titular
    ON propriedade(pessoa_titular_id);


CREATE INDEX idx_propriedade_municipio
    ON propriedade(municipio_id);



-- ============================================================
-- UNICIDADE E ÍNDICES ADICIONAIS
-- ============================================================
CREATE INDEX idx_municipio_estado ON municipio(estado_id);
