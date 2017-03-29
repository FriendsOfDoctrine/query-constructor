import React, { Component } from 'react'
import { uniqueId } from 'lodash'

class Input extends Component {
  constructor(props) {
    super(props);

    this.inputElement = null;
  }

  componentWillMount() {
    this.uniqueId = uniqueId('id');
  }

  render() {
    let labelClassName = 'control-label';
    let inputClassName = 'form-control';
    let inputAttributes = {
      id: this.props.id || this.uniqueId,
      onChange: this.props.onChange,
      defaultValue: this.props.defaultValue,
      type: this.props.inputType || 'text',
      min: this.props.min,
      max: this.props.max,
      step: this.props.step,
    };
    if (this.props.required) {
      labelClassName += ' required';
      inputAttributes.required = 'required';
    }
    return (
      <div className="form-group">
        {this.props.label &&
          <label className={labelClassName} htmlFor={inputAttributes.id}>{this.props.label}</label>
        }
        <input
          ref={(input) => { this.inputElement = input; }}
          name={this.props.name}
          className={inputClassName}
          {...inputAttributes}
        />
      </div>
    );
  }
}

export default Input