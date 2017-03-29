import React, { Component } from 'react'
import { uniqueId } from 'lodash'

class Select extends Component {
  constructor(props) {
    super(props);

    this.selectElement = null;
  }

  componentWillMount() {
    this.uniqueId = uniqueId('id');
  }

  getValueFromProps(props) {
    // the default value for the <select> (selected for ReactJS)
    // http://facebook.github.io/react/docs/forms.html#why-select-value
    return props.options.reduce(function (defaultValue, opt, i) {
      // if this is the selected option, set the <select>'s defaultValue
      if (opt.selected === true || opt.selected === 'selected') {
        // if the <select> is a multiple, push the values
        // to an array
        if (props.multiple) {
          defaultValue.push( opt.value );
        } else {
          // otherwise, just set the value.
          // NOTE: this means if you pass in a list of options with
          // multiple 'selected', WITHOUT specifiying 'multiple',
          // properties the last option in the list will be the ONLY item selected.
          defaultValue = opt.value;
        }
      }
      return defaultValue;
    }, props.multiple ? [] : '');
  }

  render() {
    const options = this.props.options.map(function(opt, i){
      // attribute schema matches <option> spec; http://www.w3.org/TR/REC-html40/interact/forms.html#h-17.6
      // EXCEPT for 'key' attribute which is requested by ReactJS
      return <option key={i} value={opt.value} label={opt.label}>{opt.label}</option>;
    }, this);

    let labelClassName = 'control-label';
    let selectClassName = this.props.className || 'form-control';
    let selectAttributes = {
      id: this.props.id || this.uniqueId,
      name: this.props.name,
      multiple: this.props.multiple,
      onChange: this.props.onChange,
      defaultValue: this.getValueFromProps(this.props),
    };
    if (this.props.required) {
      labelClassName += ' required';
      selectAttributes.required = 'required';
    }
    return (
      <div className="form-group">
        {this.props.label &&
          <label className={labelClassName} htmlFor={selectAttributes.id}>{this.props.label}</label>
        }
        <select
          ref={(select) => { this.selectElement = select; }}
          className={selectClassName}
          {...selectAttributes}
        >
            {options}
        </select>
      </div>
    );
  }
}

Select.defaultProps = {
  multiple: false
  /*
  name: 'mySelect'
  options: [
    {
      value: optionOne
      label: "Option One"
    },
    {
      value: optionsTwo
      label: "Option Two",
      selected: true,
    }
  ]
  */
}

export default Select