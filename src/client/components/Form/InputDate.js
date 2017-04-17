import React, { Component } from 'react'
import Input from './Input'

class InputDate extends Component {
  render() {
    return <Input
      {...this.props}
      ref={
        (input) => {
          if (input && window.jQuery && window.jQuery(input.inputElement).datepicker) {
            window.jQuery(input.inputElement).datepicker({
                format: (this.props.dateFormat || 'yyyy-mm-dd')
            }).off('changeDate').on('changeDate', function () {
              // Чтобы сработал react.onChange
              const event = new Event('input', { bubbles: true });
              input.inputElement.dispatchEvent(event);
            });
          }
        }
      }
    />;
  }
}

export default InputDate