import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./node_modules/flowbite/**/*.js",
    ],

    darkMode: "class",
    theme: {
        extend: {
            colors: {
                primary: {
                    50: "#fffbeb",
                    100: "#fef3c7",
                    200: "#fde68a",
                    300: "#fcd34d",
                    400: "#fbbf24",
                    500: "#f59e0b",
                    600: "#d97706",
                    700: "#b45309",
                    800: "#92400e",
                    900: "#78350f",
                    950: "#451a03",
                },

                // primary: {
                //     50: "#F3FAF7",
                //     100: "#DEF7EC",
                //     200: "#BCF0DA",
                //     300: "#84E1BC",
                //     400: "#31C48D",
                //     500: "#0E9F6E",
                //     600: "#057A55",
                //     700: "#046C4E",
                //     800: "#03543F",
                //     900: "#014737",
                // },
            },
            fontFamily: {
                sans: ["Figtree", "Noto Sans", ...defaultTheme.fontFamily.sans],
                body: ["Noto Sans", ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [require("@tailwindcss/forms"), require("flowbite/plugin")],
};
