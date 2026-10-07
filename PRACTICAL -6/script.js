document.addEventListener("DOMContentLoaded", function () {


    /* =====================================
       DARK MODE
       ===================================== */

    const themeButton =
        document.getElementById("themeButton");


    function applyTheme() {

        const savedTheme =
            localStorage.getItem("studentHubTheme");


        if (savedTheme === "dark") {

            document.body.classList.add("dark-mode");

        }
        else {

            document.body.classList.remove("dark-mode");

        }


        updateThemeButton();

    }


    function updateThemeButton() {

        if (!themeButton) {
            return;
        }


        if (document.body.classList.contains("dark-mode")) {

            themeButton.textContent = "Light Mode";

        }
        else {

            themeButton.textContent = "Dark Mode";

        }

    }


    if (themeButton) {

        themeButton.addEventListener("click", function () {


            document.body.classList.toggle("dark-mode");


            if (
                document.body.classList.contains("dark-mode")
            ) {

                localStorage.setItem(
                    "studentHubTheme",
                    "dark"
                );

            }
            else {

                localStorage.setItem(
                    "studentHubTheme",
                    "light"
                );

            }


            updateThemeButton();

        });

    }


    applyTheme();



    /* =====================================
       HAMBURGER MENU
       ===================================== */

    const menuButton =
        document.getElementById("menuButton");


    const menuLinks =
        document.getElementById("menuLinks");


    if (menuButton && menuLinks) {

        menuButton.addEventListener("click", function () {

            menuLinks.classList.toggle("show");

        });

    }



    /* =====================================
       FAQ
       ===================================== */

    const faqQuestions =
        document.querySelectorAll(".faq-question");


    faqQuestions.forEach(function (question) {


        question.addEventListener("click", function () {


            const answer =
                question.nextElementSibling;


            if (answer) {

                answer.classList.toggle("show");

            }

        });

    });



    /* =====================================
       MODAL
       ===================================== */

    const modal =
        document.querySelector(".modal");


    const openModalButton =
        document.getElementById("openModal");


    const closeModalButton =
        document.getElementById("closeModal");


    if (openModalButton && modal) {

        openModalButton.addEventListener(
            "click",
            function () {

                modal.classList.add("show");

            }
        );

    }


    if (closeModalButton && modal) {

        closeModalButton.addEventListener(
            "click",
            function () {

                modal.classList.remove("show");

            }
        );

    }



    /* =====================================
       CONTENT SLIDER
       ===================================== */

    const slides =
        document.querySelectorAll(".slider-content");


    const nextButton =
        document.getElementById("nextSlide");


    const previousButton =
        document.getElementById("previousSlide");


    let currentSlide = 0;


    function showSlide(index) {


        if (slides.length === 0) {
            return;
        }


        slides.forEach(function (slide) {

            slide.classList.remove("active");

        });


        slides[index].classList.add("active");

    }


    if (slides.length > 0) {

        showSlide(currentSlide);


        if (nextButton) {

            nextButton.addEventListener(
                "click",
                function () {

                    currentSlide++;

                    if (currentSlide >= slides.length) {

                        currentSlide = 0;

                    }

                    showSlide(currentSlide);

                }
            );

        }


        if (previousButton) {

            previousButton.addEventListener(
                "click",
                function () {

                    currentSlide--;

                    if (currentSlide < 0) {

                        currentSlide =
                            slides.length - 1;

                    }

                    showSlide(currentSlide);

                }
            );

        }

    }



    /* =====================================
       NOTIFICATION
       ===================================== */

    const notification =
        document.querySelector(".notification");


    const notificationButton =
        document.getElementById("showNotification");


    if (notificationButton && notification) {

        notificationButton.addEventListener(
            "click",
            function () {

                notification.classList.add("show");

            }
        );

    }



    /* =====================================
       REGISTRATION FORM
       ===================================== */

    const registrationForm =
        document.getElementById("registrationForm");


    if (registrationForm) {


        registrationForm.addEventListener(
            "submit",
            function (event) {


                event.preventDefault();


                /* ---------------------------------
                   Get Values
                   --------------------------------- */

                const name =
                    document.getElementById("name")
                    .value
                    .trim();


                const email =
                    document.getElementById("email")
                    .value
                    .trim();


                const mobile =
                    document.getElementById("mobile")
                    .value
                    .trim();


                const password =
                    document.getElementById("password")
                    .value;


                const confirmPassword =
                    document.getElementById("confirmPassword")
                    .value;


                const course =
                    document.getElementById("course")
                    .value;


                const year =
                    document.getElementById("year")
                    .value;


                const terms =
                    document.getElementById("terms")
                    .checked;


                const selectedGender =
                    document.querySelector(
                        'input[name="gender"]:checked'
                    );


                const gender =
                    selectedGender
                        ? selectedGender.value
                        : "";


                /* ---------------------------------
                   Clear Old Errors
                   --------------------------------- */

                document
                    .querySelectorAll(".error-message")
                    .forEach(function (error) {

                        error.textContent = "";

                    });


                const successMessage =
                    document.getElementById(
                        "registrationSuccess"
                    );


                successMessage.textContent = "";


                let isValid = true;



                /* =================================
                   REGULAR EXPRESSIONS
                   ================================= */


                const nameRegex =
                    /^[A-Za-z ]+$/;


                const emailRegex =
                    /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


                const mobileRegex =
                    /^[0-9]{10}$/;


                const passwordRegex =
                    /^(?=.*[A-Za-z])(?=.*\d).{8,}$/;



                /* =================================
                   NAME VALIDATION
                   ================================= */

                if (name === "") {

                    document.getElementById(
                        "nameError"
                    ).textContent =
                        "Please enter your full name.";

                    isValid = false;

                }
                else if (!nameRegex.test(name)) {

                    document.getElementById(
                        "nameError"
                    ).textContent =
                        "Name should contain only letters and spaces.";

                    isValid = false;

                }



                /* =================================
                   EMAIL VALIDATION
                   ================================= */

                if (email === "") {

                    document.getElementById(
                        "emailError"
                    ).textContent =
                        "Please enter your email address.";

                    isValid = false;

                }
                else if (!emailRegex.test(email)) {

                    document.getElementById(
                        "emailError"
                    ).textContent =
                        "Please enter a valid email address.";

                    isValid = false;

                }



                /* =================================
                   MOBILE VALIDATION
                   ================================= */

                if (mobile === "") {

                    document.getElementById(
                        "mobileError"
                    ).textContent =
                        "Please enter your mobile number.";

                    isValid = false;

                }
                else if (!mobileRegex.test(mobile)) {

                    document.getElementById(
                        "mobileError"
                    ).textContent =
                        "Mobile number must contain exactly 10 digits.";

                    isValid = false;

                }



                /* =================================
                   PASSWORD VALIDATION
                   ================================= */

                if (password === "") {

                    document.getElementById(
                        "passwordError"
                    ).textContent =
                        "Please enter a password.";

                    isValid = false;

                }
                else if (!passwordRegex.test(password)) {

                    document.getElementById(
                        "passwordError"
                    ).textContent =
                        "Password must be at least 8 characters and contain a letter and a number.";

                    isValid = false;

                }



                /* =================================
                   CONFIRM PASSWORD
                   ================================= */

                if (confirmPassword === "") {

                    document.getElementById(
                        "confirmPasswordError"
                    ).textContent =
                        "Please confirm your password.";

                    isValid = false;

                }
                else if (password !== confirmPassword) {

                    document.getElementById(
                        "confirmPasswordError"
                    ).textContent =
                        "Passwords do not match.";

                    isValid = false;

                }



                /* =================================
                   COURSE
                   ================================= */

                if (course === "") {

                    document.getElementById(
                        "courseError"
                    ).textContent =
                        "Please select your course.";

                    isValid = false;

                }



                /* =================================
                   YEAR
                   ================================= */

                if (year === "") {

                    document.getElementById(
                        "yearError"
                    ).textContent =
                        "Please select your academic year.";

                    isValid = false;

                }



                /* =================================
                   GENDER
                   ================================= */

                if (gender === "") {

                    document.getElementById(
                        "genderError"
                    ).textContent =
                        "Please select your gender.";

                    isValid = false;

                }



                /* =================================
                   TERMS
                   ================================= */

                if (!terms) {

                    document.getElementById(
                        "termsError"
                    ).textContent =
                        "Please accept the Terms & Conditions.";

                    isValid = false;

                }



                /* =================================
                   SUCCESS
                   ================================= */

                if (isValid) {


                    const student = {

                        name: name,

                        email: email,

                        mobile: mobile,

                        password: password,

                        course: course,

                        year: year,

                        gender: gender

                    };


                    /*
                       Store registration information.

                       This is suitable for a frontend
                       practical/demo only.
                    */

                    localStorage.setItem(
                        "studentHubUser",
                        JSON.stringify(student)
                    );


                    /*
                       Registration successful
                    */

                    successMessage.textContent =
                        "Registration successful! Redirecting to Login...";


                    successMessage.style.color =
                        "#198754";


                    /*
                       Redirect to Login
                    */

                    setTimeout(function () {

                        window.location.href =
                            "index.html";

                    }, 1500);

                }

            }
        );



        /* =====================================
           REMOVE ERRORS WHILE USER TYPES
           ===================================== */

        const formInputs =
            registrationForm.querySelectorAll(
                "input, select"
            );


        formInputs.forEach(function (input) {


            input.addEventListener(
                "input",
                function () {

                    clearFieldError(input);

                }
            );


            input.addEventListener(
                "change",
                function () {

                    clearFieldError(input);

                }
            );

        });


        function clearFieldError(input) {


            if (input.id) {

                const error =
                    document.getElementById(
                        input.id + "Error"
                    );


                if (error) {

                    error.textContent = "";

                }

            }


            /*
               Gender has three radio buttons
               but only one error message.
            */

            if (input.name === "gender") {

                document.getElementById(
                    "genderError"
                ).textContent = "";

            }


            /*
               Terms checkbox
            */

            if (input.id === "terms") {

                document.getElementById(
                    "termsError"
                ).textContent = "";

            }

        }


        /* =====================================
           RESET REGISTRATION FORM
           ===================================== */

        registrationForm.addEventListener(
            "reset",
            function () {

                setTimeout(function () {


                    document
                        .querySelectorAll(".error-message")
                        .forEach(function (error) {

                            error.textContent = "";

                        });


                    document.getElementById(
                        "registrationSuccess"
                    ).textContent = "";


                }, 0);

            }
        );

    }



    /* =====================================
       LOGIN FORM
       ===================================== */

    const loginForm =
        document.getElementById("loginForm");


    if (loginForm) {


        loginForm.addEventListener(
            "submit",
            function (event) {


                event.preventDefault();


                const username =
                    document.getElementById("username")
                    .value
                    .trim();


                const password =
                    document.getElementById("loginPassword")
                    .value;


                const loginError =
                    document.getElementById("loginError");


                const registeredUser =
                    JSON.parse(
                        localStorage.getItem(
                            "studentHubUser"
                        )
                    );


                /*
                   No account registered
                */

                if (!registeredUser) {


                    if (loginError) {

                        loginError.textContent =
                            "No account found. Please register first.";

                    }
                    else {

                        alert(
                            "No account found. Please register first."
                        );

                    }


                    return;

                }



                /*
                   Check email and password
                */

                if (
                    username === registeredUser.email &&
                    password === registeredUser.password
                ) {


                    /*
                       Store login status
                    */

                    localStorage.setItem(
                        "studentHubLoggedIn",
                        "true"
                    );


                    /*
                       Clear old login error
                    */

                    if (loginError) {

                        loginError.textContent = "";

                    }


                    /*
                       Go to Homepage
                    */

                    window.location.href =
                        "homepage.html";

                }
                else {


                    if (loginError) {

                        loginError.textContent =
                            "Invalid email or password.";

                    }
                    else {

                        alert(
                            "Invalid email or password."
                        );

                    }

                }

            }
        );

    }



    /* =====================================
       LOGOUT
       ===================================== */

    const logoutLinks =
        document.querySelectorAll(
            'a[href="index.html"]'
        );


    logoutLinks.forEach(function (link) {


        if (
            link.textContent
                .trim()
                .toLowerCase()
                .includes("logout")
        ) {


            link.addEventListener(
                "click",
                function () {


                    localStorage.removeItem(
                        "studentHubLoggedIn"
                    );

                }
            );

        }

    });



});