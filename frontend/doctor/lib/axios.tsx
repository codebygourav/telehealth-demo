import axios, { InternalAxiosRequestConfig } from "axios";
import { getAuthToken } from "./authToken";

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_BASE_URL,
  timeout: 30000,
  headers: {
    Accept: "application/json",
  },
});

api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = getAuthToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const requestUrl = String(error?.config?.url || "");
    const isAuthRequest =
      requestUrl.includes("/doctor/auth/login") ||
      requestUrl.includes("/doctor/auth/register") ||
      requestUrl.includes("/doctor/auth/forgot-password");

    if (
      typeof window !== "undefined" &&
      error?.response?.status === 401 &&
      !isAuthRequest
    ) {
      window.dispatchEvent(new CustomEvent("auth:unauthorized"));
    }

    return Promise.reject(error);
  },
);

export default api;
