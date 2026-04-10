# Operis CRM

Plataforma CRM dedicada para escolas — gerencie leads, oportunidades, tarefas e matrículas em um único lugar.

## 🚀 Sobre o Projeto

O Operis CRM é um sistema focado no controle do funil de matrículas e rematrículas, bem como no acompanhamento comercial das escolas. O sistema utiliza uma arquitetura baseada em Laravel e Vue.js, garantindo alta performance e reatividade na experiência do usuário.

**Principais Funcionalidades:**
- **Gestão de Matrículas**: Controle completo do funil de captação e rematrículas.
- **CRM Comercial**: Acompanhamento rigoroso de leads, oportunidades e tarefas iterativas.
- **Visão Multi-tenant**: Painel e base de dados com suporte a isolamento por unidade escolar/tenant.
- **Controle de Acesso (ACL)**: Sistema granular de permissões e perfis de usuário.
- **Auditoria Completa**: Rastreabilidade de ações (usando `laravel-auditing`) para total conformidade e tracking.

## 🛠️ Stack Tecnológica

### Backend
- **PHP**: ^8.4 (Laravel ^13.0)
- **Banco de Dados**: MySQL (single-database multi-tenancy)
- **Autenticação**: Laravel Fortify v1
- **Inertia Server**: inertia-laravel v2
- **Roteamento TS**: Wayfinder v0 + Ziggy v2
- **Auditoria**: `owen-it/laravel-auditing`
- **Testes & Estilo**: Pest v4, Laravel Pint v1

### Frontend
- **Framework**: Vue.js ^3.5 (Composition API com `<script setup lang="ts">`)
- **App Client**: @inertiajs/vue3 v2
- **Estilização**: Tailwind CSS v4
- **UI Components**: reka-ui + shadcn-vue
- **Ícones**: lucide-vue-next
- **Lint & Formatação**: ESLint v9, Prettier v3

## ⚙️ Requisitos do Sistema

- PHP >= 8.4
- Node.js >= 20.x
- Composer >= 2.0
- MySQL >= 8.0 ou MariaDB

## 📦 Como Instalar e Rodar

O ambiente local do projeto pode ser executado de 3 formas: **Docker (Laravel Sail)**, **Laravel Herd** ou **Laravel Valet**. 

### Passo Comum: Clonar o repositório

Independente da forma escolhida, inicialize o código e copie o `.env`:
```bash
git clone <url-do-repositorio> operis-crm
cd operis-crm
cp .env.example .env
```

---

### Opção 1: Via Docker (Laravel Sail)

Ideal para quem não deseja instalar PHP e MySQL fisicamente na máquina.

1. **Instalar dependências PHP em um container temporário:**
   ```bash
   docker run --rm \
       -u "$(id -u):$(id -g)" \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       laravelsail/php84-composer:latest \
       composer install --ignore-platform-reqs
   ```
2. **Subir os containers do projeto:**
   ```bash
   ./vendor/bin/sail up -d
   ```
3. **Gerar chave e rodar o banco:**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ```
4. **Instalar dependências front e rodar o Vite:**
   ```bash
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run dev
   ```

Acesse em: `http://localhost`

---

### Opção 2: Via Laravel Herd

Ideal para desenvolvimento nativo (macOS e Windows) zero-config.

1. No painel do **Herd**, certifique-se de que a pasta `operis-crm` está linkada e rodando no **PHP 8.4**.
2. Suba o banco de dados (via Herd Pro, DBngin, etc) e edite os dados de conexão `DB_*` no arquivo `.env`.
3. **Instalação e setup base:**
   ```bash
   composer install
   php artisan key:generate
   php artisan migrate --seed
   ```
4. **Vite dev server:**
   ```bash
   npm install
   npm run dev
   ```

Acesse em: `http://operis-crm.test`

---

### Opção 3: Via Laravel Valet (macOS/Linux)

Ideal para desenvolvedores com ambiente Nginx nativo servido pelo Valet.

1. Posicione-se na pasta do projeto e force o link com PHP 8.4:
   ```bash
   valet link operis-crm
   valet isolate php@8.4
   ```
2. Crie a base de dados em seu MySQL/MariaDB local e edite os dados `DB_*` no `.env`.
3. **Instalação e setup base:**
   ```bash
   composer install
   php artisan key:generate
   php artisan migrate --seed
   ```
4. **Vite dev server:**
   ```bash
   npm install
   npm run dev
   ```

Acesse em: `http://operis-crm.test`

---

### 👑 Criação do Usuário Master

Para segurança, não há "seed default" de usuários. O primeiro deve ser originário do Tinker.

*(Se você optou pelo Docker, prefixe com `./vendor/bin/sail`)*

Rode o Tinker:
```bash
php artisan tinker
```
Dentro do console:
```php
User::create([
    'name' => 'Master',
    'email' => 'master@operiscrm.com.br',
    'password' => bcrypt('sua_senha_segura'),
    'role_id' => 1
]);
exit
```

## 🧪 Qualidade de Código e Testes

Antes de submeter código a revisão, os passos abaixo são obrigatórios:

**Backend (Pest Test e Pint):**
```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

**Frontend (ESLint e Prettier):**
```bash
npm run lint
npm run format
```

*(Lembre-se: usuários via Sail precisam colocar `./vendor/bin/sail` à frente dos comandos de CLI)*

## 🏗️ Arquitetura e Convenções Técnicas

O desenvolvimento neste projeto obedece estritamente às regras de arquitetura documentadas em [`AGENTS.md`](AGENTS.md) e [`PROJECT_CONTEXT.md`](PROJECT_CONTEXT.md). 

A destacar:
- **Fluxo de Dados Obrigatório**: `Request → FormRequest (validação) → Controller (fino) → Service (regras de negócio) → Model`.
- **Model e Controllers**: Nunca hospedam regras de negócio.
- **TypeScript & UI**: Utilização exclusiva de Composition API com TS, Wayfinder para navegação e padronização UI baseada no diretório `components/ui/`.
- **Identificadores**: Utilização compulsória de UUIDs para a maioria das URLs públicas.

---
*Para um mapeamento minucioso do CRM, vide o arquivo [TECHNICAL_PLAN.md](TECHNICAL_PLAN.md).*