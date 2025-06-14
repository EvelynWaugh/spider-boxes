import React from "react";
import ReactDOM from "react-dom/client";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import "@/styles/frontend.css";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      staleTime: 5 * 60 * 1000, // 5 minutes
    },
  },
});

// Frontend field rendering
const fieldContainers = document.querySelectorAll("[data-spider-boxes-field]");

fieldContainers.forEach((container) => {
  const fieldType = container.getAttribute("data-field-type");
  const fieldId = container.getAttribute("data-field-id");

  if (fieldType && fieldId) {
    (async () => {
      ReactDOM.createRoot(container as HTMLElement).render(
        <React.StrictMode>
          <QueryClientProvider client={queryClient}></QueryClientProvider>
        </React.StrictMode>,
      );
    })();
  }
});
