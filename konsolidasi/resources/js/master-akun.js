import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    provinces: [],
    kabkots: [],
    selectedProvince: "",
    selectedKabkot: "",

    search: "",
    kd_wilayah: "",
    wilayahLevel: "semua",
    loading: true,
    usersData: [],
    currentPage: 1,
    lastPage: 1,
    wilayah_level: "",
    selected_province: "{{ request('kd_wilayah_provinsi') }}",
    selected_kabkot: "{{ request('kd_wilayah') }}",
    newUser: {
        username: "",
        nama_lengkap: "",
        password: "",
        showPassword: false,
        kd_wilayah: "0",
        wilayah_level: "pusat",
        selected_province: "",
        selected_kabkot: "",
        isAdminCheckbox: false,
        level: 1,
        errors: {
            username: false,
            nama_lengkap: false,
            password: false,
            kd_wilayah: false,
            level: false,
        },
        usernameExists: false,
    },
    editUser: {
        user_id: "",
        username: "",
        nama_lengkap: "",
        password: "",
        confirmPassword: "",
        kd_wilayah: "",
        wilayah_level: "pusat",
        selected_province: "",
        selected_kabkot: "",
        errors: {
            usernameLength: false,
            usernameUnique: false,
            password: false,
            confirmPassword: false,
            kd_wilayah: false,
        },
        usernameExists: false,
    },
    successMessage: "",
    failMessage: "",
    failDetails: null,
    confirmMessage: "",
    confirmDetails: null,
    confirmAction: null,

    async init() {
        this.loading = true;
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
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false;
        }
    },

    updateEditWilayah() {
        if (this.editUser.wilayah_level === "pusat") {
            this.editUser.kd_wilayah = "0";
            this.editUser.selected_province = "";
            this.editUser.selected_kabkot = "";
            this.editUser.errors.kd_wilayah = false;
        } else if (this.editUser.wilayah_level === "provinsi") {
            if (this.editUser.selected_province) {
                this.editUser.kd_wilayah = this.editUser.selected_province;
                this.editUser.errors.kd_wilayah = false;
            } else {
                this.editUser.kd_wilayah = "";
                this.editUser.errors.kd_wilayah = "Satuan kerja belum dipilih.";
            }
            this.editUser.selected_kabkot = "";
        } else if (this.editUser.wilayah_level === "kabkot") {
            if (
                this.editUser.selected_province &&
                this.editUser.selected_kabkot
            ) {
                this.editUser.kd_wilayah = this.editUser.selected_kabkot;
                this.editUser.errors.kd_wilayah = false;
            } else {
                this.editUser.kd_wilayah = "";
                this.editUser.errors.kd_wilayah = "Satuan kerja belum dipilih.";
            }
        }
    },

    updateWilayahOptions() {
        this.selectedProvince = "";
        this.selectedKabkot = "";
        this.updateKdWilayah();
    },

    async checkEditUserUsername() {
        if (!this.editUser.username) return;
        try {
            let response = await fetch(
                `/api/check-username?username=${this.editUser.username}&except=${this.editUser.user_id}`
            );
            let data = await response.json();
            this.editUser.usernameExists = data.exists;
        } catch (error) {
            console.error("Failed to check username:", error);
        }
    },

    checkFormValidity() {
        // if (

        //     !this.wilayahLevel
        // ) {
        //     this.errorMessage =
        //         "Harap isi bulan, tahun, level harga, status, dan level wilayah.";
        //     return false;
        // }
        // if (
        //     this.wilayahLevel === "semua" ||
        //     this.wilayahLevel === "semua-provinsi" ||
        //     this.wilayahLevel === "semua-kabkot"
        // ) {
        //     if (!this.isPusat) {
        //         this.errorMessage =
        //             "Hanya pengguna pusat yang dapat mengakses semua provinsi atau kabupaten/kota.";
        //         return false;
        //     }
        //     return true;
        // }
        // if (this.wilayahLevel === "provinsi" && this.selectedProvince) {
        //     return true;
        // }
        // if (
        //     this.wilayahLevel === "kabkot" &&
        //     this.selectedProvince &&
        //     this.selectedKabkot &&
        // ) {
        return true;
        // }

        // return false;
    },

    async getWilayahUsers() {
        if (!this.checkFormValidity()) return;
        this.errorMessage = "";

        try {
            const params = new URLSearchParams();

            if (this.search) {
                params.append("search", this.search);
            }
            if (this.wilayahLevel) {
                params.append("level_wilayah", this.wilayahLevel);
            }
            if (this.kd_wilayah) {
                params.append("kd_wilayah", this.kd_wilayah);
            }

            // Pagination
            params.append("page", this.currentPage);

            const response = await fetch(`/api/users?${params.toString()}`);
            const result = await response.json();

            if (!response.ok || result.status !== "success") {
                this.errorMessage =
                    result.message || "Gagal mengambil data pengguna.";
                this.usersData = [];
                return;
            }

            this.usersData = result.data.users;
            this.currentPage = result.data.current_page;
            this.lastPage = result.data.last_page;
        } catch (error) {
            console.error("Fetch error:", error);
            this.errorMessage =
                "Terjadi kesalahan saat mengambil data pengguna.";
        }
    },

    get filteredKabkots() {
        if (!this.selectedProvince) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd === this.selectedProvince
        );
    },

    get newUserFilteredKabkots() {
        if (!this.newUser.selected_province) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd === this.newUser.selected_province
        );
    },

    get editFilteredKabkots() {
        if (!this.editUser.selected_province) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd === this.editUser.selected_province
        );
    },

    get newUserHasErrors() {
        return Object.values(this.newUser.errors).some(
            (error) => error !== false
        );
    },

    updateKdWilayah() {
        this.kd_wilayah = this.selectedKabkot || this.selectedProvince || "";
    },

    validateNewUserNamaLengkap() {
        if (!this.newUser.nama_lengkap) {
            this.newUser.errors.nama_lengkap = "Nama lengkap wajib diisi.";
        } else if (this.newUser.nama_lengkap.length > 255) {
            this.newUser.errors.nama_lengkap = "Nama lengkap terlalu panjang.";
        } else {
            this.newUser.errors.nama_lengkap = false;
        }
    },

    async validateNewUserUsername() {
        const regex = /^[a-zA-Z0-9_]+$/;
        if (!this.newUser.username) {
            this.newUser.errors.username = "Username wajib diisi.";
        } else if (this.newUser.username.length < 7) {
            this.newUser.errors.username =
                "Username harus lebih dari 6 karakter.";
        } else if (this.newUser.username.length > 255) {
            this.newUser.errors.username = "Username terlalu panjang.";
        } else if (!regex.test(this.newUser.username)) {
            this.newUser.errors.username =
                "Username hanya boleh berisi huruf, angka, dan underscore.";
        } else {
            await this.checkNewUserUsername();
            this.newUser.errors.username = this.newUser.usernameExists
                ? "Username sudah digunakan."
                : false;
        }
    },

    validateNewUserPassword() {
        if (!this.newUser.password) {
            this.newUser.errors.password = "Password wajib diisi.";
        } else if (this.newUser.password.length < 6) {
            this.newUser.errors.password =
                "Password minimal sepanjang 6 karakter.";
        } else if (this.newUser.password.length > 255) {
            this.newUser.errors.password = "Password terlalu panjang.";
        } else {
            this.newUser.errors.password = false;
        }
    },

    validateNewUserLevel() {
        const validLevels = [0, 1, 2, 3, 4, 5];
        if (!validLevels.includes(this.newUser.level)) {
            this.newUser.errors.level = "Level pengguna tidak valid.";
        } else {
            this.newUser.errors.level = false;
        }
    },

    updateNewUserWilayah() {
        if (this.newUser.wilayah_level === "pusat") {
            this.newUser.kd_wilayah = "0";
            this.newUser.selected_province = "";
            this.newUser.selected_kabkot = "";
            this.newUser.errors.kd_wilayah = false;
        } else if (this.newUser.wilayah_level === "provinsi") {
            if (this.newUser.selected_province) {
                this.newUser.kd_wilayah = this.newUser.selected_province;
                this.newUser.errors.kd_wilayah =
                    this.newUser.kd_wilayah.length > 6
                        ? "Kode wilayah terlalu panjang."
                        : false;
            } else {
                this.newUser.kd_wilayah = "";
                this.newUser.errors.kd_wilayah = "Satuan kerja belum dipilih.";
            }
        } else if (this.newUser.wilayah_level === "kabkot") {
            if (
                this.newUser.selected_province &&
                this.newUser.selected_kabkot
            ) {
                this.newUser.kd_wilayah = this.newUser.selected_kabkot;
                this.newUser.errors.kd_wilayah =
                    this.newUser.kd_wilayah.length > 6
                        ? "Kode wilayah terlalu panjang."
                        : false;
            } else {
                this.newUser.kd_wilayah = "";
                this.newUser.errors.kd_wilayah = "Satuan kerja belum dipilih.";
            }
        }
    },

    updateNewUserLevel() {
        if (this.newUser.wilayah_level === "pusat") {
            this.newUser.level = this.newUser.isAdminCheckbox ? 0 : 1;
        } else if (this.newUser.wilayah_level === "provinsi") {
            this.newUser.level = this.newUser.isAdminCheckbox ? 2 : 3;
        } else if (this.newUser.wilayah_level === "kabkot") {
            this.newUser.level = this.newUser.isAdminCheckbox ? 4 : 5;
        }
        this.validateNewUserLevel();
    },

    openAddUserModal() {
        this.newUser = {
            username: "",
            nama_lengkap: "",
            password: "",
            showPassword: false,
            kd_wilayah: "0",
            wilayah_level: "pusat",
            selected_province: "",
            selected_kabkot: "",
            isAdminCheckbox: false,
            level: 1,
            errors: {
                username: false,
                nama_lengkap: false,
                password: false,
                kd_wilayah: false,
                level: false,
            },
            usernameExists: false,
        };
        this.$dispatch("open-modal", "add-user");
    },

    async checkNewUserUsername() {
        if (!this.newUser.username) return;
        try {
            let response = await fetch(
                `/api/check-username?username=${this.newUser.username}`
            );
            let data = await response.json();
            this.newUser.usernameExists = data.exists;
        } catch (error) {
            console.error("Failed to check username:", error);
        }
    },

    async addUser() {
        this.validateNewUserNamaLengkap();
        await this.validateNewUserUsername();
        this.validateNewUserPassword();
        this.updateNewUserWilayah();
        this.validateNewUserLevel();

        if (this.newUserHasErrors) {
            return;
        }

        const userData = {
            nama_lengkap: this.newUser.nama_lengkap,
            username: this.newUser.username,
            kd_wilayah: this.newUser.kd_wilayah,
            level: this.newUser.level,
            password: this.newUser.password,
        };

        try {
            const response = await fetch("/api/users", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify(userData),
            });

            const data = await response.json();

            if (!response.ok) {
                this.failMessage = data.message || "Failed to add user";
                this.failDetails = data.errors || null;
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
                return;
            }

            this.usersData.push(data);
            this.successMessage = "Berhasil menambah pengguna!";
            this.$dispatch("open-modal", "success-update-bulan-tahun");
            this.$dispatch("close");
        } catch (error) {
            this.failMessage = "An unexpected error occurred";
            this.failDetails = { error: error.message };
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
        }
    },

    openEditUserModal(user) {
        this.editUser = {
            user_id: user.user_id,
            username: user.username,
            nama_lengkap: user.nama_lengkap,
            password: "",
            confirmPassword: "",
            kd_wilayah: user.kd_wilayah || "0",
            wilayah_level:
                user.kd_wilayah === "0"
                    ? "pusat"
                    : this.kabkots.some((k) => k.kd_wilayah === user.kd_wilayah)
                    ? "kabkot"
                    : "provinsi",
            selected_province: this.provinces.some(
                (p) => p.kd_wilayah === user.kd_wilayah
            )
                ? user.kd_wilayah
                : this.kabkots.find((k) => k.kd_wilayah === user.kd_wilayah)
                      ?.parent_kd || "",
            selected_kabkot: this.kabkots.some(
                (k) => k.kd_wilayah === user.kd_wilayah
            )
                ? user.kd_wilayah
                : "",
            is_admin: [0, 2, 4].includes(user.level), // Set is_admin based on level (0, 2, 4 are admin levels)
            errors: {
                usernameLength: false,
                usernameUnique: false,
                password: false,
                confirmPassword: false,
                kd_wilayah: false,
            },
            usernameExists: false,
        };
        this.$dispatch("open-modal", "edit-user");
    },

    async updateUser() {
        this.editUser.errors.usernameLength = this.editUser.username.length < 7;
        this.editUser.errors.password =
            this.editUser.password && this.editUser.password.length < 6;
        this.editUser.errors.confirmPassword =
            this.editUser.password &&
            this.editUser.password !== this.editUser.confirmPassword;
        this.editUser.errors.kd_wilayah =
            (this.editUser.wilayah_level === "provinsi" &&
                !this.editUser.selected_province) ||
            (this.editUser.wilayah_level === "kabkot" &&
                !this.editUser.selected_kabkot);

        await this.checkEditUserUsername();
        this.editUser.errors.usernameUnique = this.editUser.usernameExists;

        if (
            this.editUser.errors.usernameLength ||
            this.editUser.errors.usernameUnique ||
            this.editUser.errors.password ||
            this.editUser.errors.confirmPassword ||
            this.editUser.errors.kd_wilayah
        ) {
            return;
        }

        const userData = {
            username: this.editUser.username,
            nama_lengkap: this.editUser.nama_lengkap,
            password: this.editUser.password || undefined,
            kd_wilayah: this.editUser.kd_wilayah,
        };

        try {
            const response = await fetch(
                `/api/users/${this.editUser.user_id}`,
                {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                    },
                    body: JSON.stringify(userData),
                }
            );

            const data = await response.json();

            if (!response.ok) {
                this.failMessage = data.message || "Failed to update user";
                this.failDetails = data.errors || null;
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
                return;
            }

            const index = this.usersData.findIndex(
                (u) => u.user_id === data.user_id
            );
            if (index !== -1) this.usersData[index] = data;
            this.successMessage = "User successfully updated!";
            this.$dispatch("open-modal", "success-update-bulan-tahun");
            this.$dispatch("close");
        } catch (error) {
            this.failMessage = "An unexpected error occurred";
            this.failDetails = { error: error.message };
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
        }
    },

    deleteUser(user_id, username) {
        this.confirmMessage = `Apakah Anda yakin ingin menghapus pengguna "${username}"?`;
        this.confirmDetails = "Tindakan ini tidak dapat dibatalkan.";
        this.confirmAction = async () => {
            try {
                const response = await fetch(`/api/users/${user_id}`, {
                    method: "DELETE",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    this.failMessage = data.message || "Failed to delete user";
                    this.failDetails = data.errors || null;
                    this.$dispatch("open-modal", "fail-update-bulan-tahun");
                    return;
                }

                this.successMessage = "Berhasil menghapus pengguna!";
                this.$dispatch("open-modal", "success-update-bulan-tahun");
                setTimeout(() => window.location.reload(), 1000);
            } catch (error) {
                this.failMessage = "An unexpected error occurred";
                this.failDetails = { error: error.message };
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
            }
        };
        this.$dispatch("open-modal", "confirm-action");
    },
}));

Alpine.start();
