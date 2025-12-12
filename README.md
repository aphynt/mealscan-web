📸 Face Recognition Attendance System

A web-based attendance system that uses real-time face recognition to identify users and record their check-in/check-out activities automatically.
Built with Laravel, integrated with webcam access, and supports rating, feedback, and meal quantity tracking.

🚀 Features

🔍 Real-time Face Recognition using webcam (front camera supported)

👤 Automatic identity detection based on stored face data

🍱 Meal quantity input for attendance with meal tracking

⭐ User rating system (1–5 stars) for meal quality

📝 Optional remarks/feedback field

📷 Image capture directly from camera

🔔 Interactive modal notifications (success/error/warning/info)

🔐 CSRF-protected API

📊 Attendance summary & logs

⚙️ Built using Laravel, JavaScript, and TailwindCSS

🧰 Tech Stack

Laravel 10+

JavaScript (Fetch API)

Face Recognition API / ML model (custom or external)

TailwindCSS

MySQL / MariaDB

📦 Installation
git clone https://github.com/aphynt/mealscan-web.git
cd mealscan-web
composer install
cp .env.example .env
php artisan key:generate


Set database credentials in .env, then:

php artisan migrate:fresh --seed
php artisan serve

🎯 Usage

Open the application in the browser.

Allow camera access when prompted.

System will detect your face automatically.

After detection:

Input meal quantity

Give a rating (optional)

Add remarks (optional)

Click Kirim

System will process recognition → save attendance → show modal result.

📁 Project Structure (Simplified)
app/
resources/
    views/
    js/
public/
routes/
database/


Includes:

Face recognition controller

Image capture handler

Attendance processing logic

🔒 Security

CSRF protection enabled

Image uploads restricted to base64 camera capture

No file upload from user device

Validation for spoofing prevention

📄 License

This project is closed-source under the Author.

👨‍💻 Author

Developed by Aphynt – Face Recognition Attendance System
If you need additional modules (API, dashboard, mobile version), feel free to request!
