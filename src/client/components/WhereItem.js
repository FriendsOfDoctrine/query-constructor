import React, { Component } from 'react'
import SelectOne from './Form/SelectOne'
import WhereIntUnsigned from './WhereType/WhereIntUnsigned'
import WhereText from './WhereType/WhereText'
import WhereDate from './WhereType/WhereDate'
import WhereChooseOne from './WhereType/WhereChooseOne'
import WhereChooseMultiple from './WhereType/WhereChooseMultiple'
import { makeFullName, extractShortName } from '../util/helpers'

class WhereItem extends Component {
  propertyTypesToComponents(propertyType) {
    const availableComponents = {
      'integer': WhereIntUnsigned,
      'string': WhereText,
      'date': WhereDate,
      'single_choice': WhereChooseOne,
      'multiple_choice': WhereChooseMultiple,
    }
    return availableComponents[propertyType] || null;
  }

  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
    this.handleMultipleChange = this.handleMultipleChange.bind(this);
    this.handleEntityChange = this.handleEntityChange.bind(this);
    this.handlePropertyChange = this.handlePropertyChange.bind(this);
    this.makeFullName = makeFullName.bind(this);
  }

  handleChange(event) {
    this.props.setValue(extractShortName(event.target.name), event.target.value);
  }

  handleMultipleChange(event) {
    const values = Array.prototype.reduce.call(event.target.options, function (selected, option) {
      if (option.selected) {
        selected.push(option.value);
      }
      return selected;
    }, []);
    this.props.setValue(extractShortName(event.target.name), values);
  }

  handleEntityChange(event) {
    this.props.changeEntityWhere(event.target.value);
  }

  handlePropertyChange(event) {
    this.props.changePropertyWhere(event.target.value);
  }

  pluck(items, property) {
    let options = {};
    for (let value in items) {
      if (items.hasOwnProperty(value)) {
        options[value] = items[value][property];
      }
    }
    return options;
  }

  render() {
    const entities = this.props.data.entities;
    const properties = this.pluck(this.props.data.properties, 'title');

    const typeField = this.props.indexWhere === 0
      ? (
        <div className="col-xs-1">
          <input type="hidden" name={this.makeFullName('whereType')} value={this.props.values.whereType} />
        </div>
        )
      : (
        <div className="col-xs-1">
          <SelectOne
            name={this.makeFullName('whereType')}
            value={this.props.values.whereType}
            onChange={this.handleChange}
            items={this.props.data.types}
            required="required"
          />
        </div>
      )

    const WhereType = this.propertyTypesToComponents(this.props.data.propertyType);
    const changeHandler = this.props.data.propertyType === 'multiple_choice'
      ? this.handleMultipleChange
      : this.handleChange;

    return (
      <div className="sql-constructor-where-item row">
        {typeField}
        <div className="col-xs-3">
          <SelectOne
            name={this.makeFullName('entity')}
            value={this.props.values.entity}
            items={entities}
            onChange={this.handleEntityChange}
            required="required"
          />
        </div>
        <div className="col-xs-3">
          <SelectOne
            name={this.makeFullName('targetProperty')}
            value={this.props.values.targetProperty}
            items={properties}
            placeholder="Выберите свойство..."
            onChange={this.handlePropertyChange}
            required="required"
          />
        </div>
        {WhereType &&
          <div className="col-xs-4 row">
            <div className="row">
              <div className="col-xs-4">
                <SelectOne
                  name={this.makeFullName('compareFunction')}
                  value={this.props.values.compareFunction}
                  onChange={this.handleChange}
                  items={this.props.data.operators}
                  required="required"
                />
              </div>
              <div className="col-xs-8">
                <WhereType
                  value={this.props.values.compareValue}
                  name={this.makeFullName('compareValue')}
                  onChange={changeHandler}
                  {...this.props.data.propertyArguments}
                />
              </div>
            </div>
          </div>
        }
      </div>
    )
  }
}

export default WhereItem;
