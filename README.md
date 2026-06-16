 
<div align="center">

<img src="https://img.shields.io/badge/Medical%20AI-Diagnostic%20System-blue?style=for-the-badge&logo=heart&logoColor=white" alt="Medical AI"/>

# 🏥 Medical-AI-Project

### Intelligent Medical Diagnostic System — Syrian Market Edition

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Python](https://img.shields.io/badge/Python-3.11+-3776AB?style=flat-square&logo=python&logoColor=white)](https://python.org)
[![React](https://img.shields.io/badge/React-18.x-61DAFB?style=flat-square&logo=react&logoColor=black)](https://reactjs.org)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Pinecone](https://img.shields.io/badge/Pinecone-Vector%20DB-00B4D8?style=flat-square)](https://pinecone.io)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat-square&logo=docker&logoColor=white)](https://docker.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

> An interactive, AI-driven primary medical diagnostic system. Utilizing a **hybrid RAG architecture** that integrates structured relational data with generative AI, specifically tailored for the Syrian medical landscape.

[Features](#-features) • [Architecture](#-architecture) • [Prerequisites](#-prerequisites) • [Getting Started](#-getting-started) • [Folder Structure](#-folder-structure)

</div>

---

## 📋 Overview

**Medical-AI-Project** is an intelligent medical diagnostic system based on the **Socratic questioning methodology**. Instead of merely processing symptoms to provide an instant diagnosis, the system engages the patient with intelligent, sequential questions to extract precise information. It then leverages a **Retrieval-Augmented Generation (RAG)** pipeline to query a comprehensive medical knowledge base, ensuring accurate and reliable diagnostic suggestions.

Designed to enhance access to primary medical insights in the Syrian market, this tool serves as a clinical decision support system for practitioners, not a replacement for professional medical advice.

---

## ✨ Features

### 🧠 AI Engine
- **End-to-End RAG Pipeline:** Retrieves medical knowledge from clinical PDFs and protocols.
- **Cross-Encoder Reranking:** Enhances diagnostic precision by re-evaluating retrieved documents.
- **Socratic Questioning Engine:** Implements logic to conduct interactive, step-by-step patient interviews.
- **Semantic Search:** Understands medical intent beyond simple keyword matching.

### 👨‍⚕️ Patient Experience
- Guided, intelligent symptom questionnaire.
- Comprehensive history of diagnostic sessions.
- Exportable PDF diagnostic reports.
- Intuitive, user-friendly interface.

### 👩‍💼 For Doctors & Admins
- Integrated dashboard for disease and symptom management.
- Patient and session tracking.
- Full Audit Logging for accountability.
- Detailed statistical reporting.

### 🔒 Security
- Protection against **Indirect Prompt Injection**.
- **Audit Logging** for every system interaction.
- Sensitive data encryption.
- Multi-tier Role-Based Access Control (Patient / Doctor / Admin).
---
## 📂 Project Structure
This repository follows a Monorepo structure:

- `/api-backend`: Laravel-based REST API for system management and orchestration.
- `/ai-engine`: FastAPI service responsible for RAG, vector searches, and LLM integration.
- `/web-frontend`: React-based Admin Dashboard.

---

```
Medical-AI-Project/
├── api-backend/      # Laravel 11.x REST API
├── react-app/        # React 18 Admin Dashboard (Vite)
├── docker-compose.yml# Container orchestration
└── .gitignore        # Global git ignore configuration
└──  README.md
```

---

## 🏗️ Architecture

```text
┌─────────────────────────────────────────────────────────┐
│                    Clients Layer                        │
│   Flutter Mobile App    React Web Dashboard             │
└─────────────────────┬───────────────────────────────────┘
                      │ HTTP / REST API
┌─────────────────────▼───────────────────────────────────┐
│                Laravel API (Backend)                    │
│    Auth · CRUD · Sessions · Queue Dispatcher            │
└──────────┬──────────────────────────┬───────────────────┘
           │                          │ Redis Queue
┌──────────▼──────────┐   ┌──────────▼───────────────────┐
│        MySQL        │   │    Python FastAPI Engine     │
│  Users · Diseases   │   │ RAG · Reranker · Gemini LLM  │
│ Sessions · Doctors  │   └──────────┬───────────────────┘
└─────────────────────┘              │
                         ┌──────────▼───────────────────┐
                         │           Pinecone           │
                         │    Vector DB · Embeddings    │
                         └──────────────────────────────┘

 ```                    


---

## 🛠️ Tech Stack

| Component | Technology |
| :--- | :--- |
| **Backend** | Laravel 11.x, PHP 8.3, MySQL 8.0, Redis |
| **AI Engine** | Python 3.11, FastAPI, LangChain, Pinecone, Google Gemini 1.5 Pro |
| **Frontend** | React 18, TypeScript, TailwindCSS |
| **Mobile** | Flutter 3.x, Riverpod |
| **DevOps** | Docker, Docker Compose, GitHub Actions |

---

## 📦 Prerequisites

| Tool | Minimum Version | Verification |
| :--- | :--- | :--- |
| **Docker** *(Recommended)* | 24.x | `docker --version` |
| **Docker Compose** *(Recommended)* | 2.x | `docker compose version` |
| Git | 2.x | `git --version` |

> **Note:** Docker is recommended for easier orchestration. For local development without Docker, see [Local Development Setup](#-local-development-setup).

---
## 🚀 Quick Start

This project is structured as a **Monorepo** with three independent services. Choose your preferred setup method below.

### Option 1: 🐳 Docker Compose (Recommended)

#### 1.1 Prerequisites
Ensure you have installed:
* **Docker** (v24+)
* **Docker Compose** (v2+)
* **Git**

#### 1.2 Clone the Repository
```bash
git clone https://github.com/Yasmine772/Medical-AI-Project.git
cd Medical-AI-Project
```

#### 1.3 Start All Services
```bash
# Build and start all containers (MySQL, Laravel API, React Dashboard, AI Engine)
docker-compose up -d

# View logs from all services
docker-compose logs -f

# View logs from specific service
docker-compose logs -f laravel-app
docker-compose logs -f react-app
docker-compose logs -f ai-engine
docker-compose logs -f database
```

#### 1.4 Service URLs
Once running, access the services at:

| Service | URL | Port |
| :--- | :--- | :--- |
| **Laravel API** | http://localhost:8080 | 8080 |
| **React Dashboard** | http://localhost:3000 | 3000 |
| **AI Engine (FastAPI)** | http://localhost:5000 | 5000 |
| **MySQL Database** | localhost:3306 | 3306 |

#### 1.5 Useful Docker Commands
```bash
# Stop all services
docker-compose down

# Stop services and remove volumes (WARNING: deletes database data)
docker-compose down -v

# Rebuild specific service
docker-compose up -d --build laravel-app

# Execute command inside container
docker-compose exec laravel-app php artisan migrate
docker-compose exec ai-engine python main.py

# View resource usage
docker stats
```

---

### Option 2: 💻 Local Development Setup (Without Docker)

If you prefer to run services locally without Docker, follow the README.md in each service folder:

#### **API Backend (Laravel)**
📖 See [api-backend/README.md](api-backend/README.md) for:
- PHP & Composer installation
- Database setup
- Laravel configuration & migrations
- Local server startup

```bash
cd api-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

#### **AI Engine (Python/FastAPI)**
📖 See [ai-engine/README.md](ai-engine/README.md) for:
- Python & virtual environment setup
- Dependencies installation
- Environment configuration
- FastAPI server startup

```bash
cd ai-engine
python -m venv venv

# Windows
venv\Scripts\activate
# macOS / Linux
source venv/bin/activate

pip install -r requirements.txt
python main.py
```

#### **Web Frontend (React)**
📖 See [web-frontend/README.md](web-frontend/README.md) for:
- Node.js installation
- Dependencies setup
- Vite development server
- Build configuration

```bash
cd web-frontend
npm install
npm run dev
```

---

## 🔄 Service Dependencies & Startup Order

When running **locally without Docker**, start services in this order:

1. **MySQL Database** (if not using Docker)
   ```bash
   # Ensure MySQL server is running
   mysql --version
   ```

2. **Laravel API Backend**
   ```bash
   cd api-backend && php artisan serve  # Runs on http://localhost:8000
   ```

3. **AI Engine (FastAPI)**
   ```bash
   cd ai-engine && python main.py  # Runs on http://localhost:5000
   ```

4. **React Frontend**
   ```bash
   cd web-frontend && npm run dev  # Runs on http://localhost:5173
   ```

---

## 📋 Environment Configuration

### Docker Environment
Environment variables are automatically configured in `docker-compose.yml`:
- **Database**: MySQL with root password and Laravel credentials
- **Laravel**: Database connection, app key, cache drivers
- **Python**: PYTHONUNBUFFERED flag for real-time logging

### Local Development Environment

Create `.env` files in each service folder:

**api-backend/.env**
```env
APP_NAME=MediScan
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=root
DB_PASSWORD=
```

**ai-engine/.env**
```env
PINECONE_API_KEY=your_key
PINECONE_INDEX=your_index
GOOGLE_API_KEY=your_gemini_api_key
PYTHONUNBUFFERED=1
```

**web-frontend/.env**
```env
VITE_API_URL=http://localhost:8000/api
VITE_AI_ENGINE_URL=http://localhost:5000
```

---

## 🛠️ Development Workflow

### With Docker Compose
```bash
# Watch for changes and auto-reload
docker-compose up -d

# SSH into container for debugging
docker-compose exec laravel-app bash
docker-compose exec ai-engine bash

# Run database migrations
docker-compose exec laravel-app php artisan migrate

# Run tests
docker-compose exec laravel-app php artisan test
docker-compose exec ai-engine pytest
```

### Local Development
```bash
# Terminal 1: Laravel Backend
cd api-backend && php artisan serve

# Terminal 2: AI Engine
cd ai-engine && python main.py

# Terminal 3: React Frontend
cd web-frontend && npm run dev

# Terminal 4: Database (if needed)
mysql -u root
```

---

## ✅ Verification Checklist

After startup, verify all services:

- [ ] **Database**: `mysql -h localhost -u laravel_user -p` (password: laravel_password)
- [ ] **Laravel API**: GET http://localhost:8080 (or 8000 locally)
- [ ] **AI Engine**: GET http://localhost:5000/docs (or 5000 locally) — FastAPI Swagger UI
- [ ] **React Dashboard**: http://localhost:3000 (or 5173 locally)

---

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [FastAPI Documentation](https://fastapi.tiangolo.com)
- [React Documentation](https://react.dev)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file)
- [Pinecone Vector Database](https://docs.pinecone.io)