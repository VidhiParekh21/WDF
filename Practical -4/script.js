document.addEventListener("DOMContentLoaded", function () {
    const menuBtn = document.getElementById("menuButton");
    const menu = document.getElementById("menuLinks");

    if (menuBtn && menu) {

        menuBtn.onclick = () => {
            menu.classList.toggle("show");
        };

    }
    if (localStorage.getItem("theme") === "dark") {

        document.body.classList.add("dark-mode");

    }

    const themeButton = document.getElementById("themeButton");

    if (themeButton) {

        updateThemeButton();

        themeButton.onclick = () => {

            document.body.classList.toggle("dark-mode");

            if (document.body.classList.contains("dark-mode")) {

                localStorage.setItem("theme", "dark");

            } else {

                localStorage.setItem("theme", "light");

            }

            updateThemeButton();

        };

    }


    function updateThemeButton() {

        if (!themeButton) return;

        if (document.body.classList.contains("dark-mode")) {

            themeButton.innerHTML = "☀️ Light Mode";

        } else {

            themeButton.innerHTML = "🌙 Dark Mode";

        }

    }

    document.querySelectorAll(".faq-question").forEach(q => {

        q.onclick = () => {

            q.nextElementSibling.classList.toggle("show");

        };

    });
    document.querySelectorAll(".modal-button").forEach(btn => {

        btn.onclick = () => {

            const modal =
                document.getElementById(btn.dataset.target);

            if (modal) {

                modal.classList.add("show");

            }

        };

    });


    document.querySelectorAll(".close-modal").forEach(btn => {

        btn.onclick = () => {

            const modal = btn.closest(".modal");

            if (modal) {

                modal.classList.remove("show");

            }

        };

    });


    // 5. NOTIFICATION BANNER

    const notice = document.querySelector(".notification");

    if (notice) {

        notice.innerHTML =
            "📢 New Announcement! Check the latest updates.";

        notice.classList.add("show");

    }


    // 6. IMAGE / CONTENT SLIDER

    const slides = document.querySelectorAll(".slide");

    let current = 0;

    function showSlide() {

        slides.forEach(s => {

            s.style.display = "none";

        });

        if (slides.length > 0) {

            slides[current].style.display = "block";

        }

    }


    const next = document.getElementById("nextSlide");
    const prev = document.getElementById("previousSlide");


    if (next && slides.length > 0) {

        next.onclick = () => {

            current =
                (current + 1) % slides.length;

            showSlide();

        };

    }


    if (prev && slides.length > 0) {

        prev.onclick = () => {

            current =
                (current - 1 + slides.length) % slides.length;

            showSlide();

        };

    }


    showSlide();

});