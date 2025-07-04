import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    server: {
        // https: true,
        // host: "konsolidasiharga.web.bps.go.id",
    },
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/administrasi/user.js",
                "resources/js/auth/login.js",
                "resources/js/data/edit.js",
                "resources/js/data/finalisasi.js",
                "resources/js/data/upload.js",
                "resources/js/master/alasan.js",
                "resources/js/master/komoditas.js",
                "resources/js/master/wilayah.js",
                "resources/js/rekonsiliasi/pembahasan.js",
                "resources/js/rekonsiliasi/pengisian_skl.js",
                "resources/js/rekonsiliasi/pengisian.js",
                "resources/js/alpine.js",
                "resources/js/app.js",
                "resources/js/bootstrap.js",
                "resources/js/edit-akun.js",
                "resources/js/harmonisasi.js",
                "resources/js/pemilihan.js",
                "resources/js/pengaturan.js",
                "resources/js/register.js",
            ],
            refresh: true,
        }),
    ],
});
