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
  const token = localStorage.getItem('ecothread_session')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})
export default api