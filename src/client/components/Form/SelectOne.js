import React, { Component } from 'react'
import Select from './Select'

class SelectOne extends Component {

  transformItemsToOptions(items, selected, placeholder = '') {
    let options = [];
    if (placeholder) {
      options.push({
        value: '',
        label: placeholder
      });
    }
    for (let value in items) {
      if (items.hasOwnProperty(value)) {
        options.push({
          value: value,
          label: items[value],
          selected: value === selected,
        });
      }
    }
    return options;
  }

  render() {
    return <Select
        options={this.transformItemsToOptions(this.props.items, this.props.value, this.props.placeholder)}
        {...this.props}
      />
  }
}

export default SelectOne