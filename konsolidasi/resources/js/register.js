import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    nama_lengkap: "",
    username: "",
    password: "",
    confirmPassword: "",
    wilayah_level: "pusat",
    selectedProvince: "",
    selectedKabkot: "",
    kd_wilayah: "0",
    level: 1,
    provinces: [],
    kabkots: [],
    errors: {
        nama_lengkap: false,
        username: false,
        password: false,
        confirmPassword: false,
        kd_wilayah: false,
        level: false,
    },
    hasInteracted: false,

    async init() {
        try {
            const wilayahResponse = await fetch("/api/wilayah");
            if (!wilayahResponse.ok) {
                throw new Error(
                    `HTTP error! status: ${wilayahResponse.status}`
                );
            }
            const wilayahData = await wilayahResponse.json();
            this.provinces = wilayahData.data.provinces || [];
            this.kabkots = wilayahData.data.kabkots || [];
            this.updateWilayah();
        } catch (error) {
            console.error("Failed to load wilayah data:", error);
            this.provinces = [];
            this.kabkots = [];
        }
    },

    get filteredKabkots() {
        if (this.wilayah_level !== "kabkot" || !this.selectedProvince) {
            return [];
        }
        return this.kabkots.filter(
            (kabkot) => kabkot.parent_kd === this.selectedProvince
        );
    },

    updateWilayah() {
        if (this.wilayah_level === "pusat") {
            this.kd_wilayah = "0";
            this.errors.kd_wilayah = false;
        } else if (this.wilayah_level === "provinsi" && this.selectedProvince) {
            this.kd_wilayah = this.selectedProvince;
            this.errors.kd_wilayah = false;
        } else if (
            this.wilayah_level === "kabkot" &&
            this.selectedProvince &&
            this.selectedKabkot
        ) {
            this.kd_wilayah = this.selectedKabkot;
            this.errors.kd_wilayah = false;
        } else {
            this.kd_wilayah = "";
            this.errors.kd_wilayah = true;
        }
        // console.log(
        //     "updateWilayah: wilayah_level=",
        //     this.wilayah_level,
        //     "level=",
        //     this.level,
        //     "kd_wilayah=",
        //     this.kd_wilayah
        // );
        this.updateLevel();
    },

    updateLevel() {
        // console.log("updateLevel: wilayah_level=", this.wilayah_level);
        if (this.wilayah_level === "pusat") {
            this.level = 1;
        } else if (this.wilayah_level === "provinsi") {
            this.level = 3;
        } else if (this.wilayah_level === "kabkot") {
            this.level = 5;
        }
        // console.log("updateLevel: final level=", this.level);
    },

    validateForm() {
        if (this.wilayah_level === "pusat") {
            this.errors.kd_wilayah = false;
        } else if (this.wilayah_level === "provinsi") {
            this.errors.kd_wilayah = !this.selectedProvince;
        } else if (this.wilayah_level === "kabkot") {
            this.errors.kd_wilayah =
                !this.selectedProvince || !this.selectedKabkot;
        }
        if (!this.errors.kd_wilayah) {
            this.$el.submit();
        }
    },

    validateNamaLengkap() {
        if (!this.nama_lengkap) {
            this.errors.nama_lengkap = "Nama lengkap wajib diisi.";
        } else if (this.nama_lengkap.length > 255) {
            this.errors.nama_lengkap = "Nama lengkap terlalu panjang.";
        } else {
            this.errors.nama_lengkap = false;
        }
    },

    validateUsername() {
        const regex = /^[a-zA-Z0-9_]+$/;
        if (!this.username) {
            this.errors.username = "Username wajib diisi.";
        } else if (this.username.length < 7) {
            this.errors.username = "Username harus lebih dari 6 karakter.";
        } else if (this.username.length > 255) {
            this.errors.username = "Username terlalu panjang.";
        } else if (!regex.test(this.username)) {
            this.errors.username =
                "Username hanya boleh berisi huruf, angka, dan underscore.";
        } else {
            this.errors.username = false;
        }
    },

    validatePassword() {
        if (!this.password) {
            this.errors.password = "Password wajib diisi.";
        } else if (this.password.length < 6) {
            this.errors.password = "Password minimal sepanjang 6 karakter.";
            // } else if (this.password.length > 255) {
            //     this.errors.password = "Password terlalu panjang.";
        } else {
            this.errors.password = false;
        }
        // Validate confirmation
        if (this.confirmPassword && this.password !== this.confirmPassword) {
            this.errors.confirmPassword =
                "Password dan konfirmasi password berbeda.";
        } else if (this.confirmPassword && this.password) {
            this.errors.confirmPassword = false;
        } else if (!this.confirmPassword && this.password) {
            this.errors.confirmPassword = "Konfirmasi password wajib diisi.";
        } else {
            this.errors.confirmPassword = false;
        }
    },

    get hasErrors() {
        return Object.values(this.errors).some((error) => error !== false);
    },
}));

Alpine.start();
