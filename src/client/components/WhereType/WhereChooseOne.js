import React, { Component } from 'react'
import SelectOne from '../Form/SelectOne'

class WhereChooseOne extends Component {
  render() {
    return (
      <SelectOne
        name={this.props.name}
        value={this.props.value}
        onChange={this.props.onChange}
        items={this.props.choices}
        required="required"
      />
    )
  }
}

export default WhereChooseOne;
