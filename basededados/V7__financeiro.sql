-- ============================================================
-- GESTÃO PECUÁRIA | BASELINE (edição 12) | PostgreSQL / Flyway
-- V6: Financeiro gerencial: categorias, contas, formas de pagamento, títulos, parcelas e baixas
-- Migração de INSTALAÇÃO NOVA, não incremental de um banco existente.
-- A integridade e as regras originais foram preservadas.
-- ============================================================


-- ============================================================
-- FINANCEIRO GERENCIAL BÁSICO — BRL
-- Não representa escrituração contábil/fiscal nem custo de produção por animal.
-- Não gera títulos automaticamente ao concluir OS. Integrações futuras usam
-- origem + chave_origem, únicos por titular, e o vínculo explícito ordem_servico_id.
-- Clientes e credores usam pessoa; não é necessário um cadastro de fornecedor
-- para existir título. Fornecedor é um papel de pessoa física ou jurídica.
-- Titular pode ser PF/PJ; autoria profissional exige PF habilitada na aplicação.
--
-- SALDOS, ESTADOS E BAIXAS:
-- Parcela não armazena valor pago/saldo nem status de atraso independente.
-- Principal liquidado = soma(valor_principal) das baixas EFETIVAS.
-- Saldo = valor_original - principal liquidado; quitada se saldo = 0;
-- parcial se 0 < principal liquidado < valor_original; vencida se saldo > 0
-- e data_vencimento < data de referência. Considerar apenas títulos CONFIRMADOS.
-- Rascunhos não são exigíveis e títulos cancelados não entram no fluxo previsto.
-- valor_principal é quanto da dívida foi liquidado, INCLUINDO a parte descontada.
-- valor_movimentado = valor_principal + juros + multa - desconto.
-- Ex.: principal 300, juros 10, desconto 5 => liquida 300 e movimenta 305.
-- Desconto integral pode liquidar principal com movimento zero, de forma explícita.
-- Moeda única BRL. Calcular com BigDecimal/Decimal, nunca float/double na aplicação.
--
-- ESTORNO: preservar valores e data originais; marcar ESTORNADA com data e motivo.
-- Baixa estornada não compõe a liquidação atual. Correção gera nova baixa com
-- outro UUID/chave. Para consulta de posição passada, considerar a data do
-- estorno (não simplesmente o status atual). Estorno de lançamento não prova
-- devolução bancária: fluxo é gerencial e não conciliado com extrato.
-- Uma baixa liquida uma parcela; pagamento agrupado deve ser desmembrado.
-- Não há pagamento somente de encargos nem adiantamento sem parcela nesta etapa.
--
-- REGRAS TRANSACIONAIS DA APLICAÇÃO (SEM TRIGGERS/FUNÇÕES):
-- * soma das parcelas = valor_total ao confirmar; rascunho admite incompletude;
-- * lock da parcela (SELECT ... FOR UPDATE), leitura das baixas válidas e
--   validação de saldo antes de pagar/receber/estornar; todos os escritores
--   obedecem à mesma ordem de locks. Não permitir saldo negativo/superliquidação;
-- * lock do título também para confirmação/cancelamento e mudanças de parcelas;
--   não cancelar título com baixa efetiva; estornar antes, com motivo;
-- * confirmação bloqueia edição livre dos valores; correções são explícitas;
-- * nenhuma baixa em rascunho/cancelado; impedir datas realizadas futuras;
-- * data_estorno >= data_baixa; validar cronologia das operações;
-- * validar categoria/conta/pessoa/formas ativas em novos lançamentos;
-- * impedir ciclos de categorias, inclusive ao editar a hierarquia;
-- * OS e propriedade do título pertencem ao titular informado;
-- * chave_origem e chave_idempotencia são estáveis, geradas pelo chamador e
--   reutilizadas nas repetições; conflito deve verificar o conteúdo original;
-- * preservar baixas e títulos confirmados: sem exclusão física na aplicação;
-- * atualizar updated_at, sem identificação de usuário por enquanto.
-- pessoa_titular_id em parcela/baixa é derivado do título, não escolhido separadamente;
-- FKs compostas impedem vincular a baixa a uma conta de outro titular.
-- Não há saldo bancário, saldo inicial, transferências, estoque, rateio ou
-- apropriação por animal. Relatórios mostram movimentos/obrigações registrados.
-- Não cadastrar transferência entre contas como receita/despesa.
-- ============================================================
CREATE TABLE categoria_financeira (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    natureza VARCHAR(10) NOT NULL,
    categoria_pai_id UUID,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uk_categoria_financeira_id_natureza UNIQUE (id, natureza),
    CONSTRAINT fk_categoria_financeira_pai FOREIGN KEY (categoria_pai_id, natureza)
        REFERENCES categoria_financeira(id, natureza) ON DELETE RESTRICT,
    CHECK (natureza IN ('PAGAR','RECEBER')),
    CHECK (btrim(codigo) <> '' AND btrim(nome) <> ''),
    CHECK (categoria_pai_id IS NULL OR categoria_pai_id <> id)
);


CREATE TABLE conta_financeira (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pessoa_titular_id UUID NOT NULL REFERENCES pessoa(id) ON DELETE RESTRICT,
    codigo VARCHAR(40) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo VARCHAR(15) NOT NULL,
    moeda CHAR(3) NOT NULL DEFAULT 'BRL',
    instituicao_financeira_id UUID REFERENCES pessoa(id) ON DELETE RESTRICT,
    agencia VARCHAR(20),
    numero_conta VARCHAR(30),
    observacao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uk_conta_financeira_codigo UNIQUE (pessoa_titular_id, codigo),
    CONSTRAINT uk_conta_financeira_titular UNIQUE (id, pessoa_titular_id),
    CHECK (tipo IN ('CAIXA','BANCARIA')),
    CHECK (moeda = 'BRL'),
    CHECK (btrim(codigo) <> '' AND btrim(nome) <> '')
);


CREATE TABLE forma_pagamento (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo VARCHAR(30) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (btrim(codigo) <> '' AND btrim(nome) <> '')
);


CREATE TABLE titulo_financeiro (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pessoa_titular_id UUID NOT NULL REFERENCES pessoa(id) ON DELETE RESTRICT,
    propriedade_id UUID,
    pessoa_id UUID NOT NULL REFERENCES pessoa(id) ON DELETE RESTRICT,
    categoria_financeira_id UUID NOT NULL,
    natureza VARCHAR(10) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    numero_documento VARCHAR(80),
    data_emissao DATE NOT NULL,
    data_competencia DATE NOT NULL,
    valor_total NUMERIC(14,2) NOT NULL,
    moeda CHAR(3) NOT NULL DEFAULT 'BRL',
    status VARCHAR(15) NOT NULL DEFAULT 'RASCUNHO',
    data_cancelamento DATE,
    motivo_cancelamento TEXT,
    origem VARCHAR(40) NOT NULL DEFAULT 'MANUAL',
    chave_origem VARCHAR(150),
    ordem_servico_id UUID REFERENCES ordem_servico(id) ON DELETE RESTRICT,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uk_titulo_financeiro_titular UNIQUE (id, pessoa_titular_id),
    CONSTRAINT uk_titulo_financeiro_origem UNIQUE (pessoa_titular_id, origem, chave_origem),
    CONSTRAINT fk_titulo_financeiro_propriedade FOREIGN KEY (pessoa_titular_id, propriedade_id)
        REFERENCES propriedade(pessoa_titular_id, id) ON DELETE RESTRICT,
    CONSTRAINT fk_titulo_financeiro_categoria FOREIGN KEY (categoria_financeira_id, natureza)
        REFERENCES categoria_financeira(id, natureza) ON DELETE RESTRICT,
    CHECK (natureza IN ('PAGAR','RECEBER')),
    CHECK (status IN ('RASCUNHO','CONFIRMADO','CANCELADO')),
    CHECK (moeda = 'BRL'),
    CHECK (valor_total > 0 AND valor_total < 1000000000000),
    CHECK (btrim(descricao) <> '' AND btrim(origem) <> ''),
    CHECK (chave_origem IS NULL OR btrim(chave_origem) <> ''),
    CHECK (origem = 'MANUAL' OR chave_origem IS NOT NULL),
    CHECK ((status = 'CANCELADO' AND data_cancelamento IS NOT NULL
        AND motivo_cancelamento IS NOT NULL AND btrim(motivo_cancelamento) <> '')
        OR (status <> 'CANCELADO' AND data_cancelamento IS NULL AND motivo_cancelamento IS NULL)),
    CHECK (data_cancelamento IS NULL OR data_cancelamento >= data_emissao)
);


CREATE TABLE parcela_financeira (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    titulo_financeiro_id UUID NOT NULL,
    pessoa_titular_id UUID NOT NULL,
    numero INTEGER NOT NULL,
    data_vencimento DATE NOT NULL,
    valor_original NUMERIC(14,2) NOT NULL,
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uk_parcela_financeira_numero UNIQUE (titulo_financeiro_id, numero),
    CONSTRAINT uk_parcela_financeira_titular UNIQUE (id, pessoa_titular_id),
    CONSTRAINT fk_parcela_financeira_titulo FOREIGN KEY (titulo_financeiro_id, pessoa_titular_id)
        REFERENCES titulo_financeiro(id, pessoa_titular_id) ON DELETE RESTRICT,
    CHECK (numero > 0),
    CHECK (valor_original > 0 AND valor_original < 1000000000000)
);


CREATE TABLE baixa_financeira (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    parcela_financeira_id UUID NOT NULL,
    pessoa_titular_id UUID NOT NULL,
    conta_financeira_id UUID NOT NULL,
    forma_pagamento_id UUID NOT NULL REFERENCES forma_pagamento(id) ON DELETE RESTRICT,
    chave_idempotencia VARCHAR(150) NOT NULL,
    data_baixa DATE NOT NULL,
    valor_principal NUMERIC(14,2) NOT NULL,
    valor_juros NUMERIC(14,2) NOT NULL DEFAULT 0,
    valor_multa NUMERIC(14,2) NOT NULL DEFAULT 0,
    valor_desconto NUMERIC(14,2) NOT NULL DEFAULT 0,
    valor_movimentado NUMERIC(14,2) NOT NULL,
    status VARCHAR(15) NOT NULL DEFAULT 'EFETIVA',
    data_estorno DATE,
    motivo_estorno TEXT,
    referencia_comprovante VARCHAR(150),
    observacao TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uk_baixa_financeira_idempotencia UNIQUE (pessoa_titular_id, chave_idempotencia),
    CONSTRAINT fk_baixa_financeira_parcela FOREIGN KEY (parcela_financeira_id, pessoa_titular_id)
        REFERENCES parcela_financeira(id, pessoa_titular_id) ON DELETE RESTRICT,
    CONSTRAINT fk_baixa_financeira_conta FOREIGN KEY (conta_financeira_id, pessoa_titular_id)
        REFERENCES conta_financeira(id, pessoa_titular_id) ON DELETE RESTRICT,
    CHECK (btrim(chave_idempotencia) <> ''),
    CHECK (valor_principal > 0 AND valor_principal < 1000000000000),
    CHECK (valor_juros >= 0 AND valor_juros < 1000000000000),
    CHECK (valor_multa >= 0 AND valor_multa < 1000000000000),
    CHECK (valor_desconto >= 0 AND valor_desconto < 1000000000000),
    CHECK (valor_movimentado >= 0 AND valor_movimentado < 1000000000000),
    CHECK (valor_movimentado = valor_principal + valor_juros + valor_multa - valor_desconto),
    CHECK (status IN ('EFETIVA','ESTORNADA')),
    CHECK ((status = 'ESTORNADA' AND data_estorno IS NOT NULL AND motivo_estorno IS NOT NULL AND btrim(motivo_estorno) <> '')
        OR (status = 'EFETIVA' AND data_estorno IS NULL AND motivo_estorno IS NULL)),
    CHECK (data_estorno IS NULL OR data_estorno >= data_baixa)
);


CREATE INDEX idx_categoria_financeira_pai ON categoria_financeira(categoria_pai_id);


CREATE INDEX idx_titulo_financeiro_titular_status ON titulo_financeiro(pessoa_titular_id, status, natureza);


CREATE INDEX idx_titulo_financeiro_pessoa ON titulo_financeiro(pessoa_id);


CREATE INDEX idx_titulo_financeiro_categoria ON titulo_financeiro(categoria_financeira_id);


CREATE INDEX idx_titulo_financeiro_propriedade ON titulo_financeiro(propriedade_id);


CREATE INDEX idx_titulo_financeiro_os ON titulo_financeiro(ordem_servico_id);


CREATE INDEX idx_parcela_financeira_vencimento ON parcela_financeira(pessoa_titular_id, data_vencimento);


CREATE INDEX idx_baixa_financeira_parcela ON baixa_financeira(parcela_financeira_id, status);


CREATE INDEX idx_baixa_financeira_conta_data ON baixa_financeira(conta_financeira_id, data_baixa);


CREATE INDEX idx_baixa_financeira_forma ON baixa_financeira(forma_pagamento_id);

CREATE INDEX idx_conta_financeira_instituicao_financeira_id ON conta_financeira(instituicao_financeira_id);
