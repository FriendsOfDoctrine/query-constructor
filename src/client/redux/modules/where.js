import { clone } from '../../util/helpers'
import callApi from '../../util/apiCaller'
import { set as setCache } from './apiCache'
import { uniqueId } from 'lodash'

const CLEAR_ALL = 'queryConstructor/where/CLEAR_ALL';
const ADD = 'queryConstructor/where/ADD';
const REMOVE = 'queryConstructor/where/REMOVE';
const SET_VALUE = 'queryConstructor/where/SET_VALUE';
const CHANGE_PROPERTY = 'queryConstructor/where/CHANGE_PROPERTY';
const LOAD = 'queryConstructor/where/LOAD'
const LOAD_SUCCESS = 'queryConstructor/where/LOAD_SUCCESS'
const LOAD_FAIL= 'queryConstructor/where/LOAD_FAIL'

const propertyTypes = {
  'integer': {
    arguments: [],
    operators: {
      '=': '=',
      '!=': '<>',
      '>': '>',
      '>=': '>=',
      '<': '<',
      '<=': '<=',
    },
    defaultValues: {
      compareFunction: '=',
      compareValue: '',
    },
  },
  'date': {
    arguments: [],
    operators: {
      '=': '=',
      '!=': '<>',
      '>': '>',
      '>=': '>=',
      '<': '<',
      '<=': '<=',
    },
    defaultValues: {
      compareFunction: '=',
      compareValue: '',
    },
  },
  'string': {
    arguments: [],
    operators: {
      'LIKE': 'содержит',
      'NOT LIKE': 'не содержит',
      '=': '=',
      '!=': '<>',
    },
    defaultValues: {
      compareFunction: 'LIKE',
      compareValue: '',
    },
  },
  'single_choice': {
    arguments: ['choices'],
    operators: {
      '=': '=',
      '!=': '<>',
    },
    defaultValues: {
      compareFunction: '=',
      compareValue: '',
    },
  },
  'multiple_choice': {
    arguments: ['choices'],
    operators: {
      'IN': 'равно',
      'NOT IN': 'кроме',
    },
    defaultValues: {
      compareFunction: 'IN',
      compareValue: [],
    },
  },
};

const initialItemState = {
  data: {
    entities: {},
    types: {
      'AND': 'И',
      'OR': 'ИЛИ',
    },
    properties: {},
    operators: {},
    propertyType: null,
    propertyArguments: {},
  },
  values: {
    whereType: null,
    entity: null,
    targetProperty: null,
    compareFunction: null,
    compareValue: null,
  },
};


const itemReducer = (state = initialItemState, action) => {
  switch (action.type) {
    case LOAD_SUCCESS: {
      const whereItem = clone(state);
      whereItem.data.properties = clone(action.data);
      return whereItem;
    }
    case CHANGE_PROPERTY: {
      const whereItem = clone(state);
      const property = state.data.properties[action.property];
      if (property) {
        whereItem.data.operators = clone(propertyTypes[property.type].operators);
        whereItem.data.propertyType = property.type;
        whereItem.data.propertyArguments = {};
        propertyTypes[property.type].arguments.forEach((argument) => {
          whereItem.data.propertyArguments[argument] = clone(property[argument]);
        });
        whereItem.values = {
          ...whereItem.values,
          ...propertyTypes[property.type].defaultValues,
        };
        if (property.type === 'single_choice') {
          whereItem.values = {
            ...whereItem.values,
            compareValue: Object.keys(property.choices)[0],
          };
        }
      } else {
        whereItem.data.operators = {};
        whereItem.data.propertyType = null;
        whereItem.data.propertyArguments = {};
        whereItem.values = {
          ...whereItem.values,
          targetProperty: null,
          compareFunction: null,
          compareValue: null,
        };
      }

      return whereItem;
    }
    case SET_VALUE:
      const values = clone(state.values);
      values[action.name] = action.value;
      return {
        ...state,
        values,
      };
    default:
      return state;
  }
};

const initialState = {
  entities: {},
  defaultProperties: null,
  items: [],
  propertiesUrl: ''
};

export default function reducer(state = initialState, action = {}) {
  switch (action.type) {
    case ADD:
      const whereItem = clone(initialItemState);
      const types = Object.keys(whereItem.data.types);
      whereItem.data.entities = clone(state.entities);
      whereItem.data.properties = clone(state.defaultProperties);
      whereItem.key = uniqueId('whereItem'); // Уникальный ключ для корректного рендера коллекции (заметно при удалении элемента). Нужна уникальность как минимум, в пределах коллекции.
      whereItem.values = {
        ...whereItem.values,
        whereType: state.items.length ? types[0] : 'NONE',
        entity: Object.keys(whereItem.data.entities)[0],
      }

      return {
        ...state,
        items: [
          ...state.items,
          whereItem
        ]
      };
    case REMOVE:
      return {
        ...state,
        items: state.items.filter((where, i) => i !== action.indexWhere)
      };
    case LOAD_SUCCESS:
    case CHANGE_PROPERTY:
    case SET_VALUE:
      return {
        ...state,
        items: [
          ...state.items.slice(0, action.indexWhere),
          itemReducer(state.items[action.indexWhere], action),
          ...state.items.slice(action.indexWhere + 1)
        ]
      };
    case CLEAR_ALL:
      return Object.assign({}, initialState, {
        defaultProperties: action.properties || {},
        entities: action.entities || {},
      });
    default:
      return state
  }
}

export function addWhere() {
  return { type: ADD };
}

export function removeWhere(indexWhere) {
  return { type: REMOVE, indexWhere };
}

export function clearAllWhere(entity) {
  return (dispatch, getState) => {
    const data = {};
    if (entity) {
      const state = getState();
      const defaultEntity = {};
      defaultEntity[entity] = state.select.data.entities[entity];
      data.entity = entity;
      if (state.apiCache[entity]) {
        data.properties = state.apiCache[entity].properties;
        data.entities = Object.assign({}, defaultEntity, state.apiCache[entity].joinableEntities);
      }
    }

    dispatch({ type: CLEAR_ALL, ...data });
  }
}

export function changeEntityWhere(indexWhere, entity) {
  return (dispatch, getState) => {
    dispatch(setValue(indexWhere, 'entity', entity));
    dispatch(loadProperties(indexWhere, entity));
  };
}

export function changePropertyWhere(indexWhere, property) {
  return (dispatch) => {
    dispatch(setValue(indexWhere, 'targetProperty', property));
    dispatch({ type: CHANGE_PROPERTY, indexWhere, property });
  };
}

export function setValue(indexWhere, name, value) {
  return { type: SET_VALUE, indexWhere, name, value };
}

function loadProperties(indexWhere, entity) {
  return (dispatch, getState) => {
    const setProperties = (properties) => {
      dispatch({ type: LOAD_SUCCESS, indexWhere, data: properties });
      dispatch(changePropertyWhere(indexWhere, null));
    };
    const state = getState();

    if (state.apiCache[entity]) {
      setProperties(state.apiCache[entity].properties);
    } else if (entity) {
      dispatch({ type: LOAD, indexWhere });

      callApi(state.where.propertiesUrl + '?entity=' + entity)
        .then(data => {
          if (data && data.result && data.result === 'success') {
            dispatch(setCache(entity, data.properties));
            setProperties(data.properties.properties);
          } else {
            if (data && data.message) {
              dispatch({ type: LOAD_FAIL, indexWhere, error: data.message });
            } else {
              dispatch({ type: LOAD_FAIL, indexWhere, error: 'Неопределенная ошибка' });
            }
          }
        });
    } else {
      setProperties({});
    }
  }
}
