# magento_notification

Desafio técnico (Mercado Eletrônico): módulo Magento 2 para notificar clientes
sobre alterações na política de privacidade.

> **Status:** infraestrutura Docker pronta. O módulo será adicionado em
> `src/app/code/`.

## Stack

| Componente | Versão |
|---|---|
| Magento Open Source | **2.4.8-p5** (fallback automático para 2.4.8) |
| PHP-FPM | 8.3 |
| MariaDB | 11.4 |
| OpenSearch | 2.19 |
| Nginx | 1.28 |

O Magento é baixado do mirror público **mirror.mage-os.org**, que espelha os
pacotes oficiais da Adobe. Assim **não é preciso ter chaves de acesso** do
`repo.magento.com`.

## Requisitos

- Docker Desktop (Windows/macOS) ou Docker Engine + Compose v2 (Linux).
- Pelo menos **6 GB de RAM** disponíveis para o Docker e ~10 GB de disco.
- Porta **8080** livre (configurável). É a única porta publicada no host.

## Como subir

```bash
git clone https://github.com/Dankdev021/magento_notification.git
cd magento_notification
docker compose up -d
```

Só isso. **Na primeira execução o comando leva de 10 a 20 minutos**: ele
constrói a imagem PHP, baixa o Magento e o instala. O comando só retorna
quando a loja está pronta. Para acompanhar:

```bash
docker compose logs -f setup
```

Ao final do log aparece `Magento pronto!`.

| | URL | Credenciais |
|---|---|---|
| Loja | http://localhost:8080/ | cliente `cliente@example.com` / `Cliente@12345` |
| Admin | http://localhost:8080/admin | `admin` / `Admin@12345` |

Nas próximas execuções o `docker compose up -d` leva cerca de 1 a 2 minutos:
os dados ficam em volumes e o serviço `setup` só roda `setup:upgrade`, que
registra módulos novos ou alterados em `src/app/code`.

## Comandos úteis

```bash
# CLI do Magento
docker compose exec -u www-data php bin/magento cache:flush

# Console do banco (a porta do MariaDB não é publicada no host)
docker compose exec db mariadb -umagento -pmagento magento

# Parar (mantém os dados)
docker compose down

# Recomeçar do zero (apaga banco, código do Magento e índices)
docker compose down -v
```

## Configuração

Todas as variáveis têm valor padrão no `docker-compose.yml`. Para mudar
porta, credenciais ou versão, copie `.env.example` para `.env`, ajuste e rode
`docker compose up -d` de novo.

## Arquitetura do ambiente

```
docker-compose.yml
docker/
  nginx/default.conf         # inclui o nginx.conf.sample oficial do Magento
  php/
    Dockerfile               # PHP 8.3-FPM + extensões exigidas + Composer
    composer.json.dist       # composer.json do projeto (repositório Mage-OS)
    conf/                    # php.ini e pool do FPM
    bin/magento-setup        # instalação/atualização idempotente
    bin/create-customer.php  # cria o cliente de teste
src/app/code/                # módulos customizados (montado no container)
```

Serviços:

- **db** (MariaDB) e **opensearch**: têm healthcheck; os demais esperam por eles.
- **setup**: roda uma vez a cada `up`. Na primeira execução faz
  `composer install`, `setup:install`, modo developer, cliente de teste e
  reindex. Depois disso, só `setup:upgrade`. Termina com código 0.
- **php** (FPM): só sobe depois que o `setup` termina com sucesso.
- **web** (Nginx): publica a porta 8080 e só sobe quando o PHP está saudável.

Decisões que garantem que o projeto suba em qualquer máquina:

- O código do Magento fica em um **volume Docker**, não numa pasta do host.
  Isso evita a lentidão de bind mounts no Windows/macOS. Só `src/app/code` é
  montado a partir do repositório.
- O `.gitattributes` força LF, e o Dockerfile ainda remove `\r` dos scripts
  caso o clone no Windows os converta para CRLF.
- O OpenSearch roda com `node.store.allow_mmap=false`, então não é preciso
  ajustar `vm.max_map_count` no host.
- O 2FA do admin (`Magento_TwoFactorAuth`) vem desativado, para o avaliador
  conseguir entrar sem configurar autenticação de dois fatores. Também não há
  envio de e-mails, porque o ambiente local não tem SMTP.

## Solução de problemas

- **`service "setup" didn't complete successfully`**: veja
  `docker compose logs setup`. Se a falha foi de rede durante o download, rode
  `docker compose up -d` de novo: o processo continua de onde parou.
- **Porta 8080 ocupada**: crie um `.env` com `HTTP_PORT=8081`.
- **Pouca memória no Windows**: aumente o limite do WSL em
  `%UserProfile%\.wslconfig` (`[wsl2]` / `memory=8GB`) e reinicie o Docker
  Desktop.
