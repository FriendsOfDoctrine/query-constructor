import React, { Component } from 'react'
import InputDate from '../Form/InputDate'

class WhereDate extends Component {
  render() {
    return (
      <InputDate
        name={this.props.name}
        defaultValue={this.props.value}
        onChange={this.props.onChange}
        required="required"
      />
    )
  }
}

export default WhereDate;
