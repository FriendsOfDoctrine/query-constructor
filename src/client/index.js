import React, { Component } from 'react'
import { Provider } from 'react-redux'
import SqlConstructor from './containers/SqlConstructor'
import configureStore from './redux/store/configureStore'
import reducer from './redux/modules/reducer'
import { clone } from './util/helpers'

function makeStateFromProps(props) {
  let initialState = reducer();

  initialState.select.data.aggregates = clone(props.aggregateFunctions) || {'': 'Ошибка загрузки'};
  initialState.select.data.entities = clone(props.entities) || {'': 'Ошибка загрузки'};
  initialState.select.values.aggregateFn = props.aggregateFunctions ? Object.keys(props.aggregateFunctions)[0] : '';
  initialState.api.url = props.propertiesUrl || '';
  return initialState;
}

class QueryConstructor extends Component {
  constructor(props) {
    super(props);

    this.store = configureStore(makeStateFromProps(props));

    if (module.hot) {
      module.hot.accept('./redux/modules/reducer', () => {
        const nextReducer = require('./redux/modules/reducer').default;
        this.store.replaceReducer(nextReducer);
      });
    }
  }

  render() {
    return (
      <Provider store={this.store}>
        <SqlConstructor prefix={this.props.prefix} />
      </Provider>
    );
  }
}

export default QueryConstructor;