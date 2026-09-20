import axios from "axios";

const apiClient = axios.create({
  baseURL:
    process.env.NEXT_PUBLIC_API_URL ??
    "http://localhost/academic-krs/backend/public/api",

  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },

  timeout: 30_000,
});

export default apiClient;