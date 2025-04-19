import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/**/*.{js,jsx,ts,tsx}", // Include all JS/TS files in resources/js
            ],
            refresh: true,
        }),
    ],
});
