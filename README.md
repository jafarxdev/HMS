# Hospital Management System

A small, exam-focused hospital application using Laravel 12, Livewire 4, Blade, Tailwind CSS 4 and SQLite. The Composer project name is `hospital/hospital-management-system`; the existing workspace remains `hms`.

## Requirements

- PHP 8.2+ with PDO SQLite, SQLite3, mbstring, OpenSSL, XML, ctype, fileinfo and tokenizer.
- Composer 2.
- Node.js 22.12+ (tested with 22.22.3) and npm.
- Git.
- Laravel Herd for Windows for the local site.

Installed versions are pinned in `composer.lock` and `package-lock.json`. This project was verified with Laravel 12.69.2, Livewire 4.4.6, Tailwind 4.3.3 and Pest 3.8.7.

## Windows PowerShell installation

Open PowerShell in the project directory. For this workspace:

```powershell
Set-Location C:\Users\PC\Herd\hms

php --version
composer --version
node --version
npm.cmd --version

composer install --no-interaction

if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
    php artisan key:generate --no-interaction
}

if (-not (Test-Path database/database.sqlite)) {
    New-Item -ItemType File database/database.sqlite
}

npm.cmd ci
php artisan migrate --seed --no-interaction
npm.cmd run build
```

Use `npm.cmd` on Windows if PowerShell blocks `npm.ps1`. If PHP or Composer is not found, open a fresh terminal after installing Herd and ensure Herd's bin directory is in PATH.

Keep an existing `.env` and its application key. Generate a key only for a new installation with an empty `APP_KEY`.

## Environment and SQLite

Set these values in your local `.env`:

```dotenv
APP_NAME="Hospital Management System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://hms.test
APP_TIMEZONE=Asia/Kabul

DB_CONNECTION=sqlite
DB_FOREIGN_KEYS=true
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Leave `DB_DATABASE` unset to use `database/database.sqlite`. If you set it, use the absolute path to that file. The database and populated `.env` are ignored by Git.

All appointment times and “today” calculations use the application timezone, which defaults to Asia/Kabul. Change `APP_TIMEZONE` to the hospital's timezone if necessary.

## Run the project

Herd already serves this directory. Open **http://hms.test/login**. A differently named Herd directory has its corresponding `.test` hostname; update `APP_URL` accordingly.

For frontend development, keep this command running in another terminal:

```powershell
npm.cmd run dev
```

For compiled assets without a development process:

```powershell
npm.cmd run build
```

No separate API server or queue worker is needed for these modules. The site itself is served by Herd; do not start a second application server.

## Development login accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@hospital.test | password |
| Receptionist | receptionist@hospital.test | password |
| Doctor | doctor@hospital.test | password |

These are intentionally public development credentials, never production secrets. There is no public registration or role-management package. User roles are provisioned through the seeder; an admin can link an existing doctor-role account to a doctor from the Doctors form.

The seed creates 3 departments, 3 doctors, 18 patients, 18 appointments, 18 medical records, 18 prescriptions and 36 prescription items. Nine appointments are dated today. The doctor login is linked to the first doctor.

Running the seeder again preserves existing accounts and skips the demo dataset when the seeded General Medicine department already exists.

## Modules and permissions

| Module | Admin | Receptionist | Doctor |
| --- | --- | --- | --- |
| Dashboard | Full overview | Full overview | Own appointment list and counts; overall patient/doctor totals |
| Departments | CRUD | No access | No access |
| Doctors | CRUD; link doctor login | No access | No access |
| Patients | CRUD | CRUD | Select assigned patients when recording visits |
| Appointments | CRUD and status changes | CRUD and status changes | View own recent appointments on dashboard |
| Medical records | CRUD | No access | CRUD on own records |
| Prescriptions and items | CRUD | No access | CRUD on own prescriptions |

A patient is available to a doctor if the patient has an appointment or medical record with that doctor. A doctor cannot submit another doctor's ID or another doctor's medical record to gain access.

All lists provide search, pagination, empty states, feedback messages and delete confirmation. Filters include:

- Doctors: department and status.
- Patients: gender.
- Appointments: date, doctor and status; search patient, doctor or reason.
- Medical records: patient and doctor; search patient or diagnosis.
- Prescriptions: date; search patient or medicine.
- Departments: name search.

Search and filters are stored in the URL. Changing or clearing filters resets pagination.

## Main architecture decisions

```text
Routes
  -> Authentication and Gate middleware
  -> Full-page Livewire class
  -> Action validation and authorization
  -> Eloquent models
  -> SQLite
  -> Blade views and Tailwind
```

- Components live in `app/Livewire`, with their views in `resources/views/livewire`. The class-based format keeps actions and validation easy to explain. Livewire 4 pages use `Route::livewire()`.
- Login uses Laravel's session authentication, session regeneration and a five-attempt, one-minute rate limit keyed by email and IP. Logout invalidates the session and regenerates the CSRF token.
- `users.role` holds `admin`, `receptionist` or `doctor`. Gates protect modules; policies protect clinical record ownership. Actions authorize again on every request.
- `doctors.user_id` is an optional unique link to a login account. It avoids relying on matching email addresses for authorization.
- Explicit `$fillable` fields and validated action data control assignment. The user role is not mass assignable through ordinary user input.
- Statuses use small model constant lists and validation rules; SQLite constraints also enforce the allowed values.
- Relationship methods have concrete return types. Date fields use Eloquent date casts. Lists and dashboard eagerly load the displayed relationships.
- Forms use small Blade components for repeated fields, buttons, alerts and badges. There is no repository, service layer, DTO or separate frontend framework.

## Database integrity and deletion

Every hospital table has an integer primary key and timestamps. Required parent relationships use foreign keys, with nullable values only where explicitly optional.

| Table | Main constraints and deletion rules |
| --- | --- |
| users | Unique email; constrained role with receptionist default |
| departments | Unique name; nullable description |
| doctors | Restricted department FK; optional unique user FK; unique email; active/inactive status |
| patients | Required name, gender, birth date and phone; optional email/address/blood group/emergency contact |
| appointments | Restricted patient/doctor FKs; scheduled/completed/cancelled status; indexed date; unique active doctor/date/time slot |
| medical_records | Restricted patient/doctor FKs; indexed visit date; optional symptoms/treatment/notes |
| prescriptions | Composite FK requires medical record, doctor and patient to match; indexed prescription date; optional notes |
| prescription_items | Required medicine, dosage, frequency and duration; optional instructions; cascades only when its prescription is deleted |

SQLite foreign keys are enabled. Parent lookup keys are indexed. Department, doctor, patient and medical-record deletion is blocked when dependent data exists, with a useful UI message.

A partial unique SQLite index prevents two non-cancelled appointments for the same doctor, date and minute. Validation supplies a friendly error, and the database prevents concurrent duplicate inserts. Cancelling a booking releases the slot. Reactivating it checks availability again. A scheduled appointment must be in the future and new bookings require an active doctor. Existing appointments can still be closed after their doctor becomes inactive.

Prescriptions derive their doctor and patient from the selected medical record. Creating or replacing their items happens in one `DB::transaction()`. A failure restores the entire previous state. A prescription requires 1–20 items; its date cannot precede the visit or be in the future. A medical record with prescriptions cannot change its doctor or patient.

## Tests and formatting

Tests use an isolated, in-memory SQLite database, factories and Pest. They do not erase the development database.

```powershell
php artisan test --compact
php artisan test --compact tests/Feature/AppointmentsTest.php
php vendor/bin/pint --dirty --format agent
npm.cmd run build
```

Coverage includes login/logout and throttling, guest protection, role permissions, doctor ownership, CRUD, validation, filters, pagination, duplicate bookings, parent deletion restrictions, matching clinical relationships, and transaction rollback during prescription creation and editing. The rollback tests deliberately fail a database insert to verify real atomicity.

## Reset the development demo and perform final QA

The following first command deletes and rebuilds the development database. Use it only when its data is disposable.

```powershell
php artisan migrate:fresh --seed --no-interaction
php artisan test
npm.cmd run build
php vendor/bin/pint --dirty --format agent
git status --short
```

For an ordinary update that must preserve data, use `php artisan migrate --no-interaction` instead.

## Practical exam walkthrough

1. Sign in as admin and create a department and an active doctor.
2. Sign in as receptionist, create a patient and book a future appointment.
3. Attempt the same doctor's time again and observe the validation message.
4. Cancel the first appointment and book the released time.
5. Use the seeded doctor account to record a visit for one of its assigned patients.
6. Create a prescription from that record, add multiple medicines and save.
7. Try accessing another role's module and observe the forbidden response.
8. Run the relevant feature test and review the component, relationships and migration together.

The Git history contains separate commits for the requested stages, followed by focused fixes and QA.
