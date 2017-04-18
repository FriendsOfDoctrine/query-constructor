import { clone } from '../../util/helpers'
import { load } from './api'
import { uniqueId } from 'lodash'
import moment from 'moment'

const CLEAR_ALL = 'queryConstructor/where/CLEAR_ALL';
const ADD = 'queryConstructor/where/ADD';
const REMOVE = 'queryConstructor/where/REMOVE';
const SET_VALUE = 'queryConstructor/where/SET_VALUE';
const CHANGE_PROPERTY = 'queryConstructor/where/CHANGE_PROPERTY';
const LOAD_PROPERTIES = 'queryConstructor/where/LOAD_PROPERTIES';

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
      compareValue: moment().format('YYYY-MM-DD'),
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
    case LOAD_PROPERTIES: {
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
    case LOAD_PROPERTIES:
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
      if (state.api.cache[entity]) {
        data.properties = state.api.cache[entity].properties;
        data.entities = Object.assign({}, defaultEntity, state.api.cache[entity].joinableEntities);
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
    load(dispatch, getState, entity).then(data => {
      dispatch({ type: LOAD_PROPERTIES, indexWhere, data: data ? data.properties : {} });
      dispatch(changePropertyWhere(indexWhere, null));
    });
  }
}
