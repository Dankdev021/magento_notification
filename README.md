# magento_notification

Desafio técnico (Mercado Eletrônico): módulo **`MercadoEletronico_TermsOfUse`**
para **Magento Open Source 2.4.8**. Ele notifica os clientes quando a política
de privacidade muda e registra o aceite de cada versão.

Depois do login, o cliente que ainda não aceitou a **versão vigente** vê um
modal com o texto da política e um botão de confirmação. O aceite fica
registrado com versão, data, loja e IP, e o modal não aparece de novo até que
uma nova versão seja publicada. No admin, a grid de clientes mostra quem já
aceitou.

## Sumário

1. [Início rápido](#1-início-rápido)
2. [Funcionalidades](#2-funcionalidades)
3. [Configuração no admin](#3-configuração-no-admin)
4. [Como testar](#4-como-testar)
5. [Concepção e arquitetura](#5-concepção-e-arquitetura)
6. [Estrutura do módulo](#6-estrutura-do-módulo)
7. [Como estender](#7-como-estender)
8. [Limitações conhecidas](#8-limitações-conhecidas)
9. [Ambiente Docker](#9-ambiente-docker)
10. [Solução de problemas](#10-solução-de-problemas)

---

## 1. Início rápido

**Requisitos:** Docker Desktop (Windows/macOS) ou Docker Engine com Compose v2
(Linux), cerca de 6 GB de RAM livres para o Docker e as portas **8080** e
**3307** livres.

```bash
git clone https://github.com/Dankdev021/magento_notification.git
cd magento_notification
docker compose up -d
```

A **primeira execução leva de 10 a 20 minutos**: o Docker baixa e instala o
Magento, e o módulo é instalado junto. O comando só retorna quando a loja está
pronta. Para acompanhar, use `docker compose logs -f setup` e espere aparecer
`Magento pronto!`.

| | URL | Credenciais |
|---|---|---|
| Loja | http://localhost:8080/ | cliente `cliente@example.com` / `Cliente@12345` |
| Admin | http://localhost:8080/admin | `admin` / `Admin@12345` |

| Item | Versão |
|---|---|
| Magento Open Source | **2.4.8-p5** (fallback automático para 2.4.8) |
| PHP | 8.3 |
| MariaDB / OpenSearch / Nginx | 11.4 / 2.19 / 1.28 |

## 2. Funcionalidades

| Requisito do desafio | Como foi atendido |
|---|---|
| 1a. Botão para ativar/desativar a notificação | Campo **Ativar notificação** em *Lojas → Configuração → Clientes → Termos de Uso* |
| 1b. Texto da notificação com os termos | Campos de título, texto (HTML com tags permitidas) e rótulo do botão, editáveis por store view |
| 2. Não notificar quem já aceitou | Tabela de aceites por **cliente e versão**. O modal só aparece se não houver aceite da versão vigente |
| 3. Ver na grid de clientes quem aceitou | Colunas **Aceitou os Termos de Uso** (Sim/Não, filtrável) e **Data do aceite** em *Clientes → Todos os clientes* |
| Aceite persistido | Registro com versão, data, store e IP (LGPD), gravado via **REST** |

Além do que o desafio pede:

- **Versionamento:** ao mudar o campo *Versão da política*, todos os clientes
  precisam aceitar de novo, e o histórico das versões anteriores é mantido.
- **API REST** para storefront, headless e auditoria (ver [4.3](#43-api-rest)).
- **ACL própria:** permissões separadas para configurar o módulo e para
  consultar os aceites.
- **Compatível com Full Page Cache:** o modal não desliga o cache das páginas.
- **Tradução pt_BR.**

## 3. Configuração no admin

*Lojas → Configuração → Clientes → Termos de Uso → Notificação da Política de Privacidade*

| Campo | Escopo | Descrição |
|---|---|---|
| Ativar notificação | Global / Website | Liga ou desliga o modal |
| Versão da política | Global / Website | Identificador da versão vigente (ex.: `2026-11`). **Ao alterar, todos precisam aceitar novamente** |
| Título do modal | Store view | Texto puro |
| Texto dos termos | Store view | HTML permitido: `p, br, strong, b, em, i, u, ul, ol, li, a, h2, h3, h4, span`. O resto é removido |
| Texto do botão | Store view | Ex.: "Li e aceito" |

O módulo vem **ativo**, com a versão `1.0` e um texto de exemplo, para
facilitar a avaliação. Depois de salvar, limpe o cache se o Magento pedir, ou
rode `docker compose exec -u www-data php bin/magento cache:flush`.

## 4. Como testar

### 4.1 Cenários manuais

| # | Cenário | Resultado esperado |
|---|---|---|
| 1 | Acessar a loja sem estar logado | Nenhum modal |
| 2 | Entrar com `cliente@example.com` | O modal aparece com título, texto e botão "Li e aceito". Não fecha com ESC, clique fora ou "X" |
| 3 | Clicar em **Li e aceito** | O modal fecha e é gravado um registro em `mercadoeletronico_terms_of_use_acceptance` |
| 4 | Navegar, sair e entrar de novo | O modal **não** aparece mais |
| 5 | Admin → *Clientes → Todos os clientes* | Coluna "Aceitou os Termos de Uso" = **Sim**, com a data do aceite. O filtro Sim/Não funciona |
| 6 | Admin → mudar a **Versão da política** para `2.0` e limpar o cache | Na grid, o cliente volta para **Não**. Na loja, depois de logar de novo, o modal reaparece |
| 7 | Admin → **Ativar notificação = Não** | O modal não aparece para ninguém e o JS do módulo não é carregado |
| 8 | Texto dos termos com `<script>alert(1)</script>` | O script é removido. Só a formatação permitida aparece |

Para conferir o banco:

```bash
docker compose exec db mariadb -umagento -pmagento magento \
  -e "SELECT * FROM mercadoeletronico_terms_of_use_acceptance;"
```

### 4.2 Testes unitários

```bash
docker compose exec -u www-data php vendor/bin/phpunit \
  -c dev/tests/unit/phpunit.xml.dist \
  app/code/MercadoEletronico/TermsOfUse/Test/Unit
```

Os testes cobrem estas regras:

- **Configuração:** a notificação só fica ativa com o flag ligado **e** uma versão definida.
- **Aceite:** é idempotente, grava cliente, versão, store e IP, e é bloqueado com o módulo desativado.
- **Seção do customer-data:** visitante nunca vê o modal, quem já aceitou não vê, e título e botão são escapados.

### 4.3 API REST

| Método | Endpoint | Autenticação | Descrição |
|---|---|---|---|
| GET | `/rest/V1/terms-of-use/current` | pública | Termos vigentes da store view |
| GET | `/rest/V1/terms-of-use/mine/status` | cliente | `true` se o cliente já aceitou a versão vigente |
| POST | `/rest/V1/terms-of-use/mine/accept` | cliente | Registra o aceite (idempotente) |
| GET | `/rest/V1/terms-of-use/acceptances?searchCriteria...` | admin (ACL `acceptance_view`) | Auditoria dos aceites |

Nas rotas `mine`, o `customerId` vem **sempre do cliente autenticado** (pelo
cookie de sessão no storefront ou pelo token Bearer), nunca da requisição. Um
cliente não consegue registrar aceite em nome de outro.

```bash
BASE=http://localhost:8080/rest/default/V1

# Termos vigentes
curl -s $BASE/terms-of-use/current

# Token do cliente
TOKEN=$(curl -s -X POST $BASE/integration/customer/token \
  -H 'Content-Type: application/json' \
  -d '{"username":"cliente@example.com","password":"Cliente@12345"}' | tr -d '"')

curl -s $BASE/terms-of-use/mine/status -H "Authorization: Bearer $TOKEN"          # false
curl -s -X POST $BASE/terms-of-use/mine/accept -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' -d '{}'                                     # registro criado
curl -s $BASE/terms-of-use/mine/status -H "Authorization: Bearer $TOKEN"          # true

# Auditoria com o token do admin
ADMIN=$(curl -s -X POST $BASE/integration/admin/token \
  -H 'Content-Type: application/json' \
  -d '{"username":"admin","password":"Admin@12345"}' | tr -d '"')
# -g (--globoff): sem ele o curl interpreta os colchetes como glob e falha em silêncio
curl -sg "$BASE/terms-of-use/acceptances?searchCriteria[pageSize]=20" -H "Authorization: Bearer $ADMIN"
```

## 5. Concepção e arquitetura

### Fluxo

```
Login ──► página (HTML em cache, igual para todos)
            └─ JS terms-modal ──► customer-data, seção "terms-of-use" (AJAX, por cliente)
                                   └─ CustomerData\TermsOfUse
                                       ├─ TermsProvider (config → termos vigentes)
                                       └─ TermsAcceptanceManagement::isAccepted()
            ◄── { show: true, title, content, button_label }
          modal ── "Li e aceito" ──► POST /rest/{store}/V1/terms-of-use/mine/accept
                                       └─ TermsAcceptanceManagement::accept()
                                           └─ AcceptanceRepository → tabela de aceites
```

### Decisões de projeto

**1. Tabela própria de aceites, e não um atributo de cliente.**
Um atributo EAV guardaria só o último estado ("aceitou: sim"). A tabela
`mercadoeletronico_terms_of_use_acceptance` guarda **uma linha por cliente e
versão**, com data, store e IP. Isso serve de prova de consentimento para a
LGPD, preserva o histórico e não altera tabelas do core. Uma chave única em
`(customer_id, terms_version)` impede duplicidade, e a FK com `ON DELETE
CASCADE` remove os aceites quando o cliente é excluído.

**2. "Versão vigente" como campo de configuração, atrás de uma interface.**
O admin decide explicitamente quando pedir um novo aceite. Um hash do texto
forçaria novo aceite a cada correção de vírgula. Quem fornece os termos é o
`TermsProviderInterface`: para evoluir para um cadastro de versões em tabela,
basta outra implementação e uma linha no `di.xml`.

**3. Modal decidido pelo customer-data, e não por um bloco ou observer de login.**
O Magento guarda as páginas em **Full Page Cache**, e o HTML é o mesmo para
todos. Se a decisão de mostrar o modal ficasse no HTML, seria preciso
`cacheable="false"`, o que desliga o cache da página inteira. A solução usa o
mecanismo oficial para conteúdo privado (`SectionSourceInterface` +
`sections.xml`). O template só inicializa o JS, e cada cliente recebe sua
decisão via AJAX. Isso também cobre quem já estava logado quando a versão
mudou: o modal aparece na próxima vez que a seção for recarregada.

**4. Aceite via REST, com a regra de negócio num serviço.**
O botão do modal chama `POST /V1/terms-of-use/mine/accept`. O controller do
Magento Webapi é a "porta"; a regra fica em `TermsAcceptanceManagement`.
Storefront, apps e integrações usam a mesma API, e o `sections.xml` invalida a
seção automaticamente depois do aceite. O IP vem do `RemoteAddress` do
Magento.

**5. Coluna da grid com join em tempo de consulta.**
A grid de clientes lê o índice `customer_grid_flat`. Guardar "aceitou" nesse
índice exigiria reindexar todos os clientes a cada nova versão. Um plugin em
`CollectionFactory::getReport()` (só para `customer_listing_data_source`)
faz um `LEFT JOIN` com a tabela de aceites **filtrando pela versão vigente**. O
resultado está sempre correto e funciona com filtro e ordenação (via
`addFilterToMap`).

**6. Segurança.**
- O HTML do texto passa por `ContentSanitizer`, com uma lista de tags permitidas configurável via `di.xml`.
- Título e botão são escapados antes de chegar ao modal.
- As rotas `mine` forçam `%customer_id%`.
- Há ACLs separadas para configuração e auditoria.

### Padrões de código

- **Service contracts** em `Api/` (`@api`), com implementações ligadas por `preference` no `di.xml`.
- **Uma única classe de configuração** (`Model\Config`). Nenhum caminho de configuração solto pelo código.
- **ViewModel** no lugar de lógica no template. Controllers e plugins enxutos.
- `declare(strict_types=1)`, *constructor property promotion* com `readonly`, sem `ObjectManager` direto.
- **Declarative schema** (`db_schema.xml`) em vez de scripts de instalação.
- Textos em inglês no código, traduzidos em `i18n/pt_BR.csv` (padrão Magento).

## 6. Estrutura do módulo

```
src/app/code/MercadoEletronico/TermsOfUse/
├── Api/                                    # Contratos públicos (@api)
│   ├── Data/AcceptanceInterface.php        #   registro de aceite
│   ├── Data/AcceptanceSearchResultsInterface.php
│   ├── Data/TermsInterface.php             #   termos vigentes (DTO)
│   ├── AcceptanceRepositoryInterface.php   #   persistência
│   ├── TermsProviderInterface.php          #   origem dos termos
│   └── TermsAcceptanceManagementInterface.php  # regra: aceitar / já aceitou?
├── CustomerData/TermsOfUse.php             # seção "terms-of-use" (compatível com FPC)
├── Model/
│   ├── Acceptance.php, AcceptanceRepository.php
│   ├── Config.php                          # leitura da configuração
│   ├── ContentSanitizer.php                # tags HTML permitidas
│   ├── TermsProvider.php                   # termos a partir da configuração
│   ├── TermsAcceptanceManagement.php       # aceite idempotente com IP
│   ├── Data/Terms.php
│   └── ResourceModel/
│       ├── Acceptance.php, Acceptance/Collection.php
│       └── Customer/Grid/AcceptanceJoin.php  # join da grid de clientes
├── Plugin/Customer/AddTermsAcceptanceToCustomerGrid.php
├── ViewModel/Modal.php                     # config do JS (URL REST)
├── Test/Unit/                              # PHPUnit
├── etc/
│   ├── module.xml, di.xml, acl.xml, config.xml, webapi.xml
│   ├── db_schema.xml, db_schema_whitelist.json
│   ├── adminhtml/system.xml, adminhtml/di.xml
│   └── frontend/di.xml, frontend/sections.xml
├── view/
│   ├── frontend/layout/default.xml
│   ├── frontend/templates/modal.phtml
│   ├── frontend/web/js/terms-modal.js      # modal (Magento_Ui/js/modal/modal)
│   ├── frontend/web/css/source/_module.less
│   └── adminhtml/ui_component/customer_listing.xml
├── i18n/pt_BR.csv
├── composer.json
└── registration.php
```

## 7. Como estender

| Necessidade futura | O que fazer |
|---|---|
| Cadastro de versões com histórico de textos | Nova implementação de `TermsProviderInterface` e troca da `preference` no `di.xml` |
| GraphQL / PWA | Criar `schema.graphqls` com resolvers que chamam `TermsAcceptanceManagementInterface` |
| Guardar user agent ou outros dados de consentimento | Nova coluna no `db_schema.xml` e um setter no `AcceptanceInterface` |
| Permitir outras tags HTML | Argumento `allowedTags` do `ContentSanitizer` via `di.xml` |
| Reagir ao aceite (e-mail, CRM...) | Observer no evento `mercadoeletronico_terms_of_use_acceptance_save_after` |
| Exibir o modal também para visitantes | Ajustar `CustomerData\TermsOfUse` e persistir o aceite por cookie ou sessão |

## 8. Limitações conhecidas

- **Cache da seção no navegador:** o customer-data fica no `localStorage`. Se
  a versão mudar enquanto o cliente está logado, o modal aparece quando a
  seção for recarregada: no próximo login, numa ação que a invalide ou quando
  ela expirar (60 minutos por padrão).
- **Grid e multi-website:** a coluna do admin compara com a versão do **escopo
  padrão**. Se cada website tiver uma versão diferente, a coluna reflete a
  versão global.
- **IP atrás de proxy:** o IP gravado é o que o PHP recebe. Atrás de um load
  balancer, é preciso configurar o Magento para confiar no `X-Forwarded-For`.

## 9. Ambiente Docker

```
docker-compose.yml
docker/
  nginx/default.conf         # inclui o nginx.conf.sample oficial do Magento
  php/
    Dockerfile               # PHP 8.3-FPM + extensões + Composer
    composer.json.dist       # composer.json do projeto (repositório Mage-OS)
    conf/                    # php.ini e pool do FPM
    bin/magento-setup        # instalação/atualização idempotente
    bin/create-customer.php  # cria o cliente de teste
src/app/code/                # módulos customizados (montados nos containers)
```

- **db** (MariaDB) e **opensearch**: com healthcheck; os demais esperam por eles.
- **setup**: roda a cada `up`. Na primeira vez faz `composer install`,
  `setup:install`, modo developer, cliente de teste e reindex; depois, só
  `setup:upgrade`, que aplica as alterações do módulo.
- **php** (FPM): sobe depois que o `setup` termina com sucesso.
- **web** (Nginx): publica a porta 8080.

Decisões para que o projeto suba em qualquer máquina:

- **Sem chaves da Adobe:** o Magento vem do mirror público `mirror.mage-os.org`.
- **Código do Magento em volume Docker:** evita a lentidão de bind mounts no
  Windows/macOS. Só `src/app/code` é montado a partir do repositório.
- **LF forçado:** o `.gitattributes` força LF, e o Dockerfile ainda remove
  `\r` dos scripts.
- **OpenSearch sem ajuste no host:** roda com `node.store.allow_mmap=false`,
  então não é preciso mexer no `vm.max_map_count`.
- **Admin acessível:** 2FA desativado e envio de e-mails desligado, já que não
  há SMTP.

Comandos úteis:

```bash
docker compose exec -u www-data php bin/magento cache:flush   # CLI do Magento
docker compose exec db mariadb -umagento -pmagento magento     # console do banco
docker compose down                                            # parar (mantém os dados)
docker compose down -v                                         # recomeçar do zero
```

Para mudar portas, credenciais ou versão, copie `.env.example` para `.env`.

## 10. Solução de problemas

- **`service "setup" didn't complete successfully`:** veja
  `docker compose logs setup --tail=100`. Se foi falha de rede, rode
  `docker compose up -d` de novo; o processo continua de onde parou.
- **PHPUnit: `Subscriber "PHPUnit\Event\Test\..." does not exist`:** ambiente
  instalado antes da correção do `exclude-from-classmap` (o padrão `**/Test/**`
  tirava do autoload as classes do próprio PHPUnit). Corrija sem reinstalar:
  ```bash
  docker compose exec -u www-data php sed -i 's#"\*\*/Test/\*\*"#"*/*/Test/**/*Test"#' composer.json
  docker compose exec -u www-data php composer dump-autoload
  ```
- **Alterei o `docker/` e nada mudou:** use `docker compose up -d --build`.
- **`port is already allocated`:** crie um `.env` com outra porta, por exemplo
  `HTTP_PORT=8081` ou `DB_PORT=3308`.
- **Modal não aparece:** confirme que a notificação está ativa, limpe o cache
  (`bin/magento cache:flush`) e faça logout e login de novo.
- **Pouca memória no Windows:** aumente o limite do WSL em
  `%UserProfile%\.wslconfig` (`[wsl2]` / `memory=8GB`) e reinicie o Docker
  Desktop.
