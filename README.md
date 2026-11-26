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
7. [Ambiente Docker](#7-ambiente-docker)

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
docker compose exec -u www-data php vendor/bin/phpunit --no-extensions \
  -c dev/tests/unit/phpunit.xml.dist \
  app/code/MercadoEletronico/TermsOfUse/Test/Unit
```

O `--no-extensions` só evita um aviso da extensão Allure, que o
`phpunit.xml.dist` padrão do Magento carrega.

Os testes cobrem estas regras:

- **Configuração:** a notificação só fica ativa com o flag ligado **e** uma versão definida.
- **Aceite:** é idempotente, grava cliente, versão, store e IP, e é bloqueado com o módulo desativado.
- **Seção do customer-data:** visitante nunca vê o modal, quem já aceitou não vê, título e botão são escapados e o texto passa pelo sanitizer.
- **Grid:** as colunas calculadas ficam mapeadas também com o prefixo `main_table.`, que o filtro da grid usa.

## 5. Concepção e arquitetura

```
Login ──► página em cache (igual para todos)
            └─ JS do modal ──► customer-data "terms-of-use" (AJAX, por cliente)
                                 └─ já aceitou a versão vigente? ──► { show, title, content }
          "Li e aceito" ──► POST /rest/V1/terms-of-use/mine/accept
                                 └─ TermsAcceptanceManagement ──► tabela de aceites
```

- **Tabela própria de aceites:** uma linha por cliente e versão, com data, loja
  e IP. Guarda o histórico (prova de consentimento) sem alterar tabelas do
  core. Chave única em `(customer_id, terms_version)`.
- **Versão como configuração:** o admin decide quando pedir novo aceite
  trocando o campo *Versão da política*.
- **Decisão no customer-data:** o HTML da página continua em Full Page Cache, e
  cada cliente recebe a decisão de mostrar o modal via AJAX.
- **Aceite via REST:** o botão chama `POST /V1/terms-of-use/mine/accept`. O
  `customerId` vem sempre do cliente autenticado, e a regra de negócio fica no
  serviço `TermsAcceptanceManagement`.
- **Coluna da grid por join:** um plugin faz `LEFT JOIN` com os aceites da
  versão vigente, sem reindex ao trocar a versão.
- **Segurança:** o texto passa por um sanitizer de tags permitidas, título e
  botão são escapados, e a configuração tem ACL própria.

## 6. Estrutura do módulo

```
src/app/code/MercadoEletronico/TermsOfUse/
├── Api/                                    # Contratos públicos (@api)
│   ├── Data/AcceptanceInterface.php        #   registro de aceite
│   ├── AcceptanceRepositoryInterface.php   #   persistência
│   └── TermsAcceptanceManagementInterface.php  # regra: aceitar / já aceitou?
├── CustomerData/TermsOfUse.php             # seção "terms-of-use" (compatível com FPC)
├── Model/
│   ├── Acceptance.php, AcceptanceRepository.php
│   ├── Config.php                          # leitura da configuração
│   ├── ContentSanitizer.php                # tags HTML permitidas
│   ├── TermsAcceptanceManagement.php       # aceite idempotente com IP
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

## 7. Ambiente Docker

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
