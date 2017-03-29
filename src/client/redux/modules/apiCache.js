import { clone } from '../../util/helpers'

const SET = 'queryConstructor/apiCache/SET';

export default function reducer(state = {}, action = {}) {
  switch (action.type) {
     case SET:
      return {
        ...state,
        ...action.data
      }
    default:
      return state
  }
}

export function set(key, value) {
  const data = {};
  data[key] = clone(value);
  return { type: SET, data };
}
