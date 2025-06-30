import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

// Intercepteur : ajoute le token aux requêtes
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('authToken')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Intercepteur : gère les erreurs (sans reload de la page)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Ne redirige pas ici, laisse le routeur gérer
      localStorage.removeItem('authToken')
    }
    return Promise.reject(error)
  },
)

export function useApi() {
  return {
    api,
    get: api.get,
    post: api.post,
    put: api.put,
    delete: api.delete,
  }
}
