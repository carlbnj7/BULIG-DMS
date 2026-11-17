# BULIG-DMS

BULIG Data and Monitoring System — Division of Bukidnon.

## Install

BULIG Data and Monitoring System for Bukidnon's Unified Literacy and Intervention Gateway.
Runs on PHP 8.0+ and MySQL 5.7+ / MariaDB 10.3+ (Hostinger, XAMPP or Laragon).

## 1. Upload the files
Upload everything inside `upload-to-public_html` into your site folder (e.g. `public_html/dms`).

## 2. Make the database
1. Hostinger hPanel → Databases → MySQL Databases: make a database and a user (XAMPP/Laragon: user `root`, empty password).
2. phpMyAdmin → choose the database → Import `database/01_schema.sql`.

## 3. Connect the app
Copy `config/config.sample.php` to `config/config.php` and type the database name, user and password. Keep `'debug' => false`. Never share `config.php`.

## 4. First sign-in
- Account ID: `DIV0001` (or username `admin`) · Password: `ChangeMe123!`
- It then asks for your own username and a new password.

## 5. Set up (Division admin)
1. **Settings** — division name, region, current school year.
2. **Municipalities** — add each municipality and its districts.
3. **Schools** — School ID (same as on the SF1), name, district, school head and position (printed on reports).
4. **Accounts** — make accounts for municipal coordinators, district supervisors, school heads / reading coordinators and teachers.
   Each gets an ID (MUN…, DIS…, SCH…, TCH…) and a starter password shown once.
   Municipal, district and school accounts can also make accounts for those under them.

## 6. Teachers
1. **Upload SF1** — the School Form 1 from LIS (.xls or .xlsx). Check the list, choose each learner's BULIG level (Level 1 to 7) and key stage, then save.
   - Same LRN already in the system → you can move the learner into your class (one record is kept) or skip.
   - Same name and birthdate under another record → merge, add as new, or skip.
2. **Class page** — AP 1 to AP 15 tabs. Set the term, and for each learner press **Passed** (moves up one level) or **Not ready** (stays). BOSY/MOSY/EOSY reading profiles follow the learner's key stage. Everything saves right away.
3. **Reports** — Classroom Reading Monitoring Report for the pre-assessment or any AP. Press **Print** (or save as PDF).

## Reports for heads and supervisors
School, district, municipal and division reports add up every class in the area, with a breakdown table (per class, school, district or municipality). Filter by school year and grade.

## Duplicates
School heads and up: **Duplicates** lists learners entered twice (same name and birthdate) and accounts made twice (same name and role). Pick the one to keep; the other's classes and results move into it.
