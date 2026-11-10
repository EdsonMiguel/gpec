-- ============================================================
-- GESTÃO PECUÁRIA | BASELINE (edição 12) | PostgreSQL / Flyway
-- V3: Reprodução, genealogia, sêmen, inseminações, gestações, diagnósticos, partos e crias
-- Migração de INSTALAÇÃO NOVA, não incremental de um banco existente.
-- A integridade e as regras originais foram preservadas.
-- ============================================================




-- ============================================================
-- GENEALOGIA
-- ============================================================

CREATE TABLE reprodutor (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    animal_id UUID UNIQUE REFERENCES animal(id) ON DELETE RESTRICT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(150) NOT NULL,
    registro_genealogico VARCHAR(80),
    entidade_registro_id UUID REFERENCES pessoa(id) ON DELETE RESTRICT,
    raca_id UUID REFERENCES raca(id) ON DELETE RESTRICT,
    origem VARCHAR(150),
    observacao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (btrim(codigo) <> '' AND btrim(nome) <> '')
);


CREATE TABLE animal_genealogia (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    animal_id UUID NOT NULL UNIQUE REFERENCES animal(id) ON DELETE RESTRICT,
    pai_reprodutor_id UUID REFERENCES reprodutor(id) ON DELETE RESTRICT,
    mae_genetica_id UUID REFERENCES animal(id) ON DELETE RESTRICT,
    origem_paternidade VARCHAR(25),
    situacao_paternidade VARCHAR(15),
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (mae_genetica_id IS NULL OR mae_genetica_id <> animal_id),
    CHECK (origem_paternidade IS NULL OR origem_paternidade IN ('INFORMADA','INSEMINACAO','EXAME_GENETICO')),
    CHECK (situacao_paternidade IS NULL OR situacao_paternidade IN ('ATRIBUIDA','CONFIRMADA')),
    CHECK ((pai_reprodutor_id IS NULL AND origem_paternidade IS NULL AND situacao_paternidade IS NULL)
        OR (pai_reprodutor_id IS NOT NULL AND origem_paternidade IS NOT NULL AND situacao_paternidade IS NOT NULL))
);


CREATE INDEX idx_genealogia_mae
    ON animal_genealogia(mae_genetica_id);




CREATE TABLE semen (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    reprodutor_id UUID NOT NULL REFERENCES reprodutor(id) ON DELETE RESTRICT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(150) NOT NULL,
    codigo_catalogo VARCHAR(80),
    central_id UUID REFERENCES pessoa(id) ON DELETE RESTRICT,
    tipo VARCHAR(25) NOT NULL DEFAULT 'CONVENCIONAL',
    observacao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (btrim(codigo) <> '' AND btrim(nome) <> ''),
    CHECK (tipo IN ('CONVENCIONAL','SEXADO_FEMEA','SEXADO_MACHO','NAO_INFORMADO'))
);


CREATE TABLE lote_semen (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    semen_id UUID NOT NULL REFERENCES semen(id) ON DELETE RESTRICT,
    codigo VARCHAR(60) NOT NULL,
    partida VARCHAR(80),
    fornecedor_id UUID REFERENCES pessoa(id) ON DELETE RESTRICT,
    data_coleta DATE,
    data_recebimento DATE,
    local_armazenamento VARCHAR(150),
    observacao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (semen_id, codigo),
    CHECK (btrim(codigo) <> ''),
    CHECK (data_recebimento IS NULL OR data_coleta IS NULL OR data_recebimento >= data_coleta)
);


CREATE TABLE inseminacao (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    animal_id UUID NOT NULL REFERENCES animal(id) ON DELETE RESTRICT,
    lote_semen_id UUID NOT NULL REFERENCES lote_semen(id) ON DELETE RESTRICT,
    profissional_id UUID NOT NULL REFERENCES pessoa(id) ON DELETE RESTRICT,
    ordem_servico_item_id UUID UNIQUE,
    data_inseminacao TIMESTAMPTZ NOT NULL,
    modalidade VARCHAR(25) NOT NULL,
    quantidade_doses INTEGER NOT NULL DEFAULT 1,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (modalidade IN ('CIO_OBSERVADO','IATF')),
    CHECK (quantidade_doses > 0)
);


CREATE TABLE gestacao (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    matriz_id UUID NOT NULL REFERENCES animal(id) ON DELETE RESTRICT,
    inseminacao_id UUID UNIQUE REFERENCES inseminacao(id) ON DELETE RESTRICT,
    data_confirmacao DATE NOT NULL,
    data_prevista_parto DATE,
    status VARCHAR(25) NOT NULL DEFAULT 'EM_ANDAMENTO',
    data_encerramento DATE,
    motivo_encerramento TEXT,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (status IN ('EM_ANDAMENTO','ENCERRADA_PARTO','PERDA_GESTACIONAL','ENCERRADA_OUTRO')),
    CHECK (data_prevista_parto IS NULL OR data_prevista_parto >= data_confirmacao),
    CHECK (data_encerramento IS NULL OR data_encerramento >= data_confirmacao),
    CHECK ((status = 'EM_ANDAMENTO' AND data_encerramento IS NULL) OR (status <> 'EM_ANDAMENTO' AND data_encerramento IS NOT NULL))
);


CREATE UNIQUE INDEX uk_gestacao_em_andamento ON gestacao(matriz_id) WHERE status = 'EM_ANDAMENTO';


CREATE TABLE diagnostico_gestacao (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    animal_id UUID NOT NULL REFERENCES animal(id) ON DELETE RESTRICT,
    inseminacao_id UUID REFERENCES inseminacao(id) ON DELETE RESTRICT,
    gestacao_id UUID REFERENCES gestacao(id) ON DELETE RESTRICT,
    profissional_id UUID NOT NULL REFERENCES pessoa(id) ON DELETE RESTRICT,
    ordem_servico_item_id UUID UNIQUE,
    data_diagnostico TIMESTAMPTZ NOT NULL,
    resultado VARCHAR(15) NOT NULL,
    metodo VARCHAR(100),
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (resultado IN ('POSITIVO','NEGATIVO','INCONCLUSIVO'))
);


CREATE TABLE parto (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    gestacao_id UUID NOT NULL UNIQUE REFERENCES gestacao(id) ON DELETE RESTRICT,
    profissional_id UUID REFERENCES pessoa(id) ON DELETE RESTRICT,
    ordem_servico_item_id UUID UNIQUE,
    data_parto TIMESTAMPTZ NOT NULL,
    assistido BOOLEAN NOT NULL DEFAULT FALSE,
    ocorrencia TEXT,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE parto_cria (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    parto_id UUID NOT NULL REFERENCES parto(id) ON DELETE RESTRICT,
    sequencia INTEGER NOT NULL,
    animal_id UUID UNIQUE REFERENCES animal(id) ON DELETE RESTRICT,
    sexo_sem_cadastro VARCHAR(10),
    condicao_nascimento VARCHAR(15) NOT NULL,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (parto_id, sequencia),
    CHECK (sequencia > 0),
    CHECK (sexo_sem_cadastro IS NULL OR sexo_sem_cadastro IN ('MACHO','FEMEA')),
    CHECK (condicao_nascimento IN ('NASCIDO_VIVO','NATIMORTO')),
    CHECK (animal_id IS NULL OR sexo_sem_cadastro IS NULL)
);


CREATE INDEX idx_reprodutor_raca ON reprodutor(raca_id);


CREATE INDEX idx_semen_reprodutor ON semen(reprodutor_id);


CREATE INDEX idx_lote_semen_fornecedor ON lote_semen(fornecedor_id);


CREATE INDEX idx_inseminacao_animal_data ON inseminacao(animal_id, data_inseminacao DESC);


CREATE INDEX idx_inseminacao_lote ON inseminacao(lote_semen_id);


CREATE INDEX idx_inseminacao_profissional ON inseminacao(profissional_id);


CREATE INDEX idx_gestacao_animal ON gestacao(matriz_id);


CREATE INDEX idx_diagnostico_animal_data ON diagnostico_gestacao(animal_id, data_diagnostico DESC);


CREATE INDEX idx_diagnostico_inseminacao ON diagnostico_gestacao(inseminacao_id);


CREATE INDEX idx_diagnostico_gestacao ON diagnostico_gestacao(gestacao_id);


CREATE INDEX idx_diagnostico_profissional ON diagnostico_gestacao(profissional_id);


CREATE INDEX idx_parto_profissional ON parto(profissional_id);

CREATE INDEX idx_genealogia_pai_reprodutor ON animal_genealogia(pai_reprodutor_id);

CREATE INDEX idx_reprodutor_entidade_registro_id ON reprodutor(entidade_registro_id);

CREATE INDEX idx_semen_central_id ON semen(central_id);

COMMENT ON COLUMN gestacao.matriz_id IS 'Fêmea que gestou; pode ser diferente da mãe genética.';

COMMENT ON COLUMN animal_genealogia.mae_genetica_id IS 'Mãe genética; não inferir automaticamente da receptora.';

COMMENT ON COLUMN parto_cria.sexo_sem_cadastro IS 'Usar somente quando animal_id for NULL; caso contrário consultar animal.sexo.';
