import callApi from '../../util/apiCaller'
import { clone } from '../../util/helpers'
import { clearAllWhere } from './where'
import { set as setCache } from './apiCache'

const SET_VALUE = 'queryConstructor/select/SET_VALUE';
const LOAD = 'queryConstructor/select/LOAD';
const LOAD_SUCCESS = 'queryConstructor/select/LOAD_SUCCESS';
const LOAD_FAIL= 'queryConstructor/select/LOAD_FAIL';

const initialState = {
  data: {
    aggregates: {},
    entities: {},
    properties: {},
  },
  values: {
    aggregateFn: null,
    entity: null,
    property: null,
  },
  propertiesUrl: '',
}

export default function reducer(state = initialState, action = {}) {
  switch (action.type) {
    case LOAD_SUCCESS:
      const properties = Object.keys(action.data);
      return {
        ...state,
        data: {
          ...state.data,
          properties: Object.assign({}, action.data)
        },
        values: {
          ...state.values,
          property: properties.length? properties[0] : ''
        }
      }
    case SET_VALUE:
      const values = clone(state.values);
      values[action.name] = action.value;
      return {
        ...state,
        values,
      }
    default:
      return state
  }
}

export function loadProperties(entity) {
  return (dispatch, getState) => {
    const setProperties = (properties) => {
      dispatch({ type: LOAD_SUCCESS, data: properties });
      dispatch(clearAllWhere(entity));
    };
    const state = getState();

    if (state.apiCache[entity]) {
      setProperties(state.apiCache[entity].aggregatableProperties);
    } else if (entity) {
      dispatch({ type: LOAD });

      callApi(state.select.propertiesUrl + '?entity=' + entity)
        .then(data => {
          if (data && data.result && data.result === 'success') {
            dispatch(setCache(entity, data.properties));
            setProperties(data.properties.aggregatableProperties);
          } else {
            if (data && data.message) {
              dispatch({ type: LOAD_FAIL, error: data.message });
            } else {
              dispatch({ type: LOAD_FAIL, error: 'Неопределенная ошибка' });
            }
          }
        });
    } else {
      setProperties({});
    }
  }
}

export function setValue(name, value) {
  return { type: SET_VALUE, name, value };
}
