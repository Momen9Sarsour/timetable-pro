// index.blade.php

document.addEventListener("DOMContentLoaded", function () {
    // Dark mode toggle
    const darkModeToggle = document.querySelector(".dark-mode-toggle");
    darkModeToggle.addEventListener("click", function () {
        document.body.classList.toggle("dark-mode");

        // Update icon
        const icon = this.querySelector("i");
        if (document.body.classList.contains("dark-mode")) {
            icon.classList.remove("fa-moon");
            icon.classList.add("fa-sun");
            this.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        } else {
            icon.classList.remove("fa-sun");
            icon.classList.add("fa-moon");
            this.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
        }
    });

    // Sidebar toggle
    const sidebarToggle = document.querySelector(".sidebar-toggle");
    const sidebar = document.querySelector(".sidebar");

    sidebarToggle.addEventListener("click", function () {
        sidebar.classList.toggle("active");
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", function (e) {
        if (
            window.innerWidth < 992 &&
            !e.target.closest(".sidebar") &&
            !e.target.closest(".sidebar-toggle")
        ) {
            sidebar.classList.remove("active");
        }
    });

    // Help button
    const helpBtn = document.querySelector(".help-btn");
    helpBtn.addEventListener("click", function () {
        alert("Help center would open here");
    });

    // Auto close sidebar when resizing to larger screen
    window.addEventListener("resize", function () {
        if (window.innerWidth >= 992) {
            sidebar.classList.remove("active");
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const submenuItems = document.querySelectorAll(
        ".sidebar-menu li.has-submenu > a"
    );

    submenuItems.forEach((item) => {
        item.addEventListener("click", function (event) {
            event.preventDefault(); // منع الانتقال للرابط '#'
            const parentLi = this.parentElement;
            parentLi.classList.toggle("open"); // تبديل كلاس open
        });
    });

    // Optional: Keep submenu open if a child link is active
    const activeSubmenuLink = document.querySelector(
        ".sidebar-menu .submenu li.active"
    );
    if (activeSubmenuLink) {
        const parentSubmenu = activeSubmenuLink.closest("li.has-submenu");
        if (parentSubmenu) {
            parentSubmenu.classList.add("open");
        }
    }
    // --- الكود الخاص بـ Mobile Sidebar Toggle (إذا كان موجوداً) ---
    const sidebarToggle = document.querySelector(".sidebar-toggle");
    const sidebar = document.querySelector(".sidebar");
    const mainContent = document.querySelector(".main-content"); // افترض أن لديك هذا العنصر

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener("click", () => {
            sidebar.classList.toggle("active"); // افترض أن لديك كلاس active للتحكم بالظهور
            if (mainContent) {
                mainContent.classList.toggle("shifted"); // افترض أن لديك كلاس لتحريك المحتوى
            }
        });
    }
    // --- نهاية كود Mobile Sidebar Toggle ---
});

// *********************************************************
// data-entry.blade.php

document.addEventListener("DOMContentLoaded", function () {
    // Dark mode toggle
    const darkModeToggle = document.querySelector(".dark-mode-toggle");
    darkModeToggle.addEventListener("click", function () {
        document.body.classList.toggle("dark-mode");

        // Update icon
        const icon = this.querySelector("i");
        if (document.body.classList.contains("dark-mode")) {
            icon.classList.remove("fa-moon");
            icon.classList.add("fa-sun");
            this.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        } else {
            icon.classList.remove("fa-sun");
            icon.classList.add("fa-moon");
            this.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
        }
    });

    // Mobile sidebar toggle
    const sidebarToggle = document.querySelector(".sidebar-toggle");
    const sidebar = document.querySelector(".sidebar");

    sidebarToggle.addEventListener("click", function () {
        sidebar.classList.toggle("active");
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", function (e) {
        if (
            window.innerWidth < 992 &&
            !e.target.closest(".sidebar") &&
            !e.target.closest(".sidebar-toggle")
        ) {
            sidebar.classList.remove("active");
        }
    });

    // Auto close sidebar when resizing to larger screen
    window.addEventListener("resize", function () {
        if (window.innerWidth >= 992) {
            sidebar.classList.remove("active");
        }
    });

    // Help button
    const helpBtn = document.querySelector(".help-btn");
    helpBtn.addEventListener("click", function () {
        alert("Help center would open here");
    });

    // Form reset functionality
    function resetForm(formId) {
        const form = document.querySelector(`#${formId}`);
        if (form) {
            // Reset all input fields
            const inputs = form.querySelectorAll("input, select, textarea");
            inputs.forEach((input) => {
                if (
                    input.type === "text" ||
                    input.type === "email" ||
                    input.type === "number" ||
                    input.tagName === "TEXTAREA"
                ) {
                    input.value = "";
                } else if (input.type === "checkbox") {
                    input.checked = false;
                } else if (input.tagName === "SELECT") {
                    input.selectedIndex = 0;
                }
            });

            // Hide status messages
            const successMsg = document.querySelector(
                `#${formId}SuccessMessage`
            );
            const errorMsg = document.querySelector(`#${formId}ErrorMessage`);
            if (successMsg) successMsg.style.display = "none";
            if (errorMsg) errorMsg.style.display = "none";
        }
    }

    // Initialize reset buttons
    document.getElementById("resetBtn").addEventListener("click", function () {
        resetForm("courses");
    });

    document
        .getElementById("resetClassroomBtn")
        .addEventListener("click", function () {
            resetForm("classroom");
        });

    document
        .getElementById("resetFacultyBtn")
        .addEventListener("click", function () {
            resetForm("faculty");
        });

    // Form submission handlers
    document.getElementById("saveBtn").addEventListener("click", function () {
        // Validate form
        const courseName = document.getElementById("courseName").value;
        const college = document.getElementById("college").value;

        if (!courseName || !college) {
            document.getElementById("errorMessage").style.display = "block";
            document.getElementById("successMessage").style.display = "none";
        } else {
            document.getElementById("successMessage").style.display = "block";
            document.getElementById("errorMessage").style.display = "none";
        }
    });

    document
        .getElementById("saveClassroomBtn")
        .addEventListener("click", function () {
            // Validate form
            const roomNumber = document.getElementById("roomNumber").value;
            const building = document.getElementById("building").value;

            if (!roomNumber || !building) {
                document.getElementById("classroomErrorMessage").style.display =
                    "block";
                document.getElementById(
                    "classroomSuccessMessage"
                ).style.display = "none";
            } else {
                document.getElementById(
                    "classroomSuccessMessage"
                ).style.display = "block";
                document.getElementById("classroomErrorMessage").style.display =
                    "none";
            }
        });

    document
        .getElementById("saveFacultyBtn")
        .addEventListener("click", function () {
            // Validate form
            const firstName = document.getElementById("firstName").value;
            const lastName = document.getElementById("lastName").value;
            const facultyDepartment =
                document.getElementById("facultyDepartment").value;

            if (!firstName || !lastName || !facultyDepartment) {
                document.getElementById("facultyErrorMessage").style.display =
                    "block";
                document.getElementById("facultySuccessMessage").style.display =
                    "none";
            } else {
                document.getElementById("facultySuccessMessage").style.display =
                    "block";
                document.getElementById("facultyErrorMessage").style.display =
                    "none";
            }
        });

    // File upload interaction
    const uploadArea = document.getElementById("uploadArea");
    const uploadBtn = document.getElementById("uploadBtn");

    if (uploadArea && uploadBtn) {
        uploadArea.addEventListener("click", function (e) {
            if (e.target !== uploadBtn) {
                // Trigger file selection
                alert("File upload dialog would open here");
            }
        });

        uploadBtn.addEventListener("click", function () {
            alert("File would be uploaded here");
        });
    }
});
