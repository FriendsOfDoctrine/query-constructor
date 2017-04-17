import React from 'react'
import { render } from 'react-dom'
import { Provider } from 'react-redux'
import QueryConstructor from './QueryConstructor'
import configureStore from './redux/store/configureStore'

const old = window.fodQueryConstructor;
const QueryConstructorContainer = function (options) {
  let opts = Object.assign({}, defaults, options);
  const selector = opts.selector;
  delete opts.selector;
  render(
    <QueryConstructor {...opts} />,
    document.querySelector(selector)
  );
};

window.fodQueryConstructor = QueryConstructorContainer;

window.fodQueryConstructor.noConflict = function () {
  window.fodQueryConstructor.datepicker = old;
  return this;
};

const defaults = window.fodQueryConstructor.defaults = {
  'selector': '#fod-query-constructor',
  'prefix': '',
  'aggregateFunctions': {'': 'Ошибка загрузки'},
  'enities': {'': 'Ошибка загрузки'},
  'propertiesUrl': '',
};
