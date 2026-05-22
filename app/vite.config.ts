import {defineConfig} from "vite";
import react from "@vitejs/plugin-react";
import {federation} from "@module-federation/vite";
import path from "path";

export default defineConfig({
    plugins: [
        react(),
        federation({
            name: "admin_host",
            remotes: {
                // Remotes are loaded dynamically at runtime — not declared here.
                // useModules() fetches the list from the backend and calls loadRemote().
            },
            exposes: {
                "./EntityForm": "./src/components/EntityForm/index.ts",
                "./List": "./src/components/List/List.tsx",
                "./List/utils": "./src/components/List/utils/convertListStateToQueryParameters.ts",
                "./DataTable": "./src/components/DataTable/index.ts",
                "./primitives/Button": "./src/components/primitives/Button.tsx",
                "./primitives/Badge": "./src/components/primitives/Badge.tsx",
                "./primitives/Slug": "./src/components/primitives/Slug.tsx",
                "./primitives/Modal": "./src/components/primitives/Modal.tsx",
                "./utils/apiCall": "./src/utils/apiCall.ts",
                "./utils/notify": "./src/lib/notify.ts",
                "./utils/errors": "./src/utils/errors.ts",
                "./AdminCore": "./src/modules/AdminCoreContext.tsx",
            },
            shared: {
                react: {singleton: true},
                "react-dom": {singleton: true},
                "react-router": {singleton: true},
                "@mantine/core": {singleton: true},
                "@mantine/hooks": {singleton: true},
                "@mantine/form": {singleton: true},
                "@mantine/dates": {singleton: true},
                "dayjs": {singleton: true},
                "@mantine/notifications": {singleton: true},
                "@mantine/modals": {singleton: true},
                "@tanstack/react-query": {singleton: true},
                "clsx": {singleton: true},
                "@module-federation/runtime": {singleton: true},
            },
        }),
    ],
    base: "/static/app/",
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "src")
        }
    },
    build: {
        outDir: "../../static/app",
        emptyOutDir: true,
        manifest: true,
        target: "esnext",
    },
    server: {
        cors: {
            origin: ["http://bc.local", "https://bc.local"],
            credentials: true
        },
        origin: "http://localhost:5173",
        proxy: {
            "/api": "http://bc.local"
        }
    },
    css: {
        modules: {
            localsConvention: "camelCaseOnly"
        }
    }
});
