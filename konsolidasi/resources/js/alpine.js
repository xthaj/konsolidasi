import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    username: "",
}));

Alpine.start();
