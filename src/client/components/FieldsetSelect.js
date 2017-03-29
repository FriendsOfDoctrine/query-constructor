import React, { Component } from 'react'
import SelectOne from './Form/SelectOne'
import { makeFullName, extractShortName } from '../util/helpers'

class FieldsetSelect extends Component {
  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
    this.handleEntityChange = this.handleEntityChange.bind(this);
    this.makeFullName = makeFullName.bind(this);
  }

  handleChange(event) {
    this.props.setValue(extractShortName(event.target.name), event.target.value);
  }

  handleEntityChange(event) {
    this.handleChange(event);
    this.props.loadProperties(event.target.value);
  }

  render() {
    const data = this.props.data;
    const values = this.props.values;
    return (
      <div className="sql-constructor-fieldset-select">
        <label className="control-label">Выбрать</label>
        <div className="row">
          <div className="col-xs-4">
            <SelectOne
              name={this.makeFullName('aggregateFn')}
              value={values.aggregateFn}
              items={data.aggregates}
              onChange={this.handleChange}
              className="form-control"
            />
          </div>
          <div className="col-xs-4">
            <SelectOne
              name={this.makeFullName('entity')}
              value={values.entity}
              items={data.entities}
              placeholder="Выберите сущность..."
              onChange={this.handleEntityChange}
              className="form-control"
              required="required"
            />
          </div>
          <div className="col-xs-4">
            <SelectOne
              name={this.makeFullName('property')}
              value={values.property}
              items={data.properties}
              onChange={this.handleChange}
              className="form-control"
              required="required"
            />
          </div>
        </div>
      </div>
    )
  }
}

export default FieldsetSelect;