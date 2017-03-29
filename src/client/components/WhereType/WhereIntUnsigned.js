import React, { Component } from 'react'
import Input from '../Form/Input'

class WhereText extends Component {
  render() {
    return (
      <Input
        name={this.props.name}
        defaultValue={this.props.value}
        onChange={this.props.onChange}
        inputType="number"
        min="0"
        step="1"
        required="required"
      />
    )
  }
}

export default WhereText;
