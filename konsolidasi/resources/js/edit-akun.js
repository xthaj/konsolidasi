import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: false,
    password: "",
    confirmPassword: "",
    errors: {
        password: false,
        confirmPassword: false,
    },
    modalMessage: "",

    async updatePassword() {
        // Reset errors and messages
        this.errors = { password: false, confirmPassword: false };
        this.modalMessage = "";

        // Client-side validation
        if (this.password && this.password.length < 6) {
            this.errors.password = true;
            return;
        }
        if (this.password !== this.confirmPassword) {
            this.errors.confirmPassword = true;
            return;
        }

        try {
            const response = await fetch("/profile/password", {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify({
                    password: this.password,
                    password_confirmation: this.confirmPassword,
                }),
            });

            const result = await response.json();

            if (response.ok) {
                this.modalMessage =
                    result.message || "Berhasil memperbarui kata sandi.";
                this.$dispatch("open-modal", "success-modal");
                this.password = "";
                this.confirmPassword = "";
            } else {
                this.modalMessage =
                    result.message || "Gagal memperbarui kata sandi.";
                this.$dispatch("open-modal", "error-modal");
            }
        } catch (error) {
            this.modalMessage =
                "Terjadi kesalahan saat memperbarui kata sandi.";
            this.$dispatch("open-modal", "error-modal");
        }
    },
}));

Alpine.start();
