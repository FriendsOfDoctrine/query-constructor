import callApi from '../../util/apiCaller'
import { clone } from '../../util/helpers'
import Promise from 'es6-promise'

const LOAD = 'queryConstructor/api/LOAD';
const LOAD_SUCCESS = 'queryConstructor/api/LOAD_SUCCESS';
const LOAD_FAIL= 'queryConstructor/api/LOAD_FAIL';
const CACHE_SET = 'queryConstructor/api/CACHE_SET';

const initialState = {
  url: '',
  cache: {}
};

export default function reducer(state = initialState, action = {}) {
  switch (action.type) {
     case CACHE_SET:
      return {
        ...state,
        cache: {
          ...state.cache,
          ...action.data
        }
      }
    default:
      return state
  }
}

export function set(key, value) {
  const data = {};
  data[key] = clone(value);
  return { type: CACHE_SET, data };
}

export function load(dispatch, getState, entity) {
  return new Promise((resolve, reject) => {
    const state = getState();
    if (state.api.cache[entity]) {
      resolve(state.api.cache[entity]);
    } else if (entity) {
      dispatch({ type: LOAD });

      callApi(state.api.url + '?entity=' + entity)
        .then(data => {
          if (data && data.result && data.result === 'success') {
            dispatch({ type: LOAD_SUCCESS, data });
            dispatch(set(entity, data.properties));
            resolve(data.properties);
          } else {
            let error = 'Неопределенная ошибка';
            if (data && data.message) {
              error = data.message;
            }
            dispatch({ type: LOAD_FAIL, error });
            reject(error);
          }
        });
    } else {
      resolve(null);
    }
  });
}
