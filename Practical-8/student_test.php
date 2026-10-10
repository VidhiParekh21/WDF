
<?php
require_once "db.php";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $course = trim($_POST["course"] ?? "");

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/^[6-9][0-9]{9}$/", $mobile) || $course === "") {
        $message = "Please enter valid details.";
    } else {
        try {
            $sql = "INSERT INTO students (full_name, email, mobile, course) VALUES (:name, :email, :mobile, :course)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":name" => $name,
                ":email" => $email,
                ":mobile" => $mobile,
                ":course" => $course
            ]);
            $message = "Student added successfully!";
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = "Unable to add student. The email may already exist.";
        }
    }
}

$stmt = $pdo->query("SELECT student_id, full_name, email, mobile, course FROM students ORDER BY student_id DESC");
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Student Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
    <h2>StudentHub - Add Student</h2>
    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></p>
    <?php endif; ?>
    <form method="POST" action="student_test.php" class="mb-4">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Mobile Number</label>
            <input type="text" name="mobile" class="form-control" pattern="[6-9][0-9]{9}" maxlength="10" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Course</label>
            <input type="text" name="course" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Student</button>
    </form>
    <h3>Saved Students</h3>
    <table class="table table-bordered">
        <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Mobile</th><th>Course</th></tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$student["student_id"]); ?></td>
                    <td><?php echo htmlspecialchars($student["full_name"]); ?></td>
                    <td><?php echo htmlspecialchars($student["email"]); ?></td>
                    <td><?php echo htmlspecialchars($student["mobile"]); ?></td>
                    <td><?php echo htmlspecialchars($student["course"]); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
