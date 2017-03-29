import fetch from 'isomorphic-fetch'

export const API_URL = window.BASE_API_URL

export default function callApi(endpoint, method = 'get', body) {
  return fetch(`${API_URL}/${endpoint}`, {
      headers: { 'content-type': 'application/json' },
      method,
      body: JSON.stringify(body),
    })
    .then(response => response.json())
    .catch(err => alert('Не удалось установить соедение с сервером. Проверьте Ваше подключение к сети.'))
}