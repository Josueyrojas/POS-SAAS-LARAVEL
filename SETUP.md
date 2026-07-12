# POS SaaS Multi-Tenant — Laravel + Supabase

Reconstrucción en **Laravel** del POS multi-inquilino, con **Supabase**
(PostgreSQL administrado) como base de datos y **autenticación de sesión nativa**.

## Cómo encaja (equivalencias con la versión anterior)

| Concepto | Antes (Next.js/Prisma) | Ahora (Laravel) |
|---|---|---|
| Esquema | `schema.prisma` | Migrations en `database/migrations` |
| Cliente con alcance `forBusiness` | `$extends` | Trait `BelongsToBusiness` + `BusinessScope` global scope |
| Contexto de inquilino | `businessId` en sesión | `TenantContext` (singleton) fijado por middleware |
| Protección de rutas | middleware Next | Middleware `superadmin` / `business` |
| Auth | Auth.js | Sesiones de Laravel + `password_hash` |
| Vistas | React/Blade N/A | Blade + Tailwind/Alpine por CDN |

El aislamiento sigue siendo **automático**: cuando el middleware `business`
fija el `TenantContext`, todo modelo con `BelongsToBusiness` filtra e inyecta el
`business_id` solo. El Super Admin no fija contexto → consultas globales.

## Instalación

### 1. Crea un proyecto Laravel limpio y superpón estos archivos

```bash
composer create-project laravel/laravel pos-saas
```

Copia el contenido de esta carpeta ENCIMA del proyecto recién creado,
sobrescribiendo cuando pregunte. Archivos que se sobrescriben a propósito:
`bootstrap/app.php`, `routes/web.php`, `app/Providers/AppServiceProvider.php`,
`app/Http/Controllers/Controller.php`,
`database/migrations/0001_01_01_000000_create_users_table.php`,
`database/seeders/DatabaseSeeder.php`, y las vistas en `resources/views`.
El resto son archivos nuevos.

### 2. Configura Supabase

En Supabase: **Project Settings → Database → Connection**. Usa el
**Session pooler** o la **Direct connection** (puerto **5432**). No uses el
Transaction pooler (6543): rompe los prepared statements de Laravel.

Copia `.env.example` a `.env` y rellena `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`
(el `DB_SSLMODE=require` es obligatorio en Supabase). **Nunca pegues la
contraseña real en este archivo ni en ningún doc del repo** — solo va en
`.env`, que está excluido por `.gitignore`.

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Crea las tablas y los datos de prueba

```bash
php artisan migrate
php artisan db:seed
```

### 4. Levanta la app

```bash
php artisan serve
```

Abre `http://localhost:8000`.

## Recorrido

- `/` — portada pública con los negocios activos + botón "Iniciar sesión".
- Clic en **Demo Store** → login del negocio → `admin@demo.dev` / `***REMOVED***`
  → POS con el CRUD de productos.
- Botón **Iniciar sesión** → login de plataforma → `root@platform.dev` /
  `***REMOVED***` → panel de Super Admin.

## Notas de arquitectura

- **IDs UUID** (`HasUuids`) en vez de autoincrementales: no revelan volumen ni
  permiten enumeración entre inquilinos (equivale a los `cuid()` de antes).
- **`unique` compuestos por `business_id`**: email y SKU son únicos POR negocio.
- **Dinero como `Decimal(12,2)`**, nunca float.
- **`SaleItem`** guarda `name_snapshot` y `unit_price` congelados: el histórico
  de ventas no cambia aunque el producto se edite o borre después.
- El **Super Admin** (`business_id` null) consulta globalmente; los usuarios de
  negocio quedan acotados a su `business_id` por el global scope.

## Endurecimiento pendiente

- CHECK constraint en `users`: `(role = 'SUPER_ADMIN') = (business_id IS NULL)`.
- Row-Level Security de Postgres (Supabase lo soporta nativamente) como tercera
  capa de aislamiento a nivel de base de datos.
- Compilar Tailwind con Vite para producción (ahora va por CDN para desarrollo).
