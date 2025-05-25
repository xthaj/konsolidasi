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
    message: "",
    currentPage: 1,
    lastPage: 1,
    totalData: 0,
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
        initial_role_label: "",
        is_admin: false,
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
    confirmMessage: "",
    modalMessage: "",
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
            this.message = "Failed to load wilayah data";
        } finally {
            this.loading = false;
        }
    },

    // Map numeric level to string
    getLevelString(level) {
        switch (parseInt(level)) {
            case 0:
                return "Admin Pusat";
            case 1:
                return "Operator Pusat";
            case 2:
                return "Admin Provinsi";
            case 3:
                return "Operator Provinsi";
            case 4:
                return "Admin Kabupaten/Kota";
            case 5:
                return "Operator Kabupaten/Kota";
            default:
                return "Unknown";
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
        return true; // Simplified; add specific validation if needed
    },

    async getWilayahUsers(isFilterSubmit = false) {
        if (!this.checkFormValidity()) return;
        this.message = "";
        if (isFilterSubmit) {
            this.currentPage = 1; // Reset to page 1 only for filter submissions
        }
        try {
            const params = new URLSearchParams();
            if (this.search) params.append("search", this.search);
            if (this.wilayahLevel)
                params.append("level_wilayah", this.wilayahLevel);
            if (this.kd_wilayah && this.wilayahLevel !== "pusat") {
                params.append("kd_wilayah", this.kd_wilayah);
            }
            params.append("page", this.currentPage);

            const response = await fetch(`/api/users?${params.toString()}`);
            const result = await response.json();

            if (response.status === 200) {
                this.message = result.message;
                this.usersData = result.data.users;
                this.currentPage = result.data.current_page;
                this.lastPage = result.data.last_page;
                this.totalData = result.data.total;
            } else {
                this.message = result.message || "Failed to fetch users";
                this.usersData = [];
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
            }
        } catch (error) {
            console.error("Fetch error:", error);
            this.message = "Terjadi kesalahan saat mengambil data pengguna.";
            this.usersData = [];
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
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
        if (this.wilayahLevel === "pusat") {
            this.kd_wilayah = "0";
        } else {
            this.kd_wilayah =
                this.selectedKabkot || this.selectedProvince || "";
        }
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

            if (response.status !== 201) {
                this.failMessage = data.message || "Failed to add user";
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
                return;
            }

            this.usersData.push(data.data);
            this.successMessage = "Berhasil menambah pengguna!";
            this.$dispatch("open-modal", "success-update-bulan-tahun");
            this.$dispatch("close");
            this.getWilayahUsers();
        } catch (error) {
            this.failMessage = "An unexpected error occurred: " + error.message;
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
        }
    },

    openEditUserModal(user) {
        this.editUser.user_id = user.user_id;
        this.editUser.username = user.username;
        this.editUser.nama_lengkap = user.nama_lengkap;
        this.editUser.kd_level = user.kd_level; // Numeric level (e.g., 0)
        this.editUser.level = user.level; // Display label (e.g., "Admin Pusat")
        this.editUser.kd_wilayah = user.kd_wilayah;
        this.editUser.nama_wilayah =
            user.nama_wilayah ||
            (user.kd_wilayah === "0" ? "Pusat" : "Unknown");
        this.editUser.wilayah_level =
            user.kd_wilayah === "0"
                ? "pusat"
                : user.kd_level === 2 || user.kd_level === 3
                ? "provinsi"
                : "kabkot";
        this.editUser.selected_province =
            user.kd_level === 2 || user.kd_level === 3 ? user.kd_wilayah : "";
        this.editUser.selected_kabkot =
            user.kd_level === 4 || user.kd_level === 5 ? user.kd_wilayah : "";
        this.editUser.password = "";
        this.editUser.confirmPassword = "";
        // Set initial_role_label for checkbox based on kd_level
        this.editUser.initial_role_label = [0, 2, 4].includes(
            parseInt(this.editUser.kd_level)
        )
            ? "Ganti menjadi Operator"
            : "Ganti menjadi Admin";
        this.editUser.role_toggle = false; // Checkbox state
        this.editUser.errors = {};
        this.$dispatch("open-modal", "edit-user");
    },

    async updateUserAttribute(attribute) {
        this.editUser.errors = {};

        // Validate input based on attribute
        if (attribute === "username") {
            if (this.editUser.username.length < 7) {
                this.editUser.errors.usernameLength =
                    "Username harus lebih dari 6 karakter.";
                return;
            }
        } else if (attribute === "password") {
            if (this.editUser.password && this.editUser.password.length < 6) {
                this.editUser.errors.password =
                    "Password minimal sepanjang 6 karakter.";
                return;
            }
            if (this.editUser.password !== this.editUser.confirmPassword) {
                this.editUser.errors.confirmPassword =
                    "Password dan konfirmasi password berbeda.";
                return;
            }
        } else if (attribute === "wilayah") {
            if (
                this.editUser.wilayah_level !== "pusat" &&
                !this.editUser.kd_wilayah
            ) {
                this.editUser.errors.kd_wilayah = "Satuan kerja belum dipilih.";
                return;
            }
        }

        try {
            // Prepare payload for the specific attribute
            const payload = {};
            if (attribute === "username") {
                payload.username = this.editUser.username;
            } else if (attribute === "nama_lengkap") {
                payload.nama_lengkap = this.editUser.nama_lengkap;
            } else if (attribute === "password") {
                if (!this.editUser.password) {
                    return; // Skip if password is empty
                }
                if (this.editUser.user_id) {
                    payload.user_id = this.editUser.user_id; // Include user_id for updating another user
                }
                payload.password = this.editUser.password;
                payload.password_confirmation = this.editUser.confirmPassword;
            } else if (attribute === "role") {
                // Toggle kd_level between admin and operator based on checkbox
                const currentLevel = parseInt(this.editUser.kd_level);
                if ([0, 1].includes(currentLevel)) {
                    // Pusat
                    payload.level = this.editUser.role_toggle
                        ? currentLevel === 0
                            ? 1
                            : 0
                        : currentLevel;
                } else if ([2, 3].includes(currentLevel)) {
                    // Provinsi
                    payload.level = this.editUser.role_toggle
                        ? currentLevel === 2
                            ? 3
                            : 2
                        : currentLevel;
                } else if ([4, 5].includes(currentLevel)) {
                    // Kabupaten/Kota
                    payload.level = this.editUser.role_toggle
                        ? currentLevel === 4
                            ? 5
                            : 4
                        : currentLevel;
                }
            } else if (attribute === "wilayah") {
                payload.kd_wilayah =
                    this.editUser.wilayah_level === "pusat"
                        ? "0"
                        : this.editUser.kd_wilayah;
                // Set level based on wilayah_level, preserving admin/operator status
                const currentLevel = parseInt(this.editUser.kd_level);
                const isAdmin = [0, 2, 4].includes(currentLevel);
                payload.level =
                    this.editUser.wilayah_level === "pusat"
                        ? isAdmin
                            ? 0
                            : 1
                        : this.editUser.wilayah_level === "provinsi"
                        ? isAdmin
                            ? 2
                            : 3
                        : isAdmin
                        ? 4
                        : 5;
            }

            // Use different endpoint and method for password update
            const url =
                attribute === "password"
                    ? "/profile/password"
                    : `/api/users/${this.editUser.user_id}`;
            const method = attribute === "password" ? "POST" : "PUT";

            const response = await fetch(url, {
                method: method,
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (response.ok) {
                if (attribute === "password") {
                    // Clear password fields after successful update
                    this.editUser.password = "";
                    this.editUser.confirmPassword = "";
                } else {
                    this.getWilayahUsers();
                    if (attribute === "wilayah") {
                        // Update nama_wilayah based on wilayah_level
                        if (this.editUser.wilayah_level === "pusat") {
                            this.editUser.nama_wilayah = "Pusat";
                        } else if (this.editUser.wilayah_level === "provinsi") {
                            const province = this.provinces.find(
                                (p) =>
                                    p.kd_wilayah ===
                                    this.editUser.selected_province
                            );
                            this.editUser.nama_wilayah = province
                                ? province.nama_wilayah
                                : "Unknown";
                        } else if (this.editUser.wilayah_level === "kabkot") {
                            const kabkot = this.editFilteredKabkots.find(
                                (k) =>
                                    k.kd_wilayah ===
                                    this.editUser.selected_kabkot
                            );
                            this.editUser.nama_wilayah = kabkot
                                ? kabkot.nama_wilayah
                                : "Unknown";
                        }
                    }
                    if (payload.level !== undefined) {
                        this.editUser.kd_level = payload.level; // Update kd_level
                        // Update level label
                        this.editUser.level =
                            payload.level === 0
                                ? "Admin Pusat"
                                : payload.level === 1
                                ? "Operator Pusat"
                                : payload.level === 2
                                ? "Admin Provinsi"
                                : payload.level === 3
                                ? "Operator Provinsi"
                                : payload.level === 4
                                ? "Admin Kabupaten/Kota"
                                : "Operator Kabupaten/Kota";
                        // Update initial_role_label
                        this.editUser.initial_role_label = [0, 2, 4].includes(
                            payload.level
                        )
                            ? "Ganti menjadi Operator"
                            : "Ganti menjadi Admin";
                        this.editUser.role_toggle = false; // Reset checkbox
                    }
                }
                this.modalMessage =
                    result.message || `Data ${attribute} berhasil diperbarui`;
                this.$dispatch("open-modal", "success-modal");
            } else {
                // Handle specific backend errors
                if (result.errors && result.errors.username) {
                    this.editUser.errors.usernameUnique =
                        result.errors.username[0];
                }
                this.modalMessage =
                    result.message || `Gagal memperbarui ${attribute}`;
                this.$dispatch("open-modal", "error-modal");
            }
        } catch (error) {
            console.error("Update error:", error);
            this.modalMessage = `Terjadi kesalahan saat memperbarui ${attribute}.`;
            this.$dispatch("open-modal", "error-modal");
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

                const result = await response.json();

                if (response.status !== 200) {
                    this.modalMessage =
                        result.message || `Gagal menghapus pengguna`;
                    this.$dispatch("open-modal", "error-modal");
                    return;
                }

                this.getWilayahUsers();

                this.modalMessage =
                    result.message || `Pengguna "${username}" berhasil dihapus`;
                this.$dispatch("open-modal", "success-modal");
            } catch (error) {
                this.failMessage =
                    "An unexpected error occurred: " + error.message;
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
            }
        };
        this.$dispatch("open-modal", "confirm-action");
    },
}));

Alpine.start();
