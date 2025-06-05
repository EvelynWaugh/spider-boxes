import {useCallback} from "react";

interface APIResponse<T = any> {
  data: T;
  success: boolean;
  message?: string;
}

export const useAPI = () => {
  const baseURL =
    (window as any).spiderBoxesAdmin?.restUrl || "wp-json/spider-boxes/v1";
  const nonce = (window as any).spiderBoxesAdmin?.nonce || "";

  const request = useCallback(
    async <T = any>(
      endpoint: string,
      options: RequestInit = {}
    ): Promise<T> => {
      const url = `${baseURL}${endpoint}`;

      const headers: HeadersInit = {
        "Content-Type": "application/json",
        "X-WP-Nonce": nonce,
        ...options.headers,
      };

      try {
        const response = await fetch(url, {
          ...options,
          headers,
        });

        if (!response.ok) {
          const errorData = await response.json().catch(() => ({}));
          throw new Error(
            errorData.message ||
              `HTTP ${response.status}: ${response.statusText}`
          );
        }
        const data = await response.json();

        // Check if this is the expected APIResponse format
        if (data && typeof data === "object" && "success" in data) {
          // This is a wrapped API response
          const apiResponse: APIResponse<T> = data;
          if (!apiResponse.success) {
            throw new Error(apiResponse.message || "API request failed");
          }
          return apiResponse.data;
        } else {
          // This is a direct response (like reviews endpoint)
          return data as T;
        }
      } catch (error) {
        if (error instanceof Error) {
          throw error;
        }
        throw new Error("An unexpected error occurred");
      }
    },
    [baseURL, nonce]
  );

  const get = useCallback(
    <T = any>(endpoint: string): Promise<T> => {
      return request<T>(endpoint, {method: "GET"});
    },
    [request]
  );

  const post = useCallback(
    <T = any>(endpoint: string, data?: any): Promise<T> => {
      return request<T>(endpoint, {
        method: "POST",
        body: data ? JSON.stringify(data) : undefined,
      });
    },
    [request]
  );

  const put = useCallback(
    <T = any>(endpoint: string, data?: any): Promise<T> => {
      return request<T>(endpoint, {
        method: "PUT",
        body: data ? JSON.stringify(data) : undefined,
      });
    },
    [request]
  );

  const patch = useCallback(
    <T = any>(endpoint: string, data?: any): Promise<T> => {
      return request<T>(endpoint, {
        method: "PATCH",
        body: data ? JSON.stringify(data) : undefined,
      });
    },
    [request]
  );

  const del = useCallback(
    <T = any>(endpoint: string): Promise<T> => {
      return request<T>(endpoint, {method: "DELETE"});
    },
    [request]
  );

  return {
    request,
    get,
    post,
    put,
    patch,
    del,
  };
};
