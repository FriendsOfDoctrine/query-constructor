import React, { Component } from 'react'
import PropTypes from 'prop-types';
import Input from './Input'
import DatePicker from 'react-datepicker'
import moment from 'moment'
import 'react-datepicker/dist/react-datepicker.css'
import { uniqueId } from 'lodash'

class InputDate extends Component {
  constructor(props) {
    super(props);

    const startDate = moment(props.defaultDate);
    this.state = {
      startDate: startDate.isValid() ? startDate : null
    };

    this.handleChange = this.handleChange.bind(this);
  }

  componentWillMount() {
    this.uniqueId = uniqueId('id');
  }

  handleChange(date) {
    this.props.onChange({
      target: {
        name: this.props.name,
        value: date.format('YYYY-MM-DD')
      }
    });
    this.setState({
      startDate: date
    });
  }

  render() {
    let labelClassName = 'control-label';
    let inputClassName = 'form-control';
    let inputAttributes = {
      id: this.props.id || this.uniqueId,
      name: this.props.name,
      type: 'text',
    };
    if (this.props.required) {
      inputAttributes.required = 'required';
      labelClassName += ' required';
    }

    return (
      <div className="form-group">
        {this.props.label &&
          <label className={labelClassName} htmlFor={inputAttributes.id}>{this.props.label}</label>
        }
        <DatePicker
          dateFormat="YYYY-MM-DD"
          selected={this.state.startDate}
          className={inputClassName}
          onChange={this.handleChange}
          {...inputAttributes}
        />
      </div>
    );
  }
}

export default InputDate