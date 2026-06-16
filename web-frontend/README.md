# 🖥️ MediScan — Frontend (React)

This repository contains the **frontend client** of the Medical Diagnosis AI System, built with [React](https://react.dev/) and [Vite](https://vitejs.dev/) for a fast, modern development experience.

> ⚠️ **Dependency:** This project requires the Laravel backend API to be running on `http://localhost:8000` before starting. See the [backend repository](#) for setup instructions.

---

## ✨ Key Features

- Fully **responsive UI** across all screen sizes
- Seamless integration with the **Laravel REST API** via Axios
- Scalable **state management** 

---

## 🛠 Prerequisites

| Tool | Version |
|------|---------|
| Node.js | 18 LTS or higher |
| Package Manager | npm / yarn / pnpm |

---

## 🚀 Quick Start

### 1. Enter the Service Directory

From the monorepo root:

```bash
cd web-frontend
```

### 2. Install Dependencies

```bash
npm install
```

### 3. Configure Environment Variables

Create a `.env` file in the project root:

```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_AI_ENGINE_URL=http://localhost:5000
```

### 4. Start the Development Server

```bash
npm run dev
```

The app will be available at `http://localhost:5173`.

> Make sure the backend API is running before using the frontend.

---

## 📂 Project Structure

```
web-frontend/
├── src/
│   ├── components/     # Reusable UI components (Buttons, Cards, Inputs)
│   ├── pages/          # Top-level application pages
│   └── services/       # Axios instance and API call handlers
├── public/             # Static assets
├── .env                # Local environment variables (not committed)
└── vite.config.js      # Vite configuration
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
   git commit -m "feat: add diagnosis result page"
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
