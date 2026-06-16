# 🏥 Medical Diagnosis AI System — Backend (API)

This repository contains the **backend API** of the Medical Diagnosis AI System, built with the [Laravel](https://laravel.com/) framework. It handles all business logic, database operations, and AI integration endpoints consumed by the frontend client.

---

## 🛠 Prerequisites

Ensure the following are installed on your machine before getting started:

| Tool | Version |
|------|---------|
| PHP | 8.2 or higher |
| Composer | Latest stable |
| MySQL | 8.0 or higher |

---

## 🚀 Quick Start

Follow these steps to set up your local development environment:

### 1. Enter the Service Directory

If you are using the monorepo, run:

```bash
cd api-backend
```

If this service is standalone, clone it and then enter the folder:

```bash
git clone <https://github.com/Medical-Diagnosis-AI-System-1/api-backend.git>
cd api-backend
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment Variables

```bash
cp .env.example .env
```

> Open `.env` and update the database credentials to match your local setup:
> ```env
> DB_HOST=127.0.0.1
> DB_DATABASE=your_database_name
> DB_USERNAME=your_username
> DB_PASSWORD=your_password
> ```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Database Migrations

```bash
php artisan migrate
```

### 6. Start the Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`.

> If the service uses a different host or port, update the `.env` file and the `--host` / `--port` flags as needed.

---

## 📂 Project Structure

```
api-backend/
├── app/
│   ├── Http/
│   │   └── Controllers/    # API request handlers and business logic
│   └── Models/             # Eloquent database models
├── routes/
│   └── api.php             # API endpoint definitions
├── database/
│   └── migrations/         # Database schema migrations
└── .env.example            # Environment variable template
```

---

## 🤝 Contributing

All contributors must follow the branching strategy below. Direct commits to `main` or `develop` are **not permitted**.

### Branching Strategy

1. **Create a feature branch** from `develop`:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Commit your changes** with clear, descriptive messages:
   ```bash
   git commit -m "feat: add patient diagnosis endpoint"
   ```

3. **Push your branch** and open a **Pull Request** targeting `develop`:
   ```bash
   git push origin feature/your-feature-name
   ```

4. All Pull Requests must be **reviewed and approved** by the Team Leader before merging.

### Commit Message Convention

Use the following prefixes to keep the commit history clean:

| Prefix | Purpose |
|--------|---------|
| `feat:` | New feature |
| `fix:` | Bug fix |
| `refactor:` | Code refactoring |
| `docs:` | Documentation update |
| `chore:` | Maintenance tasks |

---

## 📄 License

This project is proprietary and intended for internal use only. Unauthorized distribution is prohibited.
