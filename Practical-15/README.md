StudentHub - Student Management Portal
Practical 15: Final Integration, Testing, Documentation, GitHub Submission, and Local Deployment
1. Project Description
StudentHub is a web-based student portal developed using HTML, CSS, JavaScript, Bootstrap, PHP, and MySQL. It provides a single interface for student-related information and academic activities.
2. Objectives
- Integrate all developed StudentHub modules.
- Verify navigation and responsive design.
- Test registration, login, logout, and session handling.
- Test database operations and input validation.
- Verify AJAX-based announcement loading.
- Document the setup and testing process.
- Deploy the project locally using XAMPP and Apache.
- Submit the source code through GitHub.
3. Technologies Used
- Front end: HTML5, CSS3, JavaScript, Bootstrap
- Back end: PHP
- Database: MySQL
- Local server: Apache through XAMPP
- Version control: Git and GitHub
- Editor: Visual Studio Code
4. Main Modules
The final project may include the following modules, depending on the features implemented:
- Home page and navigation
- Student registration and validation
- Login, session handling, dashboard, and logout
- Student profile
- Attendance and timetable
- Questions and answers
- Announcements with AJAX, search, filtering, sorting, and pagination
- Academic calendar
- Assignments and notes
- Contact page
- Admin CRUD and event/poster management
5. Local Installation and Execution
1. Install XAMPP and open the XAMPP Control Panel.
2. Start Apache and MySQL.
3. Copy the StudentHub project folder into C:\xampp\htdocs\.
4. Open the project folder in Visual Studio Code.
5. Open phpMyAdmin at http://localhost/phpmyadmin/.
6. Create or import the project's database using the SQL file supplied with the project.
7. Update the database connection settings in the existing configuration file. Do not commit database passwords to a public repository.
8. Open the project through Apache using http://localhost/PROJECT_FOLDER/, replacing PROJECT_FOLDER with the actual folder name.
9. Test registration, login, navigation, database operations, AJAX, and logout.
6. Testing Plan
Run each test and record the actual result. Mark a test as PASS only after it has been performed.
No.	Test case	Expected result	Actual result	Status
1	Open the home page	Home page loads without errors	To be tested	Pending
2	Navigate between modules	Correct page opens	To be tested	Pending
3	Submit invalid registration data	Validation errors are shown	To be tested	Pending
4	Register with a new valid email	Account is created	To be tested	Pending
5	Register with a duplicate email	Duplicate account is rejected	To be tested	Pending
6	Login with valid credentials	User reaches the correct dashboard	To be tested	Pending
7	Login with invalid credentials	Access is rejected with a suitable message	To be tested	Pending
8	Open a protected page without a session	Access is denied or redirected to login	To be tested	Pending
9	Logout and revisit a protected page	Session-protected access is denied	To be tested	Pending
10	Run database create, search, update, and delete operations	Implemented CRUD operations work correctly	To be tested	Pending
11	Load announcements using AJAX	Announcement data is displayed	To be tested	Pending
12	Search and filter announcements	Matching announcements are displayed	To be tested	Pending
13	Upload an invalid event poster	Invalid file is rejected	To be tested	Pending
14	Resize to mobile/tablet widths	Layout remains usable	To be tested	Pending
15	Check browser console and PHP logs	No unexplained errors remain	To be tested	Pending


7. Security Checklist
- Store passwords using PHP password_hash() and verify them using password_verify().
- Use prepared statements for SQL queries.
- Validate user input on the server; browser-side validation alone is insufficient.
- Check authentication and authorization on protected pages.
- Regenerate the session ID after successful login.
- Destroy the session during logout.
- Escape user-controlled output when rendering HTML.
- Restrict poster uploads by permitted file type, size, and content; generate safe filenames.
- Keep database credentials and private configuration out of public GitHub repositories.
- Do not upload real student personal data to a public repository.
8. GitHub Submission
1. Create a repository on GitHub.
2. Open a terminal in the StudentHub project directory.
3. Initialize Git if the folder is not already a repository: git init
4. Review files before staging. Exclude secrets, real user data, unnecessary temporary files, and generated dependencies.
5. Stage the appropriate files: git add .
6. Commit: git commit -m "Complete StudentHub Practical 15"
7. Connect the repository using the URL copied from your own GitHub repository: git remote add origin YOUR_REPOSITORY_URL
8. Rename the branch if needed: git branch -M main
9. Push: git push -u origin main
If Git is already initialized or a remote already exists, inspect the existing configuration before repeating these commands.
9. Local Deployment
For basic local deployment, place the project inside C:\xampp\htdocs\, start Apache and MySQL, configure the database, and access the project through http://localhost/PROJECT_FOLDER/.

An Apache virtual host may be configured if required by the instructor. Confirm the existing Apache configuration and test it before restarting Apache. A virtual host is optional unless specifically required for assessment.
10. Conclusion
The StudentHub project is intended to integrate the developed modules into one portal. Final completion should be confirmed by executing the test cases, resolving errors, documenting actual results, deploying locally, and submitting the verified source code to GitHub.