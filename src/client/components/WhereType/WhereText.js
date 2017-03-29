import React, { Component } from 'react'
import Input from '../Form/Input'

class WhereText extends Component {
  render() {
    return (
      <Input
        name={this.props.name}
        defaultValue={this.props.value}
        onChange={this.props.onChange}
        required="required"
      />
    )
  }
}

export default WhereText;
