
<?php
require_once "db.php";

$errors = [];
$success = "";

$name = "";
$email = "";
$mobile = "";
$course = "";
$year = "";
$gender = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $mobile = trim($_POST["mobile"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $year = trim($_POST["year"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $passwordInput = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirmPassword"] ?? "";

    if ($name === "" || $email === "" || $mobile === "" ||
        $course === "" || $year === "" || $gender === "" ||
        $passwordInput === "" || $confirmPassword === "") {
        $errors[] = "Please fill in all required fields.";
    }

    if ($name !== "" && !preg_match("/^[a-zA-Z ]{2,100}$/", $name)) {
        $errors[] = "Enter a valid name using letters and spaces.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors[] = "Please enter a valid email address.";
    }

    if (!preg_match("/^[6-9][0-9]{9}$/", $mobile)) {
        $errors[] = "Enter a valid 10-digit Indian mobile number.";
    }

    $validCourses = [
        "Computer Engineering",
        "Information Technology",
        "Mechanical Engineering",
        "Civil Engineering",
        "Electronics Engineering"
    ];

    if (!in_array($course, $validCourses, true)) {
        $errors[] = "Please select a valid course.";
    }

    $validYears = [
        "First Year",
        "Second Year",
        "Third Year",
        "Fourth Year"
    ];

    if (!in_array($year, $validYears, true)) {
        $errors[] = "Please select a valid academic year.";
    }

    if (!in_array($gender, ["Male", "Female", "Other"], true)) {
        $errors[] = "Please select a valid gender.";
    }

    if (strlen($passwordInput) < 8) {
        $errors[] = "Password must contain at least 8 characters.";
    }

    if ($passwordInput !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (!isset($_POST["terms"])) {
        $errors[] = "You must accept the Terms and Conditions.";
    }

    if (empty($errors)) {
        try {
            $check = $conn->prepare(
                "SELECT student_id FROM students WHERE email = ?"
            );
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $errors[] = "This email is already registered.";
            } else {
                $passwordHash = password_hash(
                    $passwordInput,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare(
                    "INSERT INTO students
                    (full_name, email, mobile, course, academic_year, gender, password)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssssss",
                    $name,
                    $email,
                    $mobile,
                    $course,
                    $year,
                    $gender,
                    $passwordHash
                );

                if ($stmt->execute()) {
                    $success = "Registration successful! Your details have been saved.";

                    $name = "";
                    $email = "";
                    $mobile = "";
                    $course = "";
                    $year = "";
                    $gender = "";
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }

                $stmt->close();
            }

            $check->close();
        } catch (mysqli_sql_exception $e) {
            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }

            if (isset($check) && $check instanceof mysqli_stmt) {
                $check->close();
            }

            $errors[] = "Unable to complete registration. Check your database table and try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="registration-page">

<div class="container py-5">
    <div class="card shadow-lg border-0 mx-auto registration-card">
        <div class="card-body p-4 p-md-5">

            <div class="text-center">
                <img src="studenthub_logo.png" alt="StudentHub Logo"
                     width="100" height="100" class="mb-3">

                <h1 class="fw-bold studenthub-title">STUDENTHUB</h1>
                <h2 class="mt-3">Student Registration</h2>
                <p class="text-muted">Create your StudentHub account</p>
                <hr>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Please correct the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success !== ""): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form id="registrationForm" action="register.php" method="POST">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label fw-bold">
                            Full Name <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control" id="name"
                               name="name" placeholder="Enter your full name"
                               value="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>"
                               pattern="[A-Za-z ]{2,100}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label fw-bold">
                            Email Address <span class="required">*</span>
                        </label>
                        <input type="email" class="form-control" id="email"
                               name="email" placeholder="Enter your email"
                               maxlength="100"
                               value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>"
                               required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="mobile" class="form-label fw-bold">
                            Mobile Number <span class="required">*</span>
                        </label>
                        <input type="tel" class="form-control" id="mobile"
                               name="mobile" placeholder="Enter 10-digit mobile number"
                               maxlength="10" pattern="[6-9][0-9]{9}"
                               value="<?= htmlspecialchars($mobile, ENT_QUOTES, "UTF-8") ?>"
                               required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="course" class="form-label fw-bold">
                            Course <span class="required">*</span>
                        </label>
                        <select class="form-select" id="course" name="course" required>
                            <option value="">Select Course</option>
                            <?php foreach ([
                                "Computer Engineering",
                                "Information Technology",
                                "Mechanical Engineering",
                                "Civil Engineering",
                                "Electronics Engineering"
                            ] as $option): ?>
                                <option value="<?= htmlspecialchars($option, ENT_QUOTES, "UTF-8") ?>"
                                    <?= $course === $option ? "selected" : "" ?>>
                                    <?= htmlspecialchars($option, ENT_QUOTES, "UTF-8") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label fw-bold">
                            Password <span class="required">*</span>
                        </label>
                        <input type="password" class="form-control" id="password"
                               name="password" placeholder="Enter password"
                               minlength="8" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="confirmPassword" class="form-label fw-bold">
                            Confirm Password <span class="required">*</span>
                        </label>
                        <input type="password" class="form-control"
                               id="confirmPassword" name="confirmPassword"
                               placeholder="Confirm password" minlength="8" required>
                        <div id="passwordError" class="text-danger small"></div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="year" class="form-label fw-bold">
                            Academic Year <span class="required">*</span>
                        </label>
                        <select class="form-select" id="year" name="year" required>
                            <option value="">Select Year</option>
                            <?php foreach ([
                                "First Year",
                                "Second Year",
                                "Third Year",
                                "Fourth Year"
                            ] as $option): ?>
                                <option value="<?= htmlspecialchars($option, ENT_QUOTES, "UTF-8") ?>"
                                    <?= $year === $option ? "selected" : "" ?>>
                                    <?= htmlspecialchars($option, ENT_QUOTES, "UTF-8") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            Gender <span class="required">*</span>
                        </label>
                        <div class="mt-2">
                            <?php foreach (["Male", "Female", "Other"] as $option): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio"
                                           name="gender" id="gender<?= $option ?>"
                                           value="<?= $option ?>"
                                           <?= $gender === $option ? "checked" : "" ?>
                                           required>
                                    <label class="form-check-label"
                                           for="gender<?= $option ?>">
                                        <?= $option ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-12 mb-3 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   id="terms" name="terms" value="accepted" required>
                            <label class="form-check-label" for="terms">
                                I agree to the
                                <a href="#">Terms &amp; Conditions</a>
                                <span class="required">*</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary px-4 me-2">
                        Create Account
                    </button>
                    <button type="reset" class="btn btn-secondary px-4">
                        Clear
                    </button>
                </div>
            </form>

            <hr class="my-4">

            <div class="text-center">
                <p class="mb-0">
                    Already have an account?
                    <a href="index.html">Login here</a>
                </p>
            </div>

            <div class="text-center text-muted mt-4">
                <p class="mb-0">
                    <strong>StudentHub © 2026</strong><br>
                    Manage your academic information in one place.
                </p>
            </div>

        </div>
    </div>
</div>

<script>
const form = document.getElementById("registrationForm");
const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirmPassword");
const passwordError = document.getElementById("passwordError");

function checkPasswords() {
    if (confirmPassword.value !== "" &&
        password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity("Passwords do not match.");
        passwordError.textContent = "Passwords do not match.";
    } else {
        confirmPassword.setCustomValidity("");
        passwordError.textContent = "";
    }
}

password.addEventListener("input", checkPasswords);
confirmPassword.addEventListener("input", checkPasswords);

form.addEventListener("submit", function(event) {
    checkPasswords();

    if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
    }
});
</script>

</body>
</html>
