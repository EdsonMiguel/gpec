# GPec — Sistema de Gestão Pecuária

Aplicação Laravel 13, PHP 8.5, MySQL 8.4, Blade e Tailwind CSS 4. Código explícito, organizado por domínio, sem geradores de CRUD.

## Módulos

- `app/Modules/Auth`: login, sessão, tenants e usuários.
- `app/Modules/Rebanho`: animais, lotes, busca por brinco e histórico individual.
- `app/Modules/Ponderal`: pesagens por brinco com motivo obrigatório e CRUD dos motivos.
- `app/Modules/Sanidade`: medicamentos, aplicações e próximas aplicações previstas.
- `app/Modules/Dashboard`: indicadores, categorias, agenda sanitária e últimas pesagens.
- `app/Modules/Fazendas`: fazendas e suas áreas físicas, como pastos, piquetes e currais.
- `app/Modules/Alimentacao`: tipos de alimento, alimentos e refeições com vários itens fornecidos a cada animal.
- `app/Modules/Financeiro`: contas de orçamento, produtos e transações de compras/vendas com pagamento, recebimento e cancelamento.

Cada módulo registra suas rotas e possui controllers, models e Form Requests quando recebe dados. O `ModuleServiceProvider` registra explicitamente os módulos. Views ficam em `resources/views/modules`; componentes compartilhados ficam em `resources/views/components`.

## Instalação local

Requisitos: PHP 8.5 com `pdo_mysql`, Composer, MySQL 8.4 e Node.js compatível com Vite 8.

```sh
composer install
cp .env.example .env
php artisan key:generate
```

No PowerShell, use `Copy-Item .env.example .env` apenas se ainda não existir `.env`. Configure as credenciais MySQL no `.env`, especialmente `DB_PASSWORD`. Mantenha `APP_TIMEZONE=America/Sao_Paulo` para a operação nesse fuso. Se a porta 3306 estiver ocupada, escolha outra em `DB_PORT`.

```sh
php artisan migrate
pnpm install --frozen-lockfile
pnpm run build
php artisan pecuaria:admin "Minha Fazenda" admin@fazenda.com.br --nome="Administrador"
php artisan serve
```

O comando solicita uma senha oculta com confirmação. Não existem usuários nem senhas padrão. Acesse `/login`. É possível usar npm para instalar e compilar; o lockfile versionado é o do pnpm.

Após entrar como Admin, use o link **Equipe** no cabeçalho para criar acessos de Vaqueiro ou de outros administradores. Os acessos criados pertencem automaticamente à mesma fazenda.

## Papéis e segurança

| Operação | Admin | Vaqueiro |
| --- | --- | --- |
| Consultar dashboard, rebanho, lotes e históricos | Sim | Sim |
| Registrar pesagens e aplicações | Sim | Sim |
| Cadastrar, editar e arquivar animais | Sim | Não |
| Cadastrar lotes e medicamentos, inativar medicamentos | Sim | Não |
| Consultar motivos de pesagem e utilizá-los nos lançamentos | Sim | Sim |
| Cadastrar, editar, inativar e excluir motivos não utilizados | Sim | Não |
| Consultar a equipe e criar acessos da própria fazenda | Sim | Não |
| Consultar fazendas e suas áreas | Sim | Sim |
| Cadastrar, editar e inativar fazendas e áreas | Sim | Não |
| Consultar alimentos e registrar refeições | Sim | Sim |
| Cadastrar, editar, inativar e excluir alimentos/tipos sem uso | Sim | Não |
| Acessar financeiro, orçamentos, produtos e transações | Sim | Não |

O link **Financeiro** no cabeçalho reúne transações, contas de orçamento e outros produtos. Cada conta tem natureza de receita ou despesa, valor orçado e vigência. Compras utilizam contas de despesa; vendas utilizam contas de receita, dentro do período da conta. A listagem compara orçado, efetivado e pendente.

Uma transação registra fornecedor/cliente, referência documental, data da operação, vencimento opcional e até 30 itens. Os itens podem apontar para alimento, medicamento, animal ou produto, ou ter descrição livre. Quantidade e preço unitário determinam os totais, calculados no servidor com BCMath, sem float, arredondando cada item para centavos antes da soma. Nomes e unidades são copiados para preservar o histórico. Para animais, cada item representa um animal e usa quantidade 1. O registro financeiro mantém a situação do animal e os controles de fornecimento separados.

As transações podem iniciar pendentes ou efetivadas. A efetivação registra o pagamento/recebimento integral com data e forma. O cancelamento exige motivo, preserva os valores e dados originais e retira o lançamento dos totais atuais. Correções são feitas cancelando e registrando outro lançamento. Não há exclusão física de transações pela aplicação. O módulo é um registro gerencial: a efetivação não executa transferência bancária; parcelamento, liquidação parcial e conciliação bancária ficam para uma evolução específica.

Os indicadores seguem a **data da transação** e os filtros escolhidos, e mostram receitas/despesas efetivadas, pendências e resultado registrado. Valores em BRL. Contas utilizadas mantêm natureza e vigência; orçamento, nome e disponibilidade podem ser ajustados. Produtos e contas utilizados podem ser inativados. As FKs compostas protegem o tenant e a natureza da conta. A criação usa uma chave de idempotência por formulário, verificando o conteúdo em reenvios, e as mutações financeiras são serializadas por tenant.

O menu inferior **Refeições** dá acesso ao controle de alimentação. Uma refeição reúne um animal, data/hora, turno e até 20 alimentos diferentes, com as quantidades totais efetivamente fornecidas. O lançamento aceita o brinco completo e copia a unidade de cada alimento para manter o histórico. Alimento e tipo devem estar ativos. Quantidades são positivas, com até três casas decimais; datas não podem ser futuras nem anteriores ao nascimento. Refeições do mesmo animal no mesmo instante são recusadas. O histórico é preservado e pode ser consultado também na ficha do animal.

Tipos e alimentos utilizados podem ser inativados; exclusão é permitida somente sem vínculos. A unidade de um alimento já fornecido fica preservada, e uma apresentação em outra unidade exige um novo cadastro. Os lançamentos são feitos em transação, com isolamento por tenant e FKs compostas em cabeçalhos e itens.

O menu **Fazendas e áreas** permite cadastrar várias propriedades na mesma conta. Cada área pertence a uma fazenda específica, com nome, código, tipo, dimensão em hectares, descrição e situação. Os nomes e códigos de áreas são únicos dentro da fazenda. A fazenda possui localização, área total e observações. Inativar uma fazenda preserva suas áreas e impede novos cadastros de áreas até a reativação. Os cadastros de fazendas e áreas ainda não alteram a alocação de animais ou lotes.

Os formulários de fazenda e área incluem um mapa Leaflet com imagens de satélite Esri World Imagery e alternativa de ruas OpenStreetMap. As ferramentas permitem desenhar polígonos/retângulos, editar vértices, excluir e limpar contornos. As marcações são gravadas somente ao salvar o cadastro, em uma coluna `json` opcional chamada `geojson` nas duas tabelas. O GeoJSON usa coordenadas WGS84 em ordem longitude/latitude. O backend aceita Polygon, MultiPolygon, Feature ou FeatureCollection de polígonos, com até 500 KB e 10.000 vértices, validando anéis fechados e coordenadas. As medidas manuais em hectares permanecem independentes do desenho.

Ao abrir a aplicação, o navegador solicita acesso à localização. A posição autorizada centraliza os mapas novos e fica apenas na sessão do navegador; não é enviada ao banco. Um contorno já salvo tem prioridade ao reabrir o mapa. No cadastro de uma área, o contorno da fazenda aparece como referência tracejada. O botão **Minha localização** permite centralizar novamente. Ao negar a permissão ou quando a localização falha, o mapa permanece disponível para uso manual. Geolocalização exige HTTPS ou localhost; as imagens e os mapas de ruas exigem conexão com a internet. Os limites da fazenda também aparecem em sua ficha para consulta.

`tenant_id` nunca é recebido de formulários. A trait `BelongsToTenant` aplica `TenantScope`, injeta o tenant do usuário autenticado e bloqueia troca de tenant e mutações de modelos previamente carregados por outro tenant. Consultas sem autenticação não retornam registros transacionais; gravações são recusadas. Jobs e comandos que manipulam rebanho devem estabelecer explicitamente um usuário autorizado e limpar o contexto ao terminar.

`User` e `Tenant` são raízes de autenticação/provisionamento e não recebem o scope que depende de `Auth::user()`, evitando recursão durante login e recuperação de sessão. Não há endpoints públicos para listar usuários ou tenants. E-mails são únicos globalmente; o login resolve a fazenda pelo usuário. Criação administrativa de usuários exige atribuição explícita de `tenant_id` e `role` no código de provisionamento, pois esses campos não aceitam atribuição em massa.

As tabelas de domínio possuem `tenant_id`, índices compostos e FKs compostas para impedir vínculos entre fazendas também no banco. A proteção pressupõe uso dos models e das rotas autorizadas: SQL bruto e remoção deliberada do scope são operações privilegiadas. O isolamento usa escopos Eloquent e chaves estrangeiras compostas.

Login possui limitação de tentativas, regeneração de sessão e logout por POST. Formulários usam CSRF, validação no servidor e saída Blade escapada. Em produção, use HTTPS, `APP_DEBUG=false` e `SESSION_SECURE_COOKIE=true`.

## Regras de manejo

- Brinco é único por tenant, inclusive para animais arquivados; a busca de listagem aceita trecho, e o lançamento exige o brinco completo.
- Pai e mãe são opcionais e selecionados entre animais da mesma conta. O pai deve ser macho e a mãe, fêmea; animais vendidos, mortos ou arquivados continuam disponíveis para preservar a filiação. Quando as datas são conhecidas, pais devem ser anteriores ao filho. A gravação impede autorreferência e ciclos, e alterações de sexo ou nascimento preservam os vínculos com filhos existentes. Essas gravações são serializadas por tenant para impedir ciclos concorrentes.
- Animais arquivados usam soft delete. A ficha continua disponível para consulta; novas alterações e lançamentos são bloqueados.
- Animais fora de ATIVO não podem permanecer em lote.
- Peso e quantidade aplicada são decimais positivos. Pesagens repetidas no mesmo minuto são rejeitadas; podem existir várias medições no mesmo dia.
- Toda pesagem exige um motivo ativo da mesma conta. Em **Pesagem → Motivos de pesagem**, o Admin gerencia código, nome, descrição e situação. Motivos utilizados não podem ser excluídos; sua inativação preserva o histórico. A migração atribui às pesagens antigas o motivo inativo **Não informado — registro anterior**, pois o motivo original não foi registrado. O vínculo é obrigatório também no banco, com FK composta por tenant.
- Eventos realizados não podem ocorrer no futuro nem antes do nascimento. Alterações de nascimento também respeitam o histórico existente.
- Medicamentos usados podem ser inativados; o histórico permanece. A unidade é copiada do cadastro para a aplicação.
- A próxima aplicação é uma previsão. Um registro posterior do mesmo produto para o mesmo animal substitui o alerta anterior, preservando todos os registros. No mesmo dia, prevalece o último lançamento. Sem nova previsão, o alerta desse produto deixa de aparecer.
- O dashboard considera animais ativos e mostra pendências vencidas ou previstas até sete dias. Não há cálculo automático de dose ou protocolo.

## Relação com os SQLs fornecidos

Os sete arquivos V2 a V8 em `basededados/` foram usados como referência de domínio e permanecem preservados. Esta entrega implementa as tabelas e telas explicitamente pedidas: tenants, usuários, animais, lotes, pesagens, medicamentos e manejos sanitários.

O esquema Laravel é uma instalação nova, não uma importação ou execução dos SQLs Flyway. Usa IDs inteiros, tabelas plurais e isolamento por `tenant_id`; os SQLs usam UUID, tabelas singulares e vínculos por propriedade/titular. Raça e categoria seguem a forma simplificada solicitada. Fazendas e áreas adaptam os conceitos de propriedade e local dos SQLs, sem o cadastro completo de pessoas/titulares. A filiação básica usa pai e mãe no próprio cadastro do animal. A alimentação agrupa os fornecimentos individuais em refeições com vários itens. O financeiro implementa contas de orçamento e transações com itens, sem portar a estrutura completa de títulos, parcelas e baixas do SQL legado. Reprodução e genealogia avançada, ordens de serviço e prontuário clínico detalhado ainda não foram portados.

Não execute os dois esquemas como se fossem equivalentes. Não existe conversão de dados legados nesta entrega. As migrações iniciais de usuários foram adaptadas para instalação nova; um banco com migrations anteriores já executadas exige migração incremental própria, sem apagar seus dados.

Há uma dependência de ordem nos SQLs legados: V5 tenta criar `idx_aplicacao_sanitaria_ocorrencia` antes de V8 adicionar `ocorrencia_clinica_id`. Isso precisaria ser corrigido caso se opte pelo esquema Flyway.

## Dados de demonstração (seeds)

```sh
php artisan db:seed
```

O `DatabaseSeeder` chama seeders separados para tenants, usuários, fazendas, áreas, lotes, animais, medicamentos, motivos de pesagem, pesagens, manejos sanitários, tipos de alimento, alimentos e refeições com seus itens. Os eventos dos models permanecem ativos, com o contexto autenticado de cada tenant e uma transação para toda a execução.

Se não houver uma conta, será criada **Conta Demonstração**. Para cada conta existente, serão incluídos 2 fazendas, 8 áreas, 3 lotes, 16 animais (15 ativos e 1 vendido), 3 produtos fictícios, 6 motivos de pesagem, 48 pesagens e 32 aplicações fictícias, com previsões vencidas e futuras para demonstrar o dashboard. Os motivos iniciais são acompanhamento de crescimento, nascimento, desmama, entrada, saída e avaliação reprodutiva. Novas pesagens de demonstração usam acompanhamento; pesagens existentes mantêm seu motivo. Os demais dados usam o prefixo `[Demo]`, códigos `DEMO-` e brincos `DEMO-0001` a `DEMO-0016`. Reserve esses identificadores para os exemplos.

O seed preserva os dados existentes e não redefine senhas nem restaura animais arquivados. Pode ser executado novamente, inclusive em dias diferentes, sem duplicar os registros que ele criou. Se um identificador reservado for alterado manualmente, uma nova execução poderá recriar o exemplo com o identificador original.

O módulo de alimentação inclui ainda 4 tipos, 3 alimentos fictícios e, para os 15 animais ativos de demonstração, 30 refeições com 60 itens. As quantidades desses exemplos são ilustrativas. Refeições existentes permanecem intactas ao executar novamente os seeds.

O `FinanceiroSeeder` inclui 6 contas de orçamento, 2 produtos e 5 transações pendentes, todas identificadas como `[Demo]`: compras de alimento, medicamento e animal, e vendas de animal e produto. Os valores são fictícios. Os exemplos usam chaves estáveis, mantêm os lançamentos existentes e não desfazem efetivações ou cancelamentos em reexecuções.

Se o tenant ainda não tiver Admin, será criado `admin.ID_DO_TENANT@campo.test`. Cada tenant também recebe `vaqueiro.ID_DO_TENANT@campo.test`. Apenas os usuários novos de demonstração usam a senha `CampoDemo!2026`, que pode ser personalizada antes da primeira execução por `DEMO_SEED_PASSWORD` no `.env`. O administrador já provisionado mantém seu acesso atual.

Os seeds de demonstração só executam em `APP_ENV=local` ou `testing`, inclusive quando `--force` é usado. Tabelas de sessão, recuperação de senha, cache e filas são preenchidas pelo Laravel durante o funcionamento e não recebem registros fictícios. Os produtos e aplicações são exemplos de interface e não representam protocolos clínicos.

## Validação dos testes

```sh
php artisan test
php vendor/bin/pint --test
php artisan view:cache
pnpm run build
pnpm run test:js
```

Por padrão, os testes usam SQLite em memória, sem acessar o banco de operação. Para MySQL, forneça as variáveis `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` no ambiente do processo e use exclusivamente uma base descartável: `RefreshDatabase` recria as tabelas de teste. As mesmas funcionalidades e FKs compostas são testadas nos dois drivers.
