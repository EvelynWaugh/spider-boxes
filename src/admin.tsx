import React from "react";
import ReactDOM from "react-dom/client";
import {QueryClient, QueryClientProvider} from "@tanstack/react-query";
import {AdminApp} from "@/components/AdminApp";
import {ReviewsApp} from "@/components/ReviewsApp";
import "@/styles/admin.css";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      staleTime: 5 * 60 * 1000, // 5 minutes
    },
  },
});

// Admin App for main Spider Boxes page
const mainAppRoot = document.getElementById("spider-boxes-main-app");
if (mainAppRoot) {
  ReactDOM.createRoot(mainAppRoot).render(
    <React.StrictMode>
      <QueryClientProvider client={queryClient}>
        <AdminApp />
      </QueryClientProvider>
    </React.StrictMode>
  );
}

// Reviews App for Spider Product Reviews page
const reviewsAppRoot = document.getElementById("spider-boxes-reviews-app");
if (reviewsAppRoot) {
  ReactDOM.createRoot(reviewsAppRoot).render(
    <React.StrictMode>
      <QueryClientProvider client={queryClient}>
        <ReviewsApp />
      </QueryClientProvider>
    </React.StrictMode>
  );
}
