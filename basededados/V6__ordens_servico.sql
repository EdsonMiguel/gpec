-- ============================================================
-- GESTÃO PECUÁRIA | BASELINE (edição 12) | PostgreSQL / Flyway
-- V5: Profissionais, tipos de operação, ordens de serviço e vínculos com registros anteriores
-- Migração de INSTALAÇÃO NOVA, não incremental de um banco existente.
-- A integridade e as regras originais foram preservadas.
-- ============================================================



-- ORDENS DE SERVIÇO E REPRODUÇÃO
-- Profissional é independente de usuário/login; admite mais de uma função.
-- OS: uma propriedade, vários profissionais e itens; no máximo um principal.
-- Item: uma operação e, quando aplicável, um animal. Serviços gerais admitem NULL.
-- Não representa manejo coletivo por lote. Guardar os animais efetivos por item.
-- Registros especializados podem existir sem OS. Um item se vincula a no máximo
-- um registro de cada tabela especializada; a aplicação impede reutilização
-- entre tabelas e valida compatibilidade do tipo de operação e do animal.
-- Não concluir item de pesagem/inseminação etc. sem seu registro específico.
-- OS concluída admite itens concluídos/não realizados/cancelados, nunca pendentes.
-- Validar participação do executor na OS e propriedade do animal na data do serviço.
-- Profissão/função cadastrada não representa verificação de habilitação legal.
--
-- Reprodutor externo: animal_id NULL. Reprodutor interno: vínculo único com animal.
-- Sêmen identifica o produto genético; lote_semen identifica partida/recebimento.
-- Não há saldo de doses, movimentação de estoque nem execução de protocolos IATF.
-- Inseminação registra fato realizado. Exames são registros sucessivos, sem
-- sobrescrever resultados anteriores; ausência de exame não equivale a negativo.
-- Gestação admite origem não identificada (inseminacao_id NULL); sua atribuição
-- a uma inseminação deve ser confirmada, nunca presumida pela última tentativa.
-- Parto depende de gestação, permite várias crias e vínculo posterior com animal.
-- Natimortos podem ser registrados sem criação de cadastro no rebanho.
-- Peso ao nascer continua em pesagem, para não duplicar valores.
--
-- VALIDAÇÕES NA APLICAÇÃO, SEM FUNÇÕES/TRIGGERS:
-- * sexo e cronologia: fêmea na inseminação/gestação, macho no reprodutor;
-- * todos os vínculos de diagnóstico/gestação/inseminação referem a mesma fêmea;
-- * exame e confirmação coerentes; positivo pode abrir gestação; perdas são
--   registradas explicitamente, sem deduzi-las de um negativo isolado;
-- * parto, encerramento da gestação, cadastro das crias e genealogia em transação;
-- * compatibilidade da mãe, pai conhecido, data e sexo da cria; evitar ciclos;
-- * pai_reprodutor_id é a única referência paterna; se o reprodutor vincula animal,
--   considerar esse vínculo ao percorrer e validar a árvore genealógica;
-- * impedir alteração de identidade de sêmen/reprodutor/lote com uso histórico;
-- * cronologia prevista/realizada, cancelamentos e mudanças de estado da OS;
-- * profissionais/cadastros ativos para novos lançamentos e updated_at;
-- * controle concorrente das regras entre registros na mesma transação.
-- Previsão de parto é informada/revisada pela aplicação, não calculada neste SQL.

CREATE TABLE tipo_profissional (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo VARCHAR(40) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (btrim(codigo) <> '' AND btrim(nome) <> '')
);



CREATE TABLE pessoa_tipo_profissional (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pessoa_id UUID NOT NULL REFERENCES pessoa(id) ON DELETE RESTRICT,
    tipo_profissional_id UUID NOT NULL REFERENCES tipo_profissional(id) ON DELETE RESTRICT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (pessoa_id, tipo_profissional_id)
);


CREATE TABLE tipo_operacao (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    exige_animal BOOLEAN NOT NULL DEFAULT TRUE,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (btrim(codigo) <> '' AND btrim(nome) <> '')
);


CREATE TABLE ordem_servico (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    propriedade_id UUID NOT NULL REFERENCES propriedade(id) ON DELETE RESTRICT,
    numero VARCHAR(40) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT,
    prioridade VARCHAR(15) NOT NULL DEFAULT 'NORMAL',
    status VARCHAR(20) NOT NULL DEFAULT 'ABERTA',
    data_abertura TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_prevista_inicio TIMESTAMPTZ,
    data_prevista_fim TIMESTAMPTZ,
    data_inicio TIMESTAMPTZ,
    data_conclusao TIMESTAMPTZ,
    data_cancelamento TIMESTAMPTZ,
    motivo_cancelamento TEXT,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (propriedade_id, numero),
    CHECK (btrim(numero) <> '' AND btrim(titulo) <> ''),
    CHECK (prioridade IN ('BAIXA','NORMAL','ALTA','URGENTE')),
    CHECK (status IN ('ABERTA','EM_EXECUCAO','CONCLUIDA','CANCELADA')),
    CHECK (data_prevista_fim IS NULL OR data_prevista_inicio IS NULL OR data_prevista_fim >= data_prevista_inicio),
    CHECK (data_inicio IS NULL OR data_inicio >= data_abertura),
    CHECK (data_conclusao IS NULL OR (data_inicio IS NOT NULL AND data_conclusao >= data_inicio)),
    CHECK (status <> 'CONCLUIDA' OR data_conclusao IS NOT NULL),
    CHECK (status <> 'EM_EXECUCAO' OR data_inicio IS NOT NULL),
    CHECK (status <> 'CANCELADA' OR (data_cancelamento IS NOT NULL AND motivo_cancelamento IS NOT NULL AND btrim(motivo_cancelamento) <> '')),
    CHECK (data_cancelamento IS NULL OR data_cancelamento >= data_abertura)
);


CREATE TABLE ordem_servico_profissional (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ordem_servico_id UUID NOT NULL REFERENCES ordem_servico(id) ON DELETE RESTRICT,
    profissional_id UUID NOT NULL REFERENCES pessoa(id) ON DELETE RESTRICT,
    responsabilidade VARCHAR(150),
    principal BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (ordem_servico_id, profissional_id)
);


CREATE UNIQUE INDEX uk_os_profissional_principal ON ordem_servico_profissional(ordem_servico_id) WHERE principal;


CREATE TABLE ordem_servico_item (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ordem_servico_id UUID NOT NULL REFERENCES ordem_servico(id) ON DELETE RESTRICT,
    sequencia INTEGER NOT NULL,
    tipo_operacao_id UUID NOT NULL REFERENCES tipo_operacao(id) ON DELETE RESTRICT,
    animal_id UUID REFERENCES animal(id) ON DELETE RESTRICT,
    profissional_responsavel_id UUID REFERENCES pessoa(id) ON DELETE RESTRICT,
    descricao TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDENTE',
    data_prevista TIMESTAMPTZ,
    data_inicio TIMESTAMPTZ,
    data_fim TIMESTAMPTZ,
    motivo_nao_realizacao TEXT,
    resultado TEXT,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (ordem_servico_id, sequencia),
    CHECK (sequencia > 0),
    CHECK (status IN ('PENDENTE','EM_EXECUCAO','CONCLUIDO','NAO_REALIZADO','CANCELADO')),
    CHECK (data_fim IS NULL OR (data_inicio IS NOT NULL AND data_fim >= data_inicio)),
    CHECK (status <> 'CONCLUIDO' OR data_fim IS NOT NULL),
    CHECK (status <> 'EM_EXECUCAO' OR data_inicio IS NOT NULL),
    CHECK (status NOT IN ('NAO_REALIZADO','CANCELADO') OR (motivo_nao_realizacao IS NOT NULL AND btrim(motivo_nao_realizacao) <> ''))
);


CREATE INDEX idx_pessoa_tipo_profissional_tipo ON pessoa_tipo_profissional(tipo_profissional_id);


CREATE INDEX idx_os_propriedade_status ON ordem_servico(propriedade_id, status);


CREATE INDEX idx_os_profissional_profissional ON ordem_servico_profissional(profissional_id);


CREATE INDEX idx_os_item_animal ON ordem_servico_item(animal_id);


CREATE INDEX idx_os_item_operacao ON ordem_servico_item(tipo_operacao_id);


CREATE INDEX idx_os_item_profissional ON ordem_servico_item(profissional_responsavel_id);


ALTER TABLE pesagem ADD COLUMN ordem_servico_item_id UUID UNIQUE REFERENCES ordem_servico_item(id) ON DELETE RESTRICT;

ALTER TABLE aplicacao_sanitaria ADD COLUMN ordem_servico_item_id UUID UNIQUE REFERENCES ordem_servico_item(id) ON DELETE RESTRICT;

ALTER TABLE historico_alimentacao ADD COLUMN ordem_servico_item_id UUID UNIQUE REFERENCES ordem_servico_item(id) ON DELETE RESTRICT;


-- Dependências que não podem ser criadas em V3 antes da existência da OS.
-- As colunas e os índices UNIQUE já foram criados em V3; aqui entram somente as FKs.
ALTER TABLE inseminacao
    ADD CONSTRAINT fk_inseminacao_ordem_servico_item
    FOREIGN KEY (ordem_servico_item_id) REFERENCES ordem_servico_item(id) ON DELETE RESTRICT;

ALTER TABLE diagnostico_gestacao
    ADD CONSTRAINT fk_diagnostico_gestacao_ordem_servico_item
    FOREIGN KEY (ordem_servico_item_id) REFERENCES ordem_servico_item(id) ON DELETE RESTRICT;

ALTER TABLE parto
    ADD CONSTRAINT fk_parto_ordem_servico_item
    FOREIGN KEY (ordem_servico_item_id) REFERENCES ordem_servico_item(id) ON DELETE RESTRICT;
