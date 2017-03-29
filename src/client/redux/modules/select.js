import { clone } from '../../util/helpers'
import { clearAllWhere } from './where'
import { load } from './api'

const SET_VALUE = 'queryConstructor/select/SET_VALUE';
const LOAD_PROPERTIES = 'queryConstructor/select/LOAD_PROPERTIES';

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
    case LOAD_PROPERTIES:
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
    load(dispatch, getState, entity).then(data => {
      dispatch({ type: LOAD_PROPERTIES, data: data ? data.aggregatableProperties : {} });
      dispatch(clearAllWhere(entity));
    });
  }
}

export function setValue(name, value) {
  return { type: SET_VALUE, name, value };
}
