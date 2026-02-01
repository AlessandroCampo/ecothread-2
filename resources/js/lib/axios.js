import axios from 'axios'

const api = axios.create({
  baseURL: '/',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
})

// Aggiungi CSRF token automaticamente
api.interceptors.request.use((config) => {
  const token = document.querySelector('meta[name="csrf-token"]')?.content
  if (token) {
    config.headers['X-CSRF-TOKEN'] = token
  }
  return config
})

export default api