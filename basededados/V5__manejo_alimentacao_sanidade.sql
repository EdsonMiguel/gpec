-- ============================================================
-- GESTÃO PECUÁRIA | BASELINE (edição 12) | PostgreSQL / Flyway
-- V4: Manejo individual, pesagens, produtos sanitários, aplicações e alimentação
-- Migração de INSTALAÇÃO NOVA, não incremental de um banco existente.
-- A integridade e as regras originais foram preservadas.
-- ============================================================



-- ============================================================
-- MOTIVO DA PESAGEM
-- Cadastro configurável; não depende de enum ou lógica no banco.
-- ============================================================
CREATE TABLE motivo_pesagem (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo VARCHAR(40) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uk_motivo_pesagem_codigo UNIQUE (codigo),
    CONSTRAINT uk_motivo_pesagem_nome UNIQUE (nome),
    CONSTRAINT ck_motivo_pesagem_codigo CHECK (btrim(codigo) <> ''),
    CONSTRAINT ck_motivo_pesagem_nome CHECK (btrim(nome) <> '')
);


-- ============================================================
-- PESAGEM
-- Um animal possui várias pesagens e cada pesagem possui um motivo.
-- Data/hora permite mais de uma medição no mesmo dia.
-- Peso em kg; duas casas decimais. Motivos usados devem ser inativados,
-- não excluídos, para preservar o histórico.
-- A aplicação valida: data >= nascimento, ausência de data futura,
-- motivo ativo para novos lançamentos e compatibilidade do motivo com
-- sexo/estado reprodutivo (por exemplo, pré-parto só para fêmea gestante).
-- Também atualiza updated_at e controla duplicidades de lançamento.
-- ============================================================
CREATE TABLE pesagem (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    animal_id UUID NOT NULL,
    motivo_pesagem_id UUID NOT NULL,
    peso_kg NUMERIC(7,2) NOT NULL,
    data_pesagem TIMESTAMPTZ NOT NULL,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pesagem_animal FOREIGN KEY (animal_id)
        REFERENCES animal(id) ON DELETE RESTRICT,
    CONSTRAINT fk_pesagem_motivo FOREIGN KEY (motivo_pesagem_id)
        REFERENCES motivo_pesagem(id) ON DELETE RESTRICT,
    CONSTRAINT ck_pesagem_peso CHECK (peso_kg > 0 AND peso_kg < 100000)
);


CREATE INDEX idx_pesagem_animal_data ON pesagem(animal_id, data_pesagem DESC);

CREATE INDEX idx_pesagem_motivo ON pesagem(motivo_pesagem_id);

COMMENT ON COLUMN pesagem.peso_kg IS 'Peso medido em quilogramas, com duas casas decimais.';

COMMENT ON COLUMN pesagem.data_pesagem IS 'Instante da medição; informar fuso horário ao enviar pela aplicação.';


-- ============================================================
-- TIPO DE PRODUTO SANITÁRIO
-- Classificação principal: vacina, vermífugo, medicamento etc.
-- Cadastro aberto para novos tipos, sem enum fixo no banco.
-- ============================================================
CREATE TABLE tipo_produto_sanitario (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo VARCHAR(40) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uk_tipo_produto_sanitario_codigo UNIQUE (codigo),
    CONSTRAINT uk_tipo_produto_sanitario_nome UNIQUE (nome),
    CONSTRAINT ck_tipo_produto_sanitario_codigo CHECK (btrim(codigo) <> ''),
    CONSTRAINT ck_tipo_produto_sanitario_nome CHECK (btrim(nome) <> '')
);


-- ============================================================
-- PRODUTO SANITÁRIO
-- Um registro por produto/formulação/apresentação. O tipo é a classificação
-- principal; vermífugos também são medicamentos em sentido amplo.
-- Não reutilizar um cadastro para uma formulação diferente: criar outro
-- produto e inativar o anterior, preservando o significado do histórico.
-- Lote e validade pertencem à aplicação, não ao cadastro do produto.
-- Esta estrutura não controla estoque, protocolos ou prescrição.
-- ============================================================
CREATE TABLE produto_sanitario (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tipo_produto_sanitario_id UUID NOT NULL,
    codigo VARCHAR(50) NOT NULL,
    nome VARCHAR(150) NOT NULL,
    fabricante_id UUID REFERENCES pessoa(id) ON DELETE RESTRICT,
    principio_ativo TEXT,
    concentracao VARCHAR(100),
    apresentacao VARCHAR(150),
    unidade_medida VARCHAR(20) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_produto_sanitario_tipo FOREIGN KEY (tipo_produto_sanitario_id)
        REFERENCES tipo_produto_sanitario(id) ON DELETE RESTRICT,
    CONSTRAINT uk_produto_sanitario_codigo UNIQUE (codigo),
    CONSTRAINT ck_produto_sanitario_codigo CHECK (btrim(codigo) <> ''),
    CONSTRAINT ck_produto_sanitario_nome CHECK (btrim(nome) <> ''),
    CONSTRAINT ck_produto_sanitario_unidade CHECK (btrim(unidade_medida) <> '')
);


-- ============================================================
-- HISTÓRICO DE APLICAÇÃO SANITÁRIA
-- Um registro = um produto efetivamente aplicado a um animal em um instante.
-- Vários produtos no mesmo manejo geram registros separados.
-- quantidade_aplicada é o total aplicado (ex.: 5 mL), não dose por kg.
-- unidade_medida é copiada pela aplicação para preservar a unidade histórica.
-- numero_dose é a sequência opcional no protocolo (1ª dose, reforço etc.).
-- lote_fabricante é o lote do produto, sem relação com o lote de animais.
-- Responsável vinculado ao cadastro único de pessoas.
--
-- Responsabilidades da aplicação: compatibilidade/segurança do produto,
-- validar produto/tipo ativos em novos lançamentos, unidade e quantidade,
-- data de aplicação >= nascimento e não futura, validade do produto na data,
-- necessidade de prescrição, intervalos e carências aplicáveis e atualização
-- de updated_at. Não há cálculo de dose, protocolo ou carência neste SQL.
-- Datas de próxima aplicação são previsão, não comprovante de aplicação.
-- ============================================================
CREATE TABLE aplicacao_sanitaria (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    animal_id UUID NOT NULL,
    produto_sanitario_id UUID NOT NULL,
    data_aplicacao TIMESTAMPTZ NOT NULL,
    quantidade_aplicada NUMERIC(10,3) NOT NULL,
    unidade_medida VARCHAR(20) NOT NULL,
    via_aplicacao VARCHAR(50),
    numero_dose INTEGER,
    lote_fabricante VARCHAR(60),
    data_validade_produto DATE,
    motivo VARCHAR(200),
    responsavel_aplicacao_id UUID REFERENCES pessoa(id) ON DELETE RESTRICT,
    data_proxima_aplicacao TIMESTAMPTZ,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_aplicacao_sanitaria_animal FOREIGN KEY (animal_id)
        REFERENCES animal(id) ON DELETE RESTRICT,
    CONSTRAINT fk_aplicacao_sanitaria_produto FOREIGN KEY (produto_sanitario_id)
        REFERENCES produto_sanitario(id) ON DELETE RESTRICT,
    CONSTRAINT ck_aplicacao_sanitaria_quantidade
        CHECK (quantidade_aplicada > 0 AND quantidade_aplicada < 10000000),
    CONSTRAINT ck_aplicacao_sanitaria_unidade CHECK (btrim(unidade_medida) <> ''),
    CONSTRAINT ck_aplicacao_sanitaria_numero_dose
        CHECK (numero_dose IS NULL OR numero_dose > 0),
    CONSTRAINT ck_aplicacao_sanitaria_proxima
        CHECK (data_proxima_aplicacao IS NULL OR data_proxima_aplicacao > data_aplicacao)
);


CREATE INDEX idx_produto_sanitario_tipo ON produto_sanitario(tipo_produto_sanitario_id);

CREATE INDEX idx_aplicacao_sanitaria_animal_data ON aplicacao_sanitaria(animal_id, data_aplicacao DESC);

CREATE INDEX idx_aplicacao_sanitaria_produto_data ON aplicacao_sanitaria(produto_sanitario_id, data_aplicacao DESC);

CREATE INDEX idx_aplicacao_sanitaria_proxima ON aplicacao_sanitaria(data_proxima_aplicacao)
    WHERE data_proxima_aplicacao IS NOT NULL;

COMMENT ON COLUMN produto_sanitario.unidade_medida IS 'Unidade usual do cadastro, por exemplo ML, G ou DOSE. Padronizada pela aplicação.';

COMMENT ON COLUMN aplicacao_sanitaria.quantidade_aplicada IS 'Quantidade total efetivamente aplicada; não é dose por quilograma.';

COMMENT ON COLUMN aplicacao_sanitaria.unidade_medida IS 'Unidade usada nesta aplicação, preservada independentemente do cadastro atual.';

COMMENT ON COLUMN aplicacao_sanitaria.data_proxima_aplicacao IS 'Previsão opcional; não substitui um novo registro de aplicação realizada.';


-- ============================================================
-- TIPO DE ALIMENTO
-- Classificação principal: ração, volumoso, suplemento, ingrediente etc.
-- Novos tipos podem ser cadastrados sem alterar a estrutura.
-- ============================================================
CREATE TABLE tipo_alimento (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo VARCHAR(40) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uk_tipo_alimento_codigo UNIQUE (codigo),
    CONSTRAINT uk_tipo_alimento_nome UNIQUE (nome),
    CONSTRAINT ck_tipo_alimento_codigo CHECK (btrim(codigo) <> ''),
    CONSTRAINT ck_tipo_alimento_nome CHECK (btrim(nome) <> '')
);


-- ============================================================
-- ALIMENTO
-- Cadastro de rações, volumosos, suplementos e ingredientes.
-- fabricante é opcional: o alimento pode ser produzido na propriedade.
-- unidade_medida define a unidade usual (ex.: KG, G, L); a aplicação
-- padroniza as unidades e realiza eventuais conversões.
-- composicao é descritiva; não representa uma formulação estruturada.
-- Não reutilizar o cadastro para outro alimento/formulação: cadastrar
-- um novo item e inativar o anterior, preservando o histórico existente.
-- ============================================================
CREATE TABLE alimento (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tipo_alimento_id UUID NOT NULL,
    codigo VARCHAR(50) NOT NULL,
    nome VARCHAR(150) NOT NULL,
    fabricante_id UUID REFERENCES pessoa(id) ON DELETE RESTRICT,
    apresentacao VARCHAR(150),
    composicao TEXT,
    unidade_medida VARCHAR(20) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_alimento_tipo FOREIGN KEY (tipo_alimento_id)
        REFERENCES tipo_alimento(id) ON DELETE RESTRICT,
    CONSTRAINT uk_alimento_codigo UNIQUE (codigo),
    CONSTRAINT ck_alimento_codigo CHECK (btrim(codigo) <> ''),
    CONSTRAINT ck_alimento_nome CHECK (btrim(nome) <> ''),
    CONSTRAINT ck_alimento_unidade CHECK (btrim(unidade_medida) <> '')
);


-- ============================================================
-- HISTÓRICO DE ALIMENTAÇÃO INDIVIDUAL
-- Cada registro = um alimento efetivamente fornecido a um animal.
-- Vários alimentos na mesma refeição geram registros separados.
-- Permite vários fornecimentos no mesmo dia, inclusive do mesmo alimento.
-- quantidade_fornecida é o total ofertado ao animal, na unidade registrada;
-- não é quantidade por kg de peso vivo, por lote de animais ou consumo medido.
-- Não duplicar uma quantidade coletiva como se fosse individual; fornecimento
-- por lote exigirá modelagem própria. Este cadastro não controla estoque,
-- dieta prescrita, sobras, matéria seca ou consumo efetivo.
--
-- A aplicação valida: animal e alimento disponíveis para o lançamento,
-- tipo/alimento ativos em novos registros, data >= nascimento e não futura,
-- unidade/quantidade compatíveis, validade quando informada, duplicidades
-- e atualização de updated_at. Sem funções ou triggers próprias.
-- ============================================================
CREATE TABLE historico_alimentacao (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    animal_id UUID NOT NULL,
    alimento_id UUID NOT NULL,
    data_fornecimento TIMESTAMPTZ NOT NULL,
    quantidade_fornecida NUMERIC(12,3) NOT NULL,
    unidade_medida VARCHAR(20) NOT NULL,
    lote_alimento VARCHAR(60),
    data_validade_alimento DATE,
    responsavel_fornecimento_id UUID REFERENCES pessoa(id) ON DELETE RESTRICT,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_historico_alimentacao_animal FOREIGN KEY (animal_id)
        REFERENCES animal(id) ON DELETE RESTRICT,
    CONSTRAINT fk_historico_alimentacao_alimento FOREIGN KEY (alimento_id)
        REFERENCES alimento(id) ON DELETE RESTRICT,
    CONSTRAINT ck_historico_alimentacao_quantidade
        CHECK (quantidade_fornecida > 0 AND quantidade_fornecida < 1000000000),
    CONSTRAINT ck_historico_alimentacao_unidade CHECK (btrim(unidade_medida) <> '')
);


CREATE INDEX idx_alimento_tipo ON alimento(tipo_alimento_id);

CREATE INDEX idx_historico_alimentacao_animal_data ON historico_alimentacao(animal_id, data_fornecimento DESC);

CREATE INDEX idx_historico_alimentacao_alimento_data ON historico_alimentacao(alimento_id, data_fornecimento DESC);

COMMENT ON COLUMN alimento.unidade_medida IS 'Unidade usual do alimento; valores padronizados pela aplicação, por exemplo KG, G ou L.';

COMMENT ON COLUMN historico_alimentacao.quantidade_fornecida IS 'Quantidade ofertada individualmente, não o consumo efetivo nem quantidade coletiva do lote.';

COMMENT ON COLUMN historico_alimentacao.unidade_medida IS 'Unidade usada neste fornecimento, preservada independentemente do cadastro atual.';

COMMENT ON COLUMN historico_alimentacao.lote_alimento IS 'Lote de fabricação ou produção do alimento; não é o lote de manejo dos animais.';

COMMENT ON COLUMN historico_alimentacao.responsavel_fornecimento_id IS 'Cadastro único da pessoa responsável; papel validado pela aplicação.';

CREATE INDEX idx_aplicacao_sanitaria_ocorrencia ON aplicacao_sanitaria(ocorrencia_clinica_id);

COMMENT ON COLUMN aplicacao_sanitaria.ocorrencia_clinica_id IS 'Vínculo opcional ao episódio do mesmo animal; NULL em aplicações independentes/preventivas.';

CREATE INDEX idx_produto_sanitario_fabricante_id ON produto_sanitario(fabricante_id);

CREATE INDEX idx_alimento_fabricante_id ON alimento(fabricante_id);

CREATE INDEX idx_aplicacao_sanitaria_responsavel_aplicacao_id ON aplicacao_sanitaria(responsavel_aplicacao_id);

CREATE INDEX idx_historico_alimentacao_responsavel_fornecimento_id ON historico_alimentacao(responsavel_fornecimento_id);
