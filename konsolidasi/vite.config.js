import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/edit-data.js",
                "resources/js/harmonisasi.js",
                "resources/js/pemilihan.js",
                "resources/js/pengaturan.js",
                "resources/js/progres.js",
                "resources/js/register.js",
                "resources/js/upload-data.js",
            ],
            refresh: true,
        }),
    ],
});
