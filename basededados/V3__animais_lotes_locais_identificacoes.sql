-- ============================================================
-- GESTÃO PECUÁRIA | BASELINE (edição 12) | PostgreSQL / Flyway
-- V2: Raças, categorias, lotes, locais, animais, identificações e histórico de categorias
-- Migração de INSTALAÇÃO NOVA, não incremental de um banco existente.
-- A integridade e as regras originais foram preservadas.
-- ============================================================




-- ============================================================
-- RAÇA
-- Cadastro global.
-- ============================================================

CREATE TABLE raca (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    nome VARCHAR(100) NOT NULL,

    origem VARCHAR(100),
    descricao TEXT,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uk_raca_nome
        UNIQUE (nome)
);



-- ============================================================
-- CATEGORIA DO ANIMAL
--
-- Exemplos:
-- BEZERRO
-- BEZERRA
-- GARROTE
-- NOVILHO
-- NOVILHA
-- BOI
-- VACA
-- TOURO
-- ============================================================

CREATE TABLE categoria_animal (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    codigo VARCHAR(30) NOT NULL,

    nome VARCHAR(100) NOT NULL,
    descricao TEXT,

    sexo_aplicavel VARCHAR(10),

    idade_minima_meses INTEGER,
    idade_maxima_meses INTEGER,

    ordem INTEGER,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uk_categoria_codigo
        UNIQUE (codigo),

    CONSTRAINT uk_categoria_nome
        UNIQUE (nome),

    CONSTRAINT ck_categoria_sexo
        CHECK (
            sexo_aplicavel IS NULL
            OR sexo_aplicavel IN (
                'MACHO',
                'FEMEA'
            )
        ),

    CONSTRAINT ck_categoria_idade_minima
        CHECK (
            idade_minima_meses IS NULL
            OR idade_minima_meses >= 0
        ),

    CONSTRAINT ck_categoria_idade_maxima
        CHECK (
            idade_maxima_meses IS NULL
            OR idade_maxima_meses >= 0
        ),

    CONSTRAINT ck_categoria_intervalo_idade
        CHECK (
            idade_minima_meses IS NULL
            OR idade_maxima_meses IS NULL
            OR idade_maxima_meses >= idade_minima_meses
        )
);



-- ============================================================
-- LOTE
-- Grupo de animais para manejo.
-- ============================================================

CREATE TABLE lote (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    propriedade_id UUID NOT NULL,

    codigo VARCHAR(50),
    nome VARCHAR(100) NOT NULL,

    descricao TEXT,

    finalidade VARCHAR(30),

    data_inicio DATE,
    data_fim DATE,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uk_lote_propriedade_id UNIQUE (propriedade_id, id),

    CONSTRAINT uk_lote_codigo
        UNIQUE (propriedade_id, codigo),

    CONSTRAINT uk_lote_nome
        UNIQUE (propriedade_id, nome),

    CONSTRAINT fk_lote_propriedade
        FOREIGN KEY (propriedade_id)
        REFERENCES propriedade(id),

    CONSTRAINT ck_lote_finalidade
        CHECK (
            finalidade IS NULL
            OR finalidade IN (
                'CRIA',
                'RECRIA',
                'ENGORDA',
                'REPRODUCAO',
                'CONFINAMENTO',
                'OUTRO'
            )
        ),

    CONSTRAINT ck_lote_periodo
        CHECK (
            data_fim IS NULL
            OR data_inicio IS NULL
            OR data_fim >= data_inicio
        )
);



-- ============================================================
-- LOCAL
--
-- Representa locais físicos dentro da propriedade:
-- pasto, piquete, curral, confinamento etc.
-- ============================================================

CREATE TABLE local (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    propriedade_id UUID NOT NULL,

    codigo VARCHAR(50),

    nome VARCHAR(100) NOT NULL,

    tipo VARCHAR(30) NOT NULL,

    descricao TEXT,

    area_ha NUMERIC(12,2),

    latitude NUMERIC(10,7),
    longitude NUMERIC(10,7),

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uk_local_propriedade_id UNIQUE (propriedade_id, id),

    CONSTRAINT uk_local_codigo
        UNIQUE (propriedade_id, codigo),

    CONSTRAINT uk_local_nome
        UNIQUE (propriedade_id, nome),

    CONSTRAINT fk_local_propriedade
        FOREIGN KEY (propriedade_id)
        REFERENCES propriedade(id),

    CONSTRAINT ck_local_tipo
        CHECK (
            tipo IN (
                'PASTO',
                'PIQUETE',
                'CURRAL',
                'CONFINAMENTO',
                'BAIA',
                'RECEPCAO',
                'EMBARQUE',
                'OUTRO'
            )
        ),

    CONSTRAINT ck_local_area
        CHECK (
            area_ha IS NULL
            OR area_ha >= 0
        ),

    CONSTRAINT ck_local_coordenadas CHECK (
        (latitude IS NULL) = (longitude IS NULL)
    ),

    CONSTRAINT ck_local_latitude
        CHECK (
            latitude IS NULL
            OR latitude BETWEEN -90 AND 90
        ),

    CONSTRAINT ck_local_longitude
        CHECK (
            longitude IS NULL
            OR longitude BETWEEN -180 AND 180
        )
);



-- ============================================================
-- ANIMAL
-- ============================================================

CREATE TABLE animal (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    propriedade_id UUID NOT NULL,

    codigo_interno VARCHAR(50) NOT NULL,

    nome VARCHAR(100),

    sexo VARCHAR(10) NOT NULL,

    data_nascimento DATE,

    data_nascimento_estimada BOOLEAN
        NOT NULL DEFAULT FALSE,

    raca_id UUID,

    categoria_id UUID,

    pelagem VARCHAR(100),

    origem VARCHAR(30),

    fornecedor_origem_id UUID,

    data_entrada_propriedade DATE NOT NULL DEFAULT CURRENT_DATE,

    status VARCHAR(30) NOT NULL DEFAULT 'ATIVO',

    foto_url VARCHAR(500),

    observacao TEXT,

    lote_atual_id UUID,

    local_atual_id UUID,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uk_animal_propriedade_id UNIQUE (propriedade_id, id),
    CONSTRAINT ck_animal_codigo CHECK (btrim(codigo_interno) <> ''),
    CONSTRAINT ck_animal_nascimento_estimado CHECK (
        NOT data_nascimento_estimada OR data_nascimento IS NOT NULL
    ),
    CONSTRAINT ck_animal_datas CHECK (
        data_nascimento IS NULL OR data_nascimento <= data_entrada_propriedade
    ),
    CONSTRAINT ck_animal_alocacao_status CHECK (
        status = 'ATIVO' OR (lote_atual_id IS NULL AND local_atual_id IS NULL)
    ),

    CONSTRAINT uk_animal_codigo
        UNIQUE (
            propriedade_id,
            codigo_interno
        ),

    CONSTRAINT fk_animal_propriedade
        FOREIGN KEY (propriedade_id)
        REFERENCES propriedade(id),

    CONSTRAINT fk_animal_raca
        FOREIGN KEY (raca_id)
        REFERENCES raca(id),

    CONSTRAINT fk_animal_categoria
        FOREIGN KEY (categoria_id)
        REFERENCES categoria_animal(id),

    CONSTRAINT fk_animal_fornecedor
        FOREIGN KEY (fornecedor_origem_id)
        REFERENCES pessoa(id),

    CONSTRAINT fk_animal_lote
        FOREIGN KEY (propriedade_id, lote_atual_id)
        REFERENCES lote(propriedade_id, id),

    CONSTRAINT fk_animal_local
        FOREIGN KEY (propriedade_id, local_atual_id)
        REFERENCES local(propriedade_id, id),

    CONSTRAINT ck_animal_sexo
        CHECK (
            sexo IN (
                'MACHO',
                'FEMEA'
            )
        ),

    CONSTRAINT ck_animal_origem
        CHECK (
            origem IS NULL
            OR origem IN (
                'NASCIMENTO',
                'COMPRA',
                'TRANSFERENCIA',
                'DOACAO',
                'OUTRO'
            )
        ),

    CONSTRAINT ck_animal_status
        CHECK (
            status IN (
                'ATIVO',
                'VENDIDO',
                'MORTO',
                'DESCARTADO',
                'TRANSFERIDO'
            )
        )
);



-- ============================================================
-- IDENTIFICAÇÕES DO ANIMAL
--
-- Permite múltiplas formas de identificação:
-- BRINCO
-- RFID
-- SISBOV
-- TATUAGEM
-- ============================================================

CREATE TABLE animal_identificacao (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    animal_id UUID NOT NULL,
    propriedade_id UUID NOT NULL,

    tipo VARCHAR(20) NOT NULL,

    numero VARCHAR(100) NOT NULL,

    principal BOOLEAN NOT NULL DEFAULT FALSE,

    observacao TEXT,

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT ck_identificacao_numero CHECK (numero = btrim(numero) AND numero <> ''),
    CONSTRAINT ck_identificacao_principal CHECK (NOT principal OR ativo),

    CONSTRAINT fk_identificacao_animal
        FOREIGN KEY (propriedade_id, animal_id)
        REFERENCES animal(propriedade_id, id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT ck_identificacao_tipo
        CHECK (
            tipo IN (
                'BRINCO',
                'RFID',
                'SISBOV',
                'TATUAGEM',
                'OUTRO'
            )
        )
);



-- ============================================================
-- HISTÓRICO DE CATEGORIA DO ANIMAL
--
-- Exemplo:
-- Bezerro -> Garrote -> Novilho -> Boi
-- ============================================================

CREATE TABLE historico_categoria_animal (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    animal_id UUID NOT NULL,

    categoria_id UUID NOT NULL,

    data_inicio DATE NOT NULL,

    data_fim DATE,

    motivo VARCHAR(150),

    observacao TEXT,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_historico_categoria_animal
        FOREIGN KEY (animal_id)
        REFERENCES animal(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_historico_categoria_categoria
        FOREIGN KEY (categoria_id)
        REFERENCES categoria_animal(id),

    CONSTRAINT ck_historico_categoria_datas
        CHECK (
            data_fim IS NULL
            OR data_fim > data_inicio
        ),

    CONSTRAINT ex_historico_categoria_periodo
        EXCLUDE USING gist (
            animal_id WITH =,
            daterange(data_inicio, data_fim, '[)') WITH &&
        )
);


CREATE INDEX idx_animal_raca
    ON animal(raca_id);


CREATE INDEX idx_animal_categoria
    ON animal(categoria_id);


CREATE INDEX idx_animal_fornecedor
    ON animal(fornecedor_origem_id);


CREATE INDEX idx_animal_lote
    ON animal(lote_atual_id);


CREATE INDEX idx_animal_local
    ON animal(local_atual_id);


CREATE INDEX idx_animal_propriedade_status
    ON animal(propriedade_id, status);



CREATE INDEX idx_animal_identificacao_animal
    ON animal_identificacao(animal_id);



CREATE INDEX idx_historico_categoria_animal
    ON historico_categoria_animal(
        animal_id,
        data_inicio
    );

CREATE UNIQUE INDEX uk_identificacao_global
    ON animal_identificacao(tipo, numero)
    WHERE tipo IN ('RFID', 'SISBOV');

CREATE UNIQUE INDEX uk_identificacao_propriedade_ativa
    ON animal_identificacao(propriedade_id, tipo, numero)
    WHERE ativo AND tipo IN ('BRINCO', 'TATUAGEM', 'OUTRO');

CREATE UNIQUE INDEX uk_identificacao_principal_ativa
    ON animal_identificacao(animal_id) WHERE principal AND ativo;

CREATE UNIQUE INDEX uk_historico_categoria_aberta
    ON historico_categoria_animal(animal_id) WHERE data_fim IS NULL;


COMMENT ON COLUMN animal.codigo_interno IS 'Código cadastral único na propriedade; não é o brinco.';

COMMENT ON COLUMN animal.fornecedor_origem_id IS 'Fornecedor de origem; não substitui o registro de uma compra.';

COMMENT ON COLUMN animal.data_entrada_propriedade IS 'Data de entrada na propriedade atual; atualizar na transferência.';

COMMENT ON COLUMN animal.categoria_id IS 'A aplicação deve manter esta categoria igual à do período aberto, em transação.';

COMMENT ON COLUMN animal_identificacao.propriedade_id IS 'Propriedade atual, sincronizada via FK; não representa histórico de localização.';

COMMENT ON COLUMN historico_categoria_animal.data_fim IS 'Fim exclusivo; NULL representa o período atual aberto.';

COMMENT ON COLUMN categoria_animal.idade_minima_meses IS 'Limite orientativo, sem atualização automática da categoria.';

COMMENT ON COLUMN categoria_animal.idade_maxima_meses IS 'Limite orientativo, sem atualização automática da categoria.';
