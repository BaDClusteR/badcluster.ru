import {defineConfig} from "vite";
import react from "@vitejs/plugin-react";
import path from "path";

export default defineConfig({
    plugins: [react()],
    base: "/static/app/",
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "src")
        }
    },
    build: {
        outDir: "../../static/app",
        emptyOutDir: true,
        manifest: true
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
