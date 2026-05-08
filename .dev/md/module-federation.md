# Module Federation: динамическая подгрузка модулей

## Общая архитектура

Админка состоит из **хоста** (ядро, `app/`) и **remote-модулей** (плагины, `modules/<Name>/app/`). Модули собираются отдельно и загружаются в рантайме без пересборки хоста.

```
app/                          ← Хост (ядро)
├── vite.config.ts            ← MF plugin: exposes + shared
├── src/
│   ├── main.tsx              ← init() MF runtime
│   ├── App.tsx               ← Динамические routes из модулей
│   ├── layout/
│   │   └── AdminLayout.tsx   ← Backend-driven навигация + AdminCoreProvider
│   ├── modules/
│   │   ├── types.ts          ← NavItemDescriptor, ModuleDescriptor, ResolvedModule
│   │   ├── useModules.ts     ← Фетч nav + modules, загрузка remote entry
│   │   ├── loader.tsx        ← registerRemotes() + loadRemote()
│   │   └── AdminCoreContext.tsx  ← React Context с core-компонентами
│   └── components/           ← EntityForm, List, DataTable, primitives

modules/Blog/app/             ← Remote-модуль (пример)
├── vite.config.ts            ← MF plugin: exposes ./routes, shared с import: false
├── package.json              ← devDeps для сборки, peerDeps = shared
├── tsconfig.json
└── src/
    ├── index.ts              ← export default BlogRoutes
    ├── routes.tsx             ← <Routes> с вложенными <Route>
    ├── admin/
    │   ├── useAdminCore.ts   ← Хук для доступа к core-компонентам хоста
    │   └── types.ts          ← Локальные типы (EntityForm, List, ColumnDef и т.д.)
    ├── Posts/                ← Страница списка
    └── Post/                 ← Страница редактирования
```

## Как это работает (runtime flow)

1. **Хост стартует** → `main.tsx` вызывает `init({ name: 'admin_host' })` для MF runtime
2. **`useModules()`** фетчит `GET /admin/api/modules` — получает навигацию и список модулей
3. **`loader.tsx`** для каждого модуля вызывает `registerRemotes()` с URL remote entry, затем `loadRemote('<id>/routes')` — получает React-компонент
4. **`App.tsx`** рендерит `<Route path="<mod.path>/*" element={<mod.component />} />` для каждого модуля
5. **`AdminLayout`** оборачивает `<Outlet>` в `<AdminCoreProvider>` — все модули получают доступ к core-компонентам через `useAdminCore()`
6. **Модуль** при рендере вызывает `useAdminCore()` — получает EntityForm, List, apiCall, notify и т.д. через React Context

## Backend API

### `GET /admin/api/modules`

```json
{
  "nav": [
    { "label": "Dashboard", "path": "/admin", "icon": "dashboard" },
    { "label": "Блогпосты", "path": "/admin/posts", "icon": "file" },
    {
      "label": "Users", "icon": "users",
      "children": [
        { "label": "All users", "path": "/admin/users", "icon": "users" },
        { "label": "Roles", "path": "/admin/users/roles", "icon": "shield" }
      ]
    }
  ],
  "modules": [
    {
      "id": "blog",
      "path": "posts",
      "remoteEntry": "/static/modules/blog/remoteEntry.js"
    }
  ]
}
```

- **`nav`** — полное дерево навигации (рендерится в сайдбаре). Иконки маппятся на Tabler Icons через `iconMap` в `AdminLayout.tsx`.
- **`modules`** — список remote-модулей для загрузки. `id` — имя MF-контейнера, `path` — route prefix (относительно `/admin/`), `remoteEntry` — URL скомпилированного JS.

## Создание нового модуля

### 1. Структура директории

```
modules/<Name>/app/
├── package.json
├── vite.config.ts
├── tsconfig.json
└── src/
    ├── index.ts          ← Точка входа: export default MyRoutes
    ├── routes.tsx         ← Компонент с <Routes>
    └── admin/
        ├── useAdminCore.ts   ← Копия хука (одинаковая для всех модулей)
        └── types.ts          ← Локальные типы core-компонентов
```

### 2. package.json

```json
{
  "name": "@admin/<name>",
  "private": true,
  "type": "module",
  "scripts": {
    "build": "vite build"
  },
  "devDependencies": {
    "@module-federation/vite": "^1.15.0",
    "@vitejs/plugin-react": "^6.0.0",
    "typescript": "^5.0.0",
    "vite": "^8.0.0"
  },
  "peerDependencies": {
    "react": "^19.0.0",
    "react-dom": "^19.0.0",
    "react-router": "^7.0.0",
    "@mantine/core": "^9.0.0",
    "@mantine/hooks": "^9.0.0",
    "@mantine/form": "^9.0.0",
    "@mantine/dates": "^9.0.0",
    "@mantine/notifications": "^9.0.0",
    "@tanstack/react-query": "^5.0.0",
    "clsx": "^2.0.0"
  }
}
```

### 3. vite.config.ts

```ts
import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import { federation } from "@module-federation/vite";

export default defineConfig({
  plugins: [
    react(),
    federation({
      name: "<name>",                    // Должен совпадать с id в API
      filename: "remoteEntry.js",
      exposes: {
        "./routes": "./src/index.ts",
      },
      shared: {
        // import: false — не бандлить свои копии, брать из хоста
        react: { singleton: true, import: false },
        "react-dom": { singleton: true, import: false },
        "react-router": { singleton: true, import: false },
        "@mantine/core": { singleton: true, import: false },
        "@mantine/hooks": { singleton: true, import: false },
        "@mantine/form": { singleton: true, import: false },
        "@mantine/dates": { singleton: true, import: false },
        "dayjs": { singleton: true, import: false },
        "@mantine/notifications": { singleton: true, import: false },
        "@tanstack/react-query": { singleton: true, import: false },
        clsx: { singleton: true, import: false },
      },
    }),
  ],
  base: "/static/modules/<name>/",
  build: {
    outDir: "../../../static/modules/<name>",  // Путь к папке со статикой
    emptyOutDir: true,
    target: "esnext",
    rollupOptions: { input: {} },               // Без index.html
  },
  css: {
    modules: { localsConvention: "camelCaseOnly" },
  },
});
```

### 4. Точка входа (src/index.ts)

```ts
export { MyRoutes as default } from './routes';
```

### 5. Routes (src/routes.tsx)

```tsx
import { Routes, Route } from 'react-router';
import ListPage from './ListPage';
import EditPage from './EditPage';

export function MyRoutes() {
  return (
    <Routes>
      <Route index element={<ListPage />} />
      <Route path=":id" element={<EditPage />} />
    </Routes>
  );
}
```

### 6. Использование core-компонентов

```tsx
import { useAdminCore } from '../admin/useAdminCore';
import type { ColumnDef, ListDataProvider } from '../admin/types';

export default function ListPage() {
  const { List, BadgeGray, BadgeGreen, apiCall, notify } = useAdminCore();

  // List, apiCall, notify — те же инстансы что и в хосте
  return <List columns={...} dataProvider={...} />;
}
```

### 7. useAdminCore.ts (одинаковый для всех модулей)

```ts
import { useContext } from 'react';

export interface AdminCore {
  EntityForm: any;
  List: any;
  convertListStateToQueryParameters: (state: any) => any;
  BadgeGray: any;
  BadgeGreen: any;
  apiCall: (...args: any[]) => Promise<any>;
  notify: {
    success: (...args: any[]) => void;
    error: (...args: any[]) => void;
    info: (...args: any[]) => void;
    warning: (...args: any[]) => void;
  };
}

export function useAdminCore(): AdminCore {
  const ctx = (globalThis as any).__adminCoreContext;
  if (!ctx) throw new Error('AdminCoreContext not found.');
  const value = useContext(ctx);
  if (!value) throw new Error('useAdminCore: no provider found.');
  return value;
}
```

### 8. Сборка и деплой

```bash
cd modules/<Name>/app
npm install
npm run build
# Результат: static/modules/<name>/remoteEntry.js + assets/
```

### 9. Регистрация на бэкенде

Добавить в ответ `GET /admin/api/modules`:
- В `nav` — пункт меню
- В `modules` — запись с `id`, `path`, `remoteEntry`

## Как хост передаёт компоненты модулям

Хост НЕ использует MF для передачи core-компонентов модулям. Вместо этого используется **React Context через глобальную ссылку**:

1. `AdminCoreContext.tsx` создаёт контекст и сохраняет его на `globalThis.__adminCoreContext`
2. `AdminCoreProvider` оборачивает `<Outlet>` в `AdminLayout`
3. Модуль читает тот же контекст через `globalThis.__adminCoreContext` + `useContext()`

Это работает потому что:
- React — shared singleton, один инстанс на всё приложение
- Context object — один и тот же (через globalThis), значит `useContext` находит провайдер
- Компоненты передаются по ссылке, а не бандлятся в модуль

## Что расшарено (shared dependencies)

| Зависимость | Роль |
|---|---|
| `react`, `react-dom` | Ядро React |
| `react-router` | Роутинг |
| `@mantine/core`, `hooks`, `form`, `dates`, `notifications` | UI-библиотека |
| `@tanstack/react-query` | Data fetching |
| `dayjs` | Зависимость @mantine/dates |
| `clsx` | CSS classnames |

В модуле все shared помечены `import: false` — модуль не бандлит fallback-копии, полагаясь на то, что хост их предоставит.

## Важные нюансы

- **Навигация полностью на бэкенде** — хост не содержит хардкодных пунктов меню, всё приходит из `GET /admin/api/modules`
- **Fallback route** рендерится только после загрузки модулей (`loading === false`), чтобы избежать редиректа на `/admin` при обновлении страницы
- **MF runtime** инициализируется в `main.tsx` до любых других импортов
- **`remoteEntry.js`** загружается как ES module (`type: 'module'`)
- **CSS modules** используют `camelCaseOnly` конвенцию — одинаковую в хосте и модулях
- При изменении **только модуля** — пересобирается только модуль, хост не трогаем
- При изменении **core-компонентов** хоста — пересобирается хост; модули подхватывают изменения автоматически (shared deps + context)