// =====================================
// STUDENTHUB JAVASCRIPT
// =====================================

document.addEventListener("DOMContentLoaded", function () {

    // 1. HAMBURGER MENU
    const menuBtn = document.getElementById("menuButton");
    const menu = document.getElementById("menuLinks");

    if (menuBtn) {
        menuBtn.onclick = () => menu.classList.toggle("show");
    }


    // 2. DARK / LIGHT MODE
    const theme = document.createElement("button");

    theme.innerHTML = "🌙 Dark Mode";
    theme.className = "btn btn-secondary";
    theme.style = "position:fixed;bottom:20px;right:20px;z-index:1000";

    document.body.appendChild(theme);

    theme.onclick = () => {
        document.body.classList.toggle("dark-mode");

        theme.innerHTML =
            document.body.classList.contains("dark-mode")
            ? "☀️ Light Mode"
            : "🌙 Dark Mode";
    };


    // 3. COLLAPSIBLE FAQ
    document.querySelectorAll(".faq-question").forEach(q => {

        q.onclick = () =>
            q.nextElementSibling.classList.toggle("show");

    });


    // 4. MODAL POPUP
    document.querySelectorAll(".modal-button").forEach(btn => {

        btn.onclick = () => {
            const modal =
                document.getElementById(btn.dataset.target);

            if (modal) modal.classList.add("show");
        };

    });

    document.querySelectorAll(".close-modal").forEach(btn => {

        btn.onclick = () =>
            btn.closest(".modal").classList.remove("show");

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

        slides.forEach(s => s.style.display = "none");

        if (slides.length)
            slides[current].style.display = "block";
    }

    const next = document.getElementById("nextSlide");
    const prev = document.getElementById("previousSlide");

    if (next) {
        next.onclick = () => {
            current = (current + 1) % slides.length;
            showSlide();
        };
    }

    if (prev) {
        prev.onclick = () => {
            current = (current - 1 + slides.length) % slides.length;
            showSlide();
        };
    }

    showSlide();

});