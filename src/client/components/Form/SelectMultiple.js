import React, { Component } from 'react'
import Select from './Select'

class SelectMultiple extends Component {

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
          selected: selected && selected.indexOf(value) !== -1,
        });
      }
    }
    return options;
  }

  render() {
    return <Select
        multiple="multiple"
        options={this.transformItemsToOptions(this.props.items, this.props.value, this.props.placeholder)}
        {...this.props}
      />
  }
}

export default SelectMultiple