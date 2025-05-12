import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    username: "",
    password: "",
    error: "",

    async submitLogin() {
        this.error = "";

        if (this.username.length < 6 || this.password.length < 6) {
            // Simulate network delay
            await new Promise((resolve) => setTimeout(resolve, 600));
            this.error = "Kombinasi antara username dan password salah.";
            return;
        }

        try {
            const response = await fetch("/login", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'input[name="_token"]'
                    ).value,
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    username: this.username,
                    password: this.password,
                }),
            });

            if (!response.ok) {
                const data = await response.json();
                this.error = data.message || "Terjadi kesalahan.";
                return;
            }

            // Redirect on success
            window.location.href = "/dashboard";
        } catch (err) {
            this.error = "Gagal terhubung ke server.";
        }
    },
}));

Alpine.start();
