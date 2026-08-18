
<div align="center">

<img src="https://img.shields.io/badge/Medical%20AI-Diagnostic%20System-blue?style=for-the-badge&logo=heart&logoColor=white" alt="Medical AI"/>

# 🏥 Medical-AI-Project

### Intelligent Medical Diagnostic System — Syrian Market Edition

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Python](https://img.shields.io/badge/Python-3.11+-3776AB?style=flat-square&logo=python&logoColor=white)](https://python.org)
[![React](https://img.shields.io/badge/React-18.x-61DAFB?style=flat-square&logo=react&logoColor=black)](https://reactjs.org)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Supabase](https://img.shields.io/badge/Supabase-pgvector-3ECF8E?style=flat-square&logo=supabase&logoColor=white)](https://supabase.com)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat-square&logo=docker&logoColor=white)](https://docker.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

> An interactive, AI-driven primary medical diagnostic system. Utilizing a **hybrid RAG architecture** that integrates structured relational data with generative AI, specifically tailored for the Syrian medical landscape.

[Features](#-features) • [Architecture](#-architecture) • [Prerequisites](#-prerequisites) • [Getting Started](#-getting-started) • [Folder Structure](#-folder-structure)

</div>

---

## 📋 Overview

**Medical-AI-Project** is an intelligent medical diagnostic system based on the **Socratic questioning methodology**. Instead of merely processing symptoms to provide an instant diagnosis, the system engages the patient with intelligent, sequential questions to extract precise information. It then leverages a **Retrieval-Augmented Generation (RAG)** pipeline — backed by a Supabase `pgvector` knowledge base — to produce accurate, reliable diagnostic suggestions.

The platform has three roles: **Patients** (consultations), **Doctors** (join the platform, manage their profile), and **Admins** (manage diseases/symptoms, review doctor join requests, insert medical knowledge).

> **Key architectural rule:** the React frontend communicates **only** with the Laravel API. Laravel is the single entry point and **proxies** all AI calls to the FastAPI engine (e.g. knowledge insertion via `/admin/ai/insert/*`). The frontend never calls FastAPI directly.

---

## ✨ Features

### 🧠 AI Engine
- **End-to-End RAG Pipeline:** Retrieves medical knowledge from clinical PDFs and protocols stored as `pgvector` embeddings in Supabase.
- **Cross-Encoder Reranking:** Enhances diagnostic precision by re-evaluating retrieved documents.
- **Socratic Questioning Engine:** Interactive, step-by-step patient interviews.
- **Semantic Search:** Understands medical intent beyond keyword matching.
- **Knowledge Insertion:** Admins upload PDF / JSON files; Laravel forwards them to FastAPI which chunks, embeds, and upserts into Supabase.

### 👨‍⚕️ Patient & Doctor Experience
- Guided, intelligent symptom questionnaire.
- Doctor **join-request** workflow (`/joining-requests` form → admin approval).
- Comprehensive history of diagnostic sessions.
- Exportable PDF diagnostic reports.
- Firebase Cloud Messaging (FCM) push notifications.

### 👩‍💼 Admin
- Dashboard for disease, symptom, and session management.
- Review and approve doctor join requests.
- AI knowledge-base insertion (PDF / JSON).
- Multi-tier Role-Based Access Control (Patient / Doctor / Admin).

### 🔒 Security
- Protection against **Indirect Prompt Injection**.
- **Audit Logging** for system interactions.
- Multi-tier RBAC and OTP-based verification for sensitive flows.

---

## 📂 Project Structure

This repository is a **Monorepo** with three services:

- `/api-backend`: Laravel 11 REST API — auth, CRUD, RBAC, orchestration, and AI proxy.
- `/ai-engine`: FastAPI service — RAG, embeddings (`pgvector`), LLM integration, PDF processing.
- `/web-frontend`: React 18 + Vite admin/doctor dashboard (run **locally**, not containerized in production).

```
Medical-AI-Project/
├── api-backend/       # Laravel 11.x REST API
├── ai-engine/         # Python FastAPI engine (uvicorn app.main:app)
├── web-frontend/      # React 18 dashboard (Vite, runs locally)
├── docker-compose.yml # Container orchestration (DB + Laravel + worker + AI)
├── .github/workflows/ # CI/CD (deploy.yml → Azure VM)
└── .gitignore
```

> Note: the `docker-compose.yml` defines `database`, `laravel-app`, `queue-worker`, and `ai-engine`. There is **no** `react-app` service — the frontend is developed/run locally and pointed at the API via `VITE_API_URL`.

---

## 🏗️ Architecture

```text
┌──────────────────────────────────────────────────────────────┐
│                     Web Frontend (React)                      │
│   Runs locally (Vite :5173) · Admin & Doctor dashboard        │
│   Talks ONLY to Laravel (VITE_API_URL)                        │
└───────────────────────────┬──────────────────────────────────┘
                            │  HTTPS / REST (Bearer token)
┌───────────────────────────▼──────────────────────────────────┐
│                     Laravel API (Backend)                     │
│   Auth · RBAC · CRUD · Sessions · Queue Dispatcher            │
│   Proxies AI calls (e.g. /admin/ai/insert/*) → FastAPI        │
└───────┬───────────────────────┬────────────────┬─────────────┘
        │                       │                │
┌───────▼─────────┐   ┌─────────▼─────────┐  ┌───▼──────────────────┐
│     MySQL       │   │  Queue Worker     │  │   FastAPI AI Engine  │
│ Users·Diseases  │   │ (database queue)  │  │ RAG · LLM (Cloudflare │
│ Sessions·Docs   │   └───────────────────┘  │ / Groq / NVIDIA)      │
└─────────────────┘                          └─────────┬───────────┘
                                                      │
                                            ┌─────────▼──────────┐
                                            │  Supabase (pgvector)│
                                            │ Embeddings · Storage│
                                            └─────────────────────┘

        Notifications: Firebase Cloud Messaging (FCM) push
```

---

## 🛠️ Tech Stack

| Component | Technology |
| :--- | :--- |
| **Backend** | Laravel 11, PHP 8.3, MySQL 8.0, database-backed queue/cache/sessions (no Redis) |
| **AI Engine** | Python 3.11+, FastAPI, Supabase (`pgvector`), Cloudflare AI / Groq / NVIDIA, Playwright (PDF) |
| **Frontend** | React 18, Vite, Redux Toolkit, TailwindCSS, Firebase (FCM) |
| **Auth / Payments** | Laravel Sanctum/Passport-style tokens, Google OAuth, Stripe |
| **DevOps** | Docker, Docker Compose, GitHub Actions, Azure VM |

---

## 📦 Prerequisites

| Tool | Minimum Version | Verification |
| :--- | :--- | :--- |
| **Docker** *(recommended for the backend + AI engine)* | 24.x | `docker --version` |
| **Docker Compose** | 2.x | `docker compose version` |
| **Node.js** *(frontend)* | 18+ | `node --version` |
| **PHP / Composer** *(local backend, optional)* | 8.3 | `php --version` |
| **Python** *(local AI engine, optional)* | 3.11+ | `python --version` |
| Git | 2.x | `git --version` |

---

## 🚀 Quick Start

### Option 1: 🐳 Docker Compose (backend + AI engine)

Spins up MySQL, Laravel, the queue worker, and the FastAPI engine. The React frontend is **not** containerized — run it separately (Option 2).

```bash
# From repo root
docker compose up -d --build

# Logs
docker compose logs -f laravel-app
docker compose logs -f ai-engine
docker compose logs -f queue-worker
docker compose logs -f database
```

**Service URLs (Docker):**

| Service | URL | Port |
| :--- | :--- | :--- |
| **Laravel API** | http://localhost:8080 | 8080 |
| **AI Engine (FastAPI)** | http://localhost:5000/docs (Swagger) | 5000 |
| **MySQL** | localhost:3306 (`laravel_user` / `laravel_password`) | 3306 |

After the containers are up, run migrations:
```bash
docker compose exec laravel-app php artisan migrate --force
docker compose exec laravel-app php artisan db:seed --class=RolePermissionSeeder --force
```

### Option 2: 💻 Frontend (local, separate terminal)

```bash
cd web-frontend
npm install
cp .env.example .env          # set VITE_API_URL (see Environment Configuration)
npm run dev                   # http://localhost:5173
```

> The dashboard is served by Vite. Point `VITE_API_URL` at your Laravel instance (local `http://localhost:8000`, the Docker API on `:8080`, or a cloud/ngrok domain). The doctor-join form and the `/doctor` proxy both read this same value.

### Option 3: 🔧 Fully local (no Docker)

```bash
# Terminal 1 — MySQL (your own instance)
# Terminal 2 — Laravel
cd api-backend && composer install && cp .env.example .env
php artisan key:generate && php artisan migrate && php artisan serve   # :8000
# Terminal 3 — AI Engine
cd ai-engine && python -m venv venv && source venv/bin/activate
pip install -r requirements.txt && uvicorn app.main:app --reload        # :5000
# Terminal 4 — Frontend
cd web-frontend && npm install && npm run dev                          # :5173
```

---

## 📋 Environment Configuration

### `api-backend/.env` (Laravel)
```env
APP_NAME=MediScan
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1          # use "database" inside docker-compose
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_password

FASTAPI_URL=http://127.0.0.1:5000   # use "http://ai-engine:5000" in docker

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=your_mail@gmail.com
MAIL_PASSWORD="your_app_password"
MAIL_FROM_ADDRESS=your_mail@gmail.com
MAIL_FROM_NAME="Medical-AI project"

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/v1/auth/google/callback

STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret

FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
```

### `ai-engine/.env` (FastAPI)
```env
SUPABASE_URL=your_supabase_url
SUPABASE_KEY=your_supabase_key
GROQ_KEY=your_groq_key
NVIDIA_API_KEY=your_nvidia_key
CLOUDFLARE_API_KEY=your_cloudflare_key
CLOUDFLARE_ACCOUNT_ID=your_cloudflare_account_id
DEBUG=false
```

### `web-frontend/.env` (Vite)
```env
# Local Laravel
VITE_API_URL=http://localhost:8000
# For cloud / mobile testing, point at your ngrok or VM domain instead:
# VITE_API_URL=https://your-ngrok.ngrok-free.app
```
> Only `VITE_API_URL` is consumed (the `/doctor` dev proxy in `vite.config.js` reads the same value). The frontend does **not** call FastAPI directly.

---

## 🤖 AI Knowledge Insertion (Admin)

Admins insert medical knowledge via the dashboard:

1. Dashboard → upload a **PDF** or **JSON** file.
2. Frontend `POST`s to Laravel `POST /admin/ai/insert/pdf` or `/admin/ai/insert/json-file`.
3. `AiController` proxies the request (with file attachment) to FastAPI `/insert/*`.
4. FastAPI chunks the content, generates embeddings, and **upserts** them into the Supabase `embeddings` table (idempotent).

> The Supabase `embeddings` table needs INSERT/SELECT (and UPDATE) **Row-Level Security policies** enabled, or use the `service_role` key, otherwise inserts fail.

---

## 👩‍⚕️ Doctor Join Requests

1. A doctor fills the form at `/joining-requests` → `POST /doctor/sendJoinRequest` (multipart: `full_name`, `email`, `password`, `phone`, `specialization`, `years_of_experience`, `license_number`, `license_file` (PDF), `cv_file` (PDF), optional `biography`/`photo`).
2. The request is stored as `pending`.
3. An admin approves it via the admin panel or `POST /admin/doctor-requests/approve/{id}` (requires the queue worker running).

---

## 🔄 CI/CD (GitHub Actions → Azure VM)

`.github/workflows/deploy.yml` runs on push to `main`:

1. Azure login, start the VM (`medical-ai-vm`).
2. SCP the repo to `/home/azureuser/app`.
3. Generate `api-backend/.env` and `ai-engine/.env` from GitHub secrets.
4. `docker compose build` + `docker compose up -d` (MySQL, Laravel, queue-worker, AI engine).
5. Run `migrate` + `storage:link`, prune old images.
6. **Deallocate** the VM to save cost.

To read data on the VM (e.g. an OTP):
```bash
ssh azureuser@<vm-ip>
docker exec -it monorepo-db mysql -ularavel_user -plaravel_password laravel_db \
  -e "SELECT id,email,otp,otp_verified_at FROM users WHERE email='razangung@gmail.com';"
# or via Laravel tinker:
docker exec -it laravel-app php artisan tinker
>>> \App\Models\User::where('email','razangung@gmail.com')->first(['id','email','otp','otp_verified_at']);
```

---

## 🛠️ Useful Commands

```bash
# Stop everything
docker compose down

# Stop and DELETE database volume (WARNING: wipes data)
docker compose down -v

# Rebuild a service
docker compose up -d --build laravel-app

# Free disk space on the VM (safe — keeps the DB volume)
docker builder prune -a -f
docker system prune -a -f

# Tinker / migrations
docker compose exec laravel-app php artisan tinker
docker compose exec laravel-app php artisan migrate
```

---

## ✅ Verification Checklist

After startup:

- [ ] **MySQL**: `mysql -h localhost -u laravel_user -p` (password: `laravel_password`)
- [ ] **Laravel API**: `curl http://localhost:8080` (or `:8000` locally)
- [ ] **AI Engine**: open `http://localhost:5000/docs` (FastAPI Swagger UI)
- [ ] **Frontend**: `http://localhost:5173` (set `VITE_API_URL` first)
- [ ] **Queue worker** running (needed for doctor-request approvals)

---

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [FastAPI Documentation](https://fastapi.tiangolo.com)
- [React Documentation](https://react.dev)
- [Supabase pgvector](https://supabase.com/docs/guides/database/extensions/pgvector)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file)
- [GitHub Actions](https://docs.github.com/actions)
