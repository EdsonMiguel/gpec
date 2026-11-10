-- ============================================================
-- GESTÃO PECUÁRIA | BASELINE (edição 12) | PostgreSQL / Flyway
-- V7: Saúde animal: doenças, ocorrências clínicas, avaliações, diagnósticos e vínculo sanitário
-- Migração de INSTALAÇÃO NOVA, não incremental de um banco existente.
-- A integridade e as regras originais foram preservadas.
-- ============================================================


-- ============================================================
-- SAÚDE ANIMAL: OCORRÊNCIAS, AVALIAÇÕES E DIAGNÓSTICOS
-- Etapa inicial: não inclui prescrição/planejamento estruturado de tratamento.
-- Cadastro de doenças é descritivo; não é protocolo clínico nem classificação oficial.
-- Um episódio pode começar sem doença conhecida e ter vários diagnósticos.
-- Reincidência gera NOVA ocorrência, preservando o episódio anterior.
-- Um animal pode ter mais de uma ocorrência em acompanhamento simultaneamente.
-- Cada atendimento gera uma avaliação; mudanças de hipótese são novos registros.
-- Não sobrescrever avaliações anteriores para guardar apenas o último resultado.
-- Situação do episódio é diferente da situação do diagnóstico: descartar uma
-- hipótese não encerra automaticamente a investigação nem significa recuperação.
-- O diagnóstico atual por doença é a última avaliação que a menciona; ausência
-- de menção em uma nova avaliação não equivale a descarte.
-- Desfechos são explícitos; terminar aplicações não implica recuperação.
-- Aplicações preventivas continuam sem ocorrência_clinica_id; aplicações
-- vinculadas devem se referir ao MESMO animal (FK composta).
-- Não criar aplicação duplicada para vincular a doença: usar o registro existente.
-- A OS é opcional tanto na abertura quanto nos atendimentos. O item de OS da
-- aplicação sanitária é seu procedimento, não necessariamente o item da avaliação.
--
-- RESPONSABILIDADES DA APLICAÇÃO, SEM FUNÇÕES/TRIGGERS:
-- * datas realizadas não futuras e >= nascimento; início dos sinais <= abertura;
-- * avaliação >= abertura e <= encerramento quando houver;
-- * produtos aplicados relacionados ao episódio e cronologia compatível;
-- * profissional apto/ativo e tipo de operação compatível nos itens de OS;
-- * animal e propriedade da OS consistentes na data do atendimento;
-- * cadastros ativos para novos registros, mantendo vínculos históricos;
-- * estado ENCERRADA exige desfecho e justificativa; alterações de estado e
--   encerramento sincronizados em transação com as avaliações correspondentes;
-- * óbito não altera animal.status automaticamente; registrar explicitamente,
--   liberar alocação e conciliar outras ocorrências abertas em transação;
-- * preservar autoria profissional e registros clínicos, sem exclusão física
--   de históricos pela aplicação; corrigir com rastreabilidade, sem apagar evolução;
-- * atualizar updated_at; profissional não representa usuário/login.
-- ============================================================
CREATE TABLE doenca (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo VARCHAR(50) NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uk_doenca_codigo UNIQUE (codigo),
    CONSTRAINT uk_doenca_nome UNIQUE (nome),
    CHECK (btrim(codigo) <> '' AND btrim(nome) <> '')
);


CREATE TABLE ocorrencia_clinica (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    animal_id UUID NOT NULL REFERENCES animal(id) ON DELETE RESTRICT,
    ordem_servico_item_id UUID REFERENCES ordem_servico_item(id) ON DELETE RESTRICT,
    titulo VARCHAR(150) NOT NULL,
    data_inicio_sinais TIMESTAMPTZ,
    data_abertura TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    descricao_inicial TEXT NOT NULL,
    status VARCHAR(25) NOT NULL DEFAULT 'ABERTA',
    data_encerramento TIMESTAMPTZ,
    desfecho VARCHAR(30),
    justificativa_encerramento TEXT,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uk_ocorrencia_clinica_animal UNIQUE (id, animal_id),
    CHECK (btrim(titulo) <> '' AND btrim(descricao_inicial) <> ''),
    CHECK (status IN ('ABERTA','EM_ACOMPANHAMENTO','ENCERRADA')),
    CHECK (data_inicio_sinais IS NULL OR data_inicio_sinais <= data_abertura),
    CHECK (data_encerramento IS NULL OR data_encerramento >= data_abertura),
    CHECK (desfecho IS NULL OR desfecho IN ('RECUPERACAO','DIAGNOSTICO_DESCARTADO','TRANSFERENCIA_ACOMPANHAMENTO','OBITO','OUTRO')),
    CHECK ((status = 'ENCERRADA' AND data_encerramento IS NOT NULL
        AND desfecho IS NOT NULL AND justificativa_encerramento IS NOT NULL
        AND btrim(justificativa_encerramento) <> '')
        OR (status <> 'ENCERRADA' AND data_encerramento IS NULL
        AND desfecho IS NULL AND justificativa_encerramento IS NULL))
);


CREATE TABLE avaliacao_clinica (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ocorrencia_clinica_id UUID NOT NULL REFERENCES ocorrencia_clinica(id) ON DELETE RESTRICT,
    profissional_id UUID NOT NULL REFERENCES pessoa(id) ON DELETE RESTRICT,
    ordem_servico_item_id UUID UNIQUE REFERENCES ordem_servico_item(id) ON DELETE RESTRICT,
    data_avaliacao TIMESTAMPTZ NOT NULL,
    sintomas TEXT,
    achados TEXT,
    conclusao TEXT,
    conduta TEXT,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (sintomas IS NOT NULL OR achados IS NOT NULL OR conclusao IS NOT NULL OR conduta IS NOT NULL)
);


CREATE TABLE avaliacao_clinica_diagnostico (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    avaliacao_clinica_id UUID NOT NULL REFERENCES avaliacao_clinica(id) ON DELETE RESTRICT,
    doenca_id UUID NOT NULL REFERENCES doenca(id) ON DELETE RESTRICT,
    situacao VARCHAR(15) NOT NULL,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uk_avaliacao_clinica_doenca UNIQUE (avaliacao_clinica_id, doenca_id),
    CHECK (situacao IN ('SUSPEITO','CONFIRMADO','DESCARTADO','INCONCLUSIVO'))
);


CREATE INDEX idx_ocorrencia_clinica_animal_data ON ocorrencia_clinica(animal_id, data_abertura DESC);


CREATE INDEX idx_ocorrencia_clinica_status ON ocorrencia_clinica(status);


CREATE INDEX idx_ocorrencia_clinica_os ON ocorrencia_clinica(ordem_servico_item_id);


CREATE INDEX idx_avaliacao_clinica_ocorrencia_data ON avaliacao_clinica(ocorrencia_clinica_id, data_avaliacao DESC);


CREATE INDEX idx_avaliacao_clinica_profissional ON avaliacao_clinica(profissional_id);


CREATE INDEX idx_diagnostico_clinico_doenca ON avaliacao_clinica_diagnostico(doenca_id);

ALTER TABLE aplicacao_sanitaria ADD COLUMN ocorrencia_clinica_id UUID;

ALTER TABLE aplicacao_sanitaria ADD CONSTRAINT fk_aplicacao_sanitaria_ocorrencia
    FOREIGN KEY (ocorrencia_clinica_id, animal_id)
    REFERENCES ocorrencia_clinica(id, animal_id) ON DELETE RESTRICT;

COMMENT ON COLUMN ocorrencia_clinica.desfecho IS 'Resultado explícito do episódio; independente do status dos diagnósticos.';

COMMENT ON COLUMN avaliacao_clinica.conduta IS 'Orientação registrada pelo profissional; não gera aplicações automaticamente.';
