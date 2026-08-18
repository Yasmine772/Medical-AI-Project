import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import App from "./App.jsx";

import { PersistGate } from "redux-persist/integration/react";
import { store, persistor } from "./store/index.js";

import { Toaster } from "react-hot-toast";
import { Provider } from "react-redux";

createRoot(document.getElementById("root")).render(
  <StrictMode>
    <Provider store={store}>
      <PersistGate loading={null} persistor={persistor}>
        <Toaster
          position="bottom-center"
          reverseOrder={false}
          toastOptions={{
            duration: 3000,
            style: {
              background: "#72A6BB", 
              color: "#fff", 
              borderRadius: "16px", 
              padding: "12px 20px",
              fontWeight: "500",
            },
          }}
        />
        <App />
      </PersistGate>
    </Provider>
  </StrictMode>,
);
