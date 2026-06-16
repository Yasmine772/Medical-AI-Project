# 🧠 MediScan — AI Engine

This repository contains the **AI processing engine** of the Medical Diagnosis AI System, responsible for analyzing medical images and data to produce diagnostic results using trained machine learning models.

---

## 🛠 Prerequisites

| Tool | Version |
|------|---------|
| Python | 3.10 or higher |
| pip | Latest stable |
| Virtual Environment | Recommended (see setup below) |

---

## 🚀 Quick Start (Local Development)

### 1. Create & Activate Virtual Environment

```bash
python -m venv venv
```

```bash
# Windows
venv\Scripts\activate

# macOS / Linux
source venv/bin/activate
```

### 2. Install Dependencies

```bash
pip install -r requirements.txt
```

### 3. Configure Environment Variables

Create a `.env` file in the project root:

```env
# API Configuration
API_HOST=0.0.0.0
API_PORT=5000

# Pinecone Vector Database
PINECONE_API_KEY=your_pinecone_api_key
PINECONE_INDEX=your_index_name

# Google Gemini LLM
GOOGLE_API_KEY=your_google_api_key

# Database Connection
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=laravel_db
DB_USER=laravel_user
DB_PASSWORD=laravel_password

# Logging
LOG_LEVEL=INFO
```

### 4. Run the Engine

```bash
# Standard startup
python main.py

# Or with hot-reload (development)
uvicorn main:app --reload --host 0.0.0.0 --port 5000
```

> If your AI engine entrypoint is not `main.py`, replace the command with the correct filename, for example `python app.py` or `uvicorn api:app`.

The API will be available at:
- 🌐 **Main API**: http://localhost:5000
- 📚 **Interactive Docs (Swagger UI)**: http://localhost:5000/docs
- 📖 **Alternative Docs (ReDoc)**: http://localhost:5000/redoc

---

## 🔗 Integration with Backend

The AI Engine communicates with the Laravel backend for:
- Patient session data
- Diagnostic history
- User authentication

Ensure the Laravel API is running on `http://localhost:8000` or update the connection string in `.env`.

---

## 📂 Project Structure

```
ai-engine/
├── models/         # Trained model files (.h5 / .pkl) — NOT committed to Git (see note below)
├── data/           # Test datasets — NOT committed to Git
├── requirements.txt
├── Dockerfile
├── README.md
└── <entrypoint>.py  # e.g. main.py or app.py
```

> Ensure the AI engine entrypoint file exists in `ai-engine/` before running locally or via Docker.

> **⚠️ Models & Data are not tracked in this repository.**
> Download the required model files from **[Google Drive / external link — update here]** and place them inside the `/models` directory before running.

---

## 📦 Managing Dependencies

All project dependencies are tracked in `requirements.txt`. This file **must be kept up to date** at all times.

**Whenever you install a new package, run:**

```bash
pip freeze > requirements.txt
```

Then commit the updated file immediately:

```bash
git add requirements.txt
git commit -m "chore: update requirements.txt"
```

> Never leave `requirements.txt` out of sync — other team members depend on it to reproduce your environment exactly.

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
   git commit -m "feat: add X-ray classification model"
   ```

3. **Push your branch** and open a **Pull Request** targeting `develop`:
   ```bash
   git push origin feature/your-feature-name
   ```

4. All Pull Requests must be **reviewed and approved** by the Team Leader before merging.

### Commit Message Convention

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
