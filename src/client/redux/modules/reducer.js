import { combineReducers } from 'redux'
import apiCache from './apiCache'
import select from './select'
import where from './where'
import callApi from '../../util/apiCaller'
import { clone } from '../../util/helpers'

const reducer = combineReducers({
  select,
  where,
  apiCache,
});

export default reducer;

export function mapStateToQuery(stateSqlConstructor)
{
  return {
    aggregateFunction: stateSqlConstructor.select.values.aggregateFn,
    entity: stateSqlConstructor.select.values.entity,
    property: stateSqlConstructor.select.values.property,
    conditions: stateSqlConstructor.where.items.map((whereItem) => {
      return {
        type: whereItem.values.whereType,
        entity: whereItem.values.entity,
        property: whereItem.values.targetProperty,
        operator: whereItem.values.compareFunction,
        value: whereItem.values.compareValue,
      }
    }),
  };
}
