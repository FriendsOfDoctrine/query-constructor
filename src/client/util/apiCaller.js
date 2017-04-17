import fetch from 'isomorphic-fetch'

export default function callApi(endpoint, method = 'get', body) {
  return fetch(endpoint, {
      headers: { 'content-type': 'application/json' },
      method,
      body: JSON.stringify(body),
    })
    .then(response => response.json())
    .catch(err => alert('Не удалось установить соедение с сервером. Проверьте Ваше подключение к сети.'))
}