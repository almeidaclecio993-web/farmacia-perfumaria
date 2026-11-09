# Farmácia e Perfumaria SaaS

Aplicação PHP MVC para vitrine digital de farmácia e perfumaria com catálogo, filtros, carrinho, checkout, painel administrativo, contas individuais, histórico privado de pedidos e login/vínculo opcional com Google.

## Requisitos

- PHP 8.1+ com `pdo_mysql` habilitado.
- MySQL 8+ ou MariaDB compatível, com um banco criado para a aplicação.

Se `pdo_mysql` estiver instalado, mas desativado, habilite `extension=pdo_mysql` no `php.ini` usado pelo servidor e confirme com `php -m`.

## Configuração e execução local

Configure as variáveis de ambiente antes de iniciar o servidor:

```powershell
$env:DB_DRIVER = "mysql"
$env:DB_HOST = "127.0.0.1"
$env:DB_PORT = "3306"
$env:DB_DATABASE = "farmacia"
$env:DB_USERNAME = "farmacia_app"
$env:DB_PASSWORD = "uma-senha-forte-do-banco"
$env:ADMIN_EMAIL = "admin@sualoja.com"
$env:ADMIN_PASSWORD = "uma-senha-administrativa-com-pelo-menos-16-caracteres"
```

Crie o banco e um usuário MySQL com permissões limitadas ao banco da aplicação. A aplicação cria as tabelas necessárias na primeira inicialização. Não use a conta `root` em produção.

Inicie o servidor local:

```powershell
php -S 127.0.0.1:8000 -t public
```

Acesse http://127.0.0.1:8000/.

## Migração do armazenamento JSON

Na primeira inicialização com o banco MySQL sem produtos, a aplicação importa os produtos, usuários, pedidos, itens, pagamentos e configurações encontrados em `storage/database.json`. O arquivo JSON original não é alterado nem apagado. Faça backup dele e do banco antes de migrar; a importação não sobrescreve registros existentes e só inicia quando a tabela de produtos está vazia. Se o banco já contém produtos, a importação automática não é executada.

## Contas e senhas

Clientes podem criar uma conta em `/register`, entrar em `/login` e consultar seus pedidos em `/account`. O checkout exige autenticação e cada cliente visualiza apenas pedidos associados ao seu próprio ID.

Novas senhas de clientes devem ter de 8 a 11 caracteres ASCII, incluindo ao menos uma letra maiúscula, uma minúscula e um caractere especial. Senhas antigas com mais de 11 caracteres continuam aceitas no login para evitar bloquear contas já existentes. Senhas são armazenadas com `password_hash()`.

O usuário administrativo padrão com senha conhecida foi removido. Configure `ADMIN_EMAIL` e `ADMIN_PASSWORD` antes de iniciar o servidor. A senha administrativa precisa ter ao menos 16 caracteres e é armazenada somente como hash.

## Login Google

1. Crie um cliente OAuth 2.0 do tipo aplicação Web no Google Cloud Console.
2. Configure `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` e `GOOGLE_REDIRECT_URI` no ambiente do servidor. Cadastre a URI de retorno exatamente igual no cliente OAuth.
3. Use uma URI HTTPS em produção; HTTP é aceito somente para `localhost`/`127.0.0.1`. Nunca coloque o segredo Google no frontend ou no repositório.
4. Habilite a extensão PHP OpenSSL. Sem as credenciais e o OpenSSL, o botão Google fica indisponível.

## Segurança e produção

- O acesso ao banco usa PDO/MySQL com prepared statements e tabelas InnoDB.
- Sessões regeneram o ID ao autenticar; cookies são HttpOnly, SameSite=Lax e Secure quando a conexão é HTTPS.
- Formulários usam CSRF; tentativas de senha são limitadas; o login Google usa state, PKCE e HTTPS com validação de certificado.
- O histórico de pedidos é filtrado pelo ID da conta no servidor, e o checkout recalcula preço/estoque em transação com bloqueio das linhas de produto.
- Mantenha `storage` fora da raiz pública e proteja as credenciais do banco. Em produção, use HTTPS, backups criptografados, gestão de segredos e monitoramento.

O pagamento deste protótipo não está conectado a um gateway real.
