import React, { Component } from 'react'
import SelectMultiple from '../Form/SelectMultiple'

class WhereChooseMultiple extends Component {
  render() {
    return (
      <SelectMultiple
        name={this.props.name}
        value={this.props.value}
        onChange={this.props.onChange}
        items={this.props.choices}
        required="required"
      />
    )
  }
}

export default WhereChooseMultiple;
