import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    provinces: [],
    kabkots: [],
    selectedProvince: "",
    selectedKabkot: "",
    wilayah_level: "pusat",
    kd_wilayah: "0",
    username: "",
    password: "",
    confirmPassword: "",
    errors: {
        usernameLength: false,
        usernameUnique: false,
        password: false,
        confirmPassword: false,
        kd_wilayah: false,
    },
    usernameExists: false,

    async init() {
        try {
            let response = await fetch("/api/wilayah");
            let data = await response.json();
            this.provinces = data.provinces || [];
            this.kabkots = data.kabkots || [];
            this.updateWilayah(); // Initialize kd_wilayah based on default wilayah_level
        } catch (error) {
            console.error("Failed to load wilayah data:", error);
        }
    },

    get filteredKabkots() {
        if (!this.selectedProvince) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd === this.selectedProvince
        );
    },

    updateWilayah() {
        if (this.wilayah_level === "pusat") {
            this.kd_wilayah = "0";
            this.selectedProvince = "";
            this.selectedKabkot = "";
        } else if (this.wilayah_level === "provinsi") {
            this.kd_wilayah = this.selectedProvince || "";
            this.selectedKabkot = "";
        } else if (this.wilayah_level === "kabkot") {
            this.kd_wilayah =
                this.selectedKabkot || this.selectedProvince || "";
        }
    },

    async checkUsername() {
        try {
            let response = await fetch(
                `/api/check-username?username=${this.username}`
            );
            let data = await response.json();
            this.usernameExists = data.exists;
        } catch (error) {
            console.error("Failed to check username:", error);
        }
    },

    async validateForm() {
        this.errors.usernameLength = this.username.length < 6;
        this.errors.password = this.password.length < 6;
        this.errors.confirmPassword = this.password !== this.confirmPassword;
        this.errors.kd_wilayah =
            (this.wilayah_level === "provinsi" && !this.selectedProvince) ||
            (this.wilayah_level === "kabkot" && !this.selectedKabkot);

        await this.checkUsername();
        this.errors.usernameUnique = this.usernameExists;

        if (
            !this.errors.usernameLength &&
            !this.errors.usernameUnique &&
            !this.errors.password &&
            !this.errors.confirmPassword &&
            !this.errors.kd_wilayah
        ) {
            this.$el.submit();
        }
    },
}));

Alpine.start();
