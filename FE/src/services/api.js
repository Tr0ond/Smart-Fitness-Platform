import axios from 'axios'

// Chỉ chuẩn bị HTTP client; chưa gọi API hoặc thêm cơ chế xác thực.
const ketNoiApi = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api',
  timeout: 15000,
  headers: { Accept: 'application/json' },
})

export default ketNoiApi
